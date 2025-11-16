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
if (!isset($pdo)) { send_json_error('Veritabanı bağlantısı kurulamadı.'); }

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
// --- YENİ: Veri Özetleme (Aggregation) Mantığı ---
$response_data = [
    'tank_data' => ['time' => [], 'radar_cm' => [], 'basinc_bar' => [], 'sicaklik' => []],
    'operations_data' => []
];

try {
    // Adım 1: Ana Grafik Verisini Özetleyerek Çek
    $max_points = 1500; // Grafikte gösterilecek maksimum nokta sayısı
    $base_query = "FROM tank_verileri WHERE tank = :tank_id";
    $params = [':tank_id' => $tank_id];

    if (!$is_all_data) {
        if (!$start_date_str || !$end_date_str) { send_json_error('Hesaplanacak geçerli bir tarih aralığı bulunamadı.', 400); }
        $base_query .= " AND tarihsaat BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $start_date_str . ' 00:00:00';
        $params[':end_date'] = $end_date_str . ' 23:59:59';
    }

    // Veri sayısını bul
    $count_stmt = $pdo->prepare("SELECT COUNT(*) " . $base_query);
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();

    $sql = "";
    if ($total_rows > $max_points) {
        // Çok fazla veri var, özetleme yap (Aggregation)
        $group_interval = ceil($total_rows / $max_points);
        $sql = "SELECT 
                    DATE_FORMAT(MIN(tarihsaat), '%d.%m %H:%i') as time,
                    AVG(rdr) as rdr,
                    AVG(bsnc) as bsnc,
                    AVG(pt100) as pt100
                FROM (
                    SELECT *, FLOOR((@row_number:=@row_number + 1) / {$group_interval}) as grp
                    FROM tank_verileri, (SELECT @row_number:=0) as r
                    WHERE tank = :tank_id " .
                    (!$is_all_data ? "AND tarihsaat BETWEEN :start_date AND :end_date " : "") .
                    "ORDER BY tarihsaat
                ) as sub
                GROUP BY grp
                ORDER BY MIN(tarihsaat)";
    } else {
        // Yeterince az veri var, hepsini çek
        $sql = "SELECT tarihsaat as time, rdr, bsnc, pt100 " . $base_query .
               (!$is_all_data ? " AND tarihsaat BETWEEN :start_date AND :end_date " : "") .
               "ORDER BY tarihsaat ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response_data['tank_data']['time'][] = is_numeric($row['time']) ? date('d.m H:i', $row['time']) : $row['time'];
        $response_data['tank_data']['radar_cm'][] = round(($row['rdr'] ?? 0) / 10, 2);
        $response_data['tank_data']['basinc_bar'][] = round(($row['bsnc'] ?? 0) / 100, 3);
        $response_data['tank_data']['sicaklik'][] = round(($row['pt100'] ?? 0) / 100, 2);
    }

    // Adım 2: Operasyonları Çek (YENİ VE DOĞRU MANTIK)
    if ($show_operations && !empty($response_data['tank_data']['time'])) {
        // Adım A: İlgili aralıktaki tüm operasyonları çek
        $op_sql = "SELECT gemi_adi, Gemi_no, tonaj, islem, kayit_tarihi FROM gemioperasyon";
        $op_sql_params = [];
        if (!$is_all_data) {
            $op_sql .= " WHERE kayit_tarihi BETWEEN :start_date AND :end_date";
            $op_sql_params[':start_date'] = $params[':start_date'];
            $op_sql_params[':end_date'] = $params[':end_date'];
        }
        $op_sql .= " ORDER BY kayit_tarihi ASC";
        
        $op_stmt = $pdo->prepare($op_sql);
        $op_stmt->execute($op_sql_params);
        $operations = $op_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Adım B: Eşleştirme yap ve en yakın zamanları ÖZETLENMİŞ VERİDEN bul
        $started_ops_stack = [];
        $chart_time_points = $response_data['tank_data']['time']; // Grafiğin X ekseni verisi

        // PHP'de en yakın zamanı bulan bir yardımcı fonksiyon
        function find_closest_time_in_array($search_time, $time_array) {
            $closest_time = null;
            $min_diff = PHP_INT_MAX;
            $search_timestamp = strtotime($search_time);

            foreach ($time_array as $time_point) {
                $point_timestamp = strtotime(date('Y').'-'.str_replace(' ', '-', str_replace('.', '-', $time_point)));
                $diff = abs($search_timestamp - $point_timestamp);
                if ($diff < $min_diff) {
                    $min_diff = $diff;
                    $closest_time = $time_point;
                }
            }
            return $closest_time;
        }

        foreach ($operations as $op) {
            $op_key = $op['Gemi_no'];
            if ($op['islem'] === 'basla') {
                if (!isset($started_ops_stack[$op_key])) $started_ops_stack[$op_key] = [];
                $started_ops_stack[$op_key][] = $op;
            } 
            elseif ($op['islem'] === 'dur' && isset($started_ops_stack[$op_key]) && !empty($started_ops_stack[$op_key])) {
                $start_op = array_shift($started_ops_stack[$op_key]);

                // Veritabanına sormak yerine, PHP'de en yakın zamanı bul
                $closest_start = find_closest_time_in_array($start_op['kayit_tarihi'], $chart_time_points);
                $closest_end = find_closest_time_in_array($op['kayit_tarihi'], $chart_time_points);

                if ($closest_start && $closest_end && $closest_start !== $closest_end) {
                    // Yeşil Başlangıç Çizgisi
                    $response_data['operations_data'][] = [
                        'name' => "{$start_op['gemi_adi']} (Başlangıç)", 'xAxis' => $closest_start,
                        'lineStyle' => ['color' => '#28a745', 'width' => 2, 'type' => 'dashed'],
                        'label' => ['formatter' => '{b}', 'position' => 'insideStartTop', 'color' => '#fff', 'backgroundColor' => '#28a745', 'padding' => [3, 6], 'borderRadius' => 4]
                    ];
                    // Kırmızı Bitiş Çizgisi
                    $response_data['operations_data'][] = [
                        'name' => "{$start_op['gemi_adi']} (Bitiş)", 'xAxis' => $closest_end,
                        'lineStyle' => ['color' => '#dc3545', 'width' => 2, 'type' => 'dashed'],
                        'label' => ['formatter' => '{b}', 'position' => 'insideEndTop', 'color' => '#fff', 'backgroundColor' => '#dc3545', 'padding' => [3, 6], 'borderRadius' => 4]
                    ];
                }
            }
        }
    }

    echo json_encode($response_data);

} catch (PDOException $e) {
    send_json_error('Veritabanı sorgu hatası: ' . $e->getMessage());
}
?>