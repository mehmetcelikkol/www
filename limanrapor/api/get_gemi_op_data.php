<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

function send_json_error($message, $http_code = 404) {
    http_response_code($http_code);
    echo json_encode(['error' => $message]);
    exit;
}

@require_once '../includes/database.php';
if (!isset($pdo)) {
    send_json_error('Veritabanı bağlantısı kurulamadı.', 500);
}

$op_id = filter_input(INPUT_GET, 'op_id', FILTER_VALIDATE_INT);
if (!$op_id) { send_json_error('Geçersiz operasyon ID.', 400); }

try {
    // 1. Adım: Operasyon aralığı sorgusu hazırlanıyor (En Yakın 'dur' Mantığı ile)
    $sql_range = "SELECT 
                    g_start.gemi_adi, g_start.Gemi_no, g_start.tonaj,
                    g_start.kayit_tarihi as first_start,
                    (SELECT MIN(g_stop.kayit_tarihi) 
                     FROM gemioperasyon g_stop 
                     WHERE g_stop.Gemi_no = g_start.Gemi_no 
                       AND g_stop.islem = 'dur' 
                       AND g_stop.kayit_tarihi > g_start.kayit_tarihi) as last_stop
                  FROM gemioperasyon g_start
                  WHERE g_start.id = :op_id AND g_start.islem = 'basla'";
    
    $stmt_range = $pdo->prepare($sql_range);
    $stmt_range->execute([':op_id' => $op_id]);
    $range = $stmt_range->fetch(PDO::FETCH_ASSOC);

    if (!$range || !$range['first_start']) { send_json_error('Bu ID ile bir başlangıç operasyonu bulunamadı.'); }
    
    $start_time = new DateTime($range['first_start']);
    $end_time = $range['last_stop'] ? new DateTime($range['last_stop']) : new DateTime(); 

    $params = [
        ':start_date' => $start_time->format('Y-m-d H:i:s'),
        ':end_date' => $end_time->format('Y-m-d H:i:s')
    ];

    // 2. Adım: Dur/Kalk sayımı için akış verileri çekiliyor.
    $sql_flow_log = "SELECT okuma_zamani, debi FROM flowveri 
                     WHERE okuma_zamani BETWEEN :start_date AND :end_date
                     AND sensor_adi IN ('gflow1', 'gflow2')
                     ORDER BY okuma_zamani ASC";
    $stmt_flow_log = $pdo->prepare($sql_flow_log);
    $stmt_flow_log->execute($params);
    $flow_logs = $stmt_flow_log->fetchAll(PDO::FETCH_ASSOC);

    $stop_start_count = 0;
    $stop_timestamp = null;
    $is_stopped = false;
    foreach ($flow_logs as $log) {
        try {
            if (empty($log['okuma_zamani'])) continue;
            $current_time = new DateTime($log['okuma_zamani']);
        } catch (Exception $e) { continue; }
        $current_debi = (float)$log['debi'];
        if ($current_debi <= 0 && !$is_stopped) {
            $is_stopped = true;
            $stop_timestamp = $current_time;
        } elseif ($current_debi > 0 && $is_stopped) {
            if ($stop_timestamp) {
                $duration = $current_time->getTimestamp() - $stop_timestamp->getTimestamp();
                if ($duration > 180) { $stop_start_count++; }
            }
            $is_stopped = false;
            $stop_timestamp = null;
        }
    }

    // 3. Adım: Diğer istatistikler sorgulanıyor.
    $sql_stats = "SELECT
            COUNT(CASE WHEN debi > 0 THEN 1 END) as flow_records,
            COUNT(CASE WHEN debi <= 0 THEN 1 END) as stop_records,
            AVG(CASE WHEN debi > 0 THEN debi ELSE NULL END) as avg_flow_rate
        FROM flowveri
        WHERE okuma_zamani BETWEEN :start_date AND :end_date AND sensor_adi IN ('gflow1', 'gflow2')";
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // 4. Adım: Toplam miktar hesaplanıyor (Basitleştirilmiş Sorgularla)
    $total_transferred = 0;
    foreach (['gflow1', 'gflow2'] as $sensor) {
        $sql_end = "SELECT toplam FROM flowveri 
                    WHERE sensor_adi = :sensor AND okuma_zamani BETWEEN :start_date AND :end_date 
                    ORDER BY okuma_zamani DESC LIMIT 1";
        $stmt_end = $pdo->prepare($sql_end);
        $stmt_end->execute(array_merge($params, [':sensor' => $sensor]));
        $end_total = $stmt_end->fetchColumn();

        $sql_start = "SELECT toplam FROM flowveri 
                      WHERE sensor_adi = :sensor AND okuma_zamani BETWEEN :start_date AND :end_date 
                      ORDER BY okuma_zamani ASC LIMIT 1";
        $stmt_start = $pdo->prepare($sql_start);
        $stmt_start->execute(array_merge($params, [':sensor' => $sensor]));
        $start_total = $stmt_start->fetchColumn();

        if ($end_total !== false && $start_total !== false) {
            $total_transferred += ($end_total - $start_total);
        }
    }

    // 5. Adım: Grafik verileri çekiliyor.
    $sql_data = "SELECT 
                    DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(okuma_zamani) / 900) * 900), '%d.%m %H:%i') as time_group,
                    sensor_adi, AVG(debi) as avg_debi, AVG(sicaklik) as avg_sicaklik, AVG(yogunluk) as avg_yogunluk
                FROM flowveri
                WHERE okuma_zamani BETWEEN :start_date AND :end_date AND sensor_adi IN ('gflow1', 'gflow2')
                GROUP BY time_group, sensor_adi";
    $stmt_data = $pdo->prepare($sql_data);
    $stmt_data->execute($params);
    $db_results = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    // Eğer operasyon tamamlanmışsa VE HİÇBİR aktivite yoksa hata ver. Devam eden operasyonlar için bu kontrolü atla.
    if ($range['last_stop'] && empty($db_results) && $total_transferred == 0) { 
        send_json_error('Bu tamamlanmış operasyon aralığında herhangi bir akış aktivitesi bulunamadı.'); 
    }

    // 6. Adım: Yanıt hazırlanıyor.
    $indexed_results = [];
    $active_sensors = [];
    foreach ($db_results as $row) {
        $indexed_results[$row['sensor_adi']][$row['time_group']] = $row;
        if ($row['avg_debi'] > 0 && !in_array($row['sensor_adi'], $active_sensors)) {
            $active_sensors[] = $row['sensor_adi'];
        }
    }

    $chart_data = [];
    foreach($active_sensors as $sensor) { $chart_data[$sensor] = ['time' => [], 'debi' => [], 'sicaklik' => [], 'yogunluk' => []]; }
    $period = new DatePeriod($start_time, new DateInterval('PT15M'), $end_time);
    foreach ($period as $dt) {
        $current_time_group = $dt->format('d.m H:i');
        foreach ($active_sensors as $sensor) {
            $chart_data[$sensor]['time'][] = $current_time_group;
            if (isset($indexed_results[$sensor][$current_time_group])) {
                $row = $indexed_results[$sensor][$current_time_group];
                $chart_data[$sensor]['debi'][] = round($row['avg_debi'] ?? 0, 2);
                $chart_data[$sensor]['sicaklik'][] = round($row['avg_sicaklik'] ?? 0, 2);
                $chart_data[$sensor]['yogunluk'][] = round($row['avg_yogunluk'] ?? 0, 4);
            } else {
                $chart_data[$sensor]['debi'][] = 0; $chart_data[$sensor]['sicaklik'][] = 0; $chart_data[$sensor]['yogunluk'][] = 0;
            }
        }
    }

    $final_response = [
        'operation_details' => [
            'gemi_adi' => $range['gemi_adi'],
            'gemi_no' => $range['Gemi_no'],
            'tonaj' => (int)$range['tonaj']
        ],
        'statistics' => [
            'start_time' => $start_time->format('d.m.Y H:i'),
            'end_time' => $range['last_stop'] ? $end_time->format('d.m.Y H:i') : 'Devam Ediyor',
            'avg_flow_rate' => round($stats['avg_flow_rate'] ?? 0, 2),
            'flow_minutes' => (int)($stats['flow_records'] ?? 0),
            'stop_minutes' => (int)($stats['stop_records'] ?? 0),
            'stop_start_count' => (int)$stop_start_count,
            'total_transferred' => round($total_transferred, 2)
        ],
        'chart_data' => $chart_data
    ];

    echo json_encode($final_response);

} catch (Throwable $e) {
    send_json_error('Kritik bir sunucu hatası oluştu: ' . $e->getMessage(), 500);
}
?>