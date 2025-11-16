<?php
// filepath: c:\wamp64\www\limanrapor\api\get_tank_data.php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

function send_json_error($message, $http_code = 500) {
    http_response_code($http_code);
    echo json_encode(['error' => $message]);
    exit;
}

@require_once '../includes/database.php';
if (!isset($pdo)) {
    send_json_error('Veritabanı bağlantısı kurulamadı.');
}

// --- Parametreleri al ---
$tank_id = filter_input(INPUT_GET, 'tank_id', FILTER_VALIDATE_INT);
$range = filter_input(INPUT_GET, 'range', FILTER_SANITIZE_STRING);
$show_operations = filter_input(INPUT_GET, 'show_operations', FILTER_VALIDATE_BOOLEAN);

if (!$tank_id || !$range) { send_json_error('Geçersiz parametreler.', 400); }

// --- Tarih Aralığını Hesapla (Düzeltilmiş Mantıkla) ---
$start_date_str = null;
$end_date_str = null;
$is_all_data = false;

if ($range === 'custom') {
    $start_date_str = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
    $end_date_str = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
    if (!$start_date_str || !$end_date_str) { send_json_error('Özel aralık için başlangıç ve bitiş tarihleri gereklidir.', 400); }
} elseif ($range !== 'all') {
    try {
        $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
        $start_date = null; $end_date = null;
        switch ($range) {
            case 'last_7_days': $start_date = $today->modify('-6 days'); $end_date = $today; break;
            case 'this_week': $start_date = $today->modify('monday this week'); $end_date = $today; break;
            case 'last_30_days': $start_date = $today->modify('-29 days'); $end_date = $today; break;
            case 'this_month': $start_date = $today->modify('first day of this month'); $end_date = $today; break;
            case 'last_month':
                // --- DÜZELTME BURADA ---
                $start_date = $today->modify('first day of last month');
                $end_date = $today->modify('last day of last month'); // $today üzerinden hesapla
                break;
            case 'this_year': $start_date = $today->modify('first day of january this year'); $end_date = $today; break;
            default:
                if (strpos($range, 'month_') === 0) {
                    $month_num = (int)str_replace('month_', '', $range);
                    if ($month_num >= 1 && $month_num <= 12) {
                        $start_date = $today->setDate($today->format('Y'), $month_num, 1);
                        $end_date = $start_date->modify('last day of this month');
                    }
                }
                break;
        }
        if ($start_date && $end_date) {
            $start_date_str = $start_date->format('Y-m-d');
            $end_date_str = $end_date->format('Y-m-d');
        }
    } catch (Exception $e) { send_json_error('Tarih hesaplama hatası: ' . $e->getMessage()); }
} else { $is_all_data = true; }

// --- Veri Çekme İşlemleri ---
$response_data = [
    'tank_data' => ['time' => [], 'radar_cm' => [], 'basinc_bar' => [], 'sicaklik' => []],
    'operations_data' => []
];

try {
    // 1. Tank verilerini çek
    $sql = "SELECT tarihsaat, rdr, bsnc, pt100 FROM tank_verileri WHERE tank = :tank_id";
    $params = [':tank_id' => $tank_id];
    if (!$is_all_data) {
        if (!$start_date_str || !$end_date_str) { send_json_error('Hesaplanacak geçerli bir tarih aralığı bulunamadı.', 400); }
        $sql .= " AND tarihsaat BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date_str . ' 00:00:00';
        $params[':end_date'] = $end_date_str . ' 23:59:59';
    }
    $sql .= " ORDER BY tarihsaat ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response_data['tank_data']['time'][] = date('d.m H:i', strtotime($row['tarihsaat']));
        $response_data['tank_data']['radar_cm'][] = ($row['rdr'] ?? 0) / 10;
        $response_data['tank_data']['basinc_bar'][] = ($row['bsnc'] ?? 0) / 100;
        $response_data['tank_data']['sicaklik'][] = ($row['pt100'] ?? 0) / 100;
    }

    // 2. Operasyon verilerini çek (eğer istenmişse)
    if ($show_operations && !empty($response_data['tank_data']['time'])) {
        $op_sql = "SELECT gemi_adi, Gemi_no, tonaj, islem, kayit_tarihi FROM gemioperasyon";
        $op_params = [];
        if (!$is_all_data) {
            $op_sql .= " WHERE kayit_tarihi BETWEEN :start_date AND :end_date";
            $op_params[':start_date'] = $params[':start_date'];
            $op_params[':end_date'] = $params[':end_date'];
        }
        $op_sql .= " ORDER BY Gemi_no, kayit_tarihi ASC";
        $op_stmt = $pdo->prepare($op_sql);
        $op_stmt->execute($op_params);
        $operations = $op_stmt->fetchAll(PDO::FETCH_ASSOC);

        $started_ops = [];
        
        $find_closest_sql = "
            SELECT tarihsaat 
            FROM tank_verileri
            WHERE tank = :tank_id
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, tarihsaat, :op_time))
            LIMIT 1;
        ";
        $closest_stmt = $pdo->prepare($find_closest_sql);

        foreach ($operations as $op) {
            $op_key = $op['Gemi_no'];
            if ($op['islem'] === 'basla') {
                $started_ops[$op_key] = $op;
            } elseif ($op['islem'] === 'dur' && isset($started_ops[$op_key])) {
                $start_op = $started_ops[$op_key];

                $closest_stmt->execute([':tank_id' => $tank_id, ':op_time' => $start_op['kayit_tarihi']]);
                $closest_start_row = $closest_stmt->fetch(PDO::FETCH_ASSOC);

                $closest_stmt->execute([':tank_id' => $tank_id, ':op_time' => $op['kayit_tarihi']]);
                $closest_end_row = $closest_stmt->fetch(PDO::FETCH_ASSOC);

                if ($closest_start_row && $closest_end_row) {
                    $closest_start = date('d.m H:i', strtotime($closest_start_row['tarihsaat']));
                    $closest_end = date('d.m H:i', strtotime($closest_end_row['tarihsaat']));

                    if ($closest_start !== $closest_end) {
                        // --- DEĞİŞİKLİK: Dikey çizgiler için ECharts formatı ---
                        
                        // Yeşil Başlangıç Çizgisi
                        $response_data['operations_data'][] = [
                            'name' => "{$start_op['gemi_adi']} (Başlangıç)",
                            'xAxis' => $closest_start,
                            'lineStyle' => ['color' => '#28a745', 'width' => 2, 'type' => 'dashed'],
                            'label' => ['formatter' => '{b}', 'position' => 'insideStartTop', 'color' => '#28a745']
                        ];

                        // Kırmızı Bitiş Çizgisi
                        $response_data['operations_data'][] = [
                            'name' => "{$start_op['gemi_adi']} (Bitiş)",
                            'xAxis' => $closest_end,
                            'lineStyle' => ['color' => '#dc3545', 'width' => 2, 'type' => 'dashed'],
                            'label' => ['formatter' => '{b}', 'position' => 'insideEndTop', 'color' => '#dc3545']
                        ];
                    }
                }
                unset($started_ops[$op_key]);
            }
        }
    }

    echo json_encode($response_data);

} catch (PDOException $e) {
    send_json_error('Veritabanı sorgu hatası: ' . $e->getMessage());
}
?>