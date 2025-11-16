<?php
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

// --- Parametreleri al ve Tarih Aralığını Hesapla ---
$range = filter_input(INPUT_GET, 'range', FILTER_SANITIZE_STRING);
if (!$range) { send_json_error('Geçersiz parametreler.', 400); }

// 'all' seçeneği için özel durum: Boşluk doldurma yapılamaz, eski yöntem kullanılır.
if ($range === 'all') {
    try {
        $sql = "SELECT 
                    DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(okuma_zamani) / (15 * 60)) * (15 * 60)), '%d.%m %H:%i') as time_group,
                    sensor_adi,
                    AVG(CASE WHEN debi BETWEEN 0 AND 50000 THEN debi ELSE NULL END) as avg_debi,
                    AVG(CASE WHEN sicaklik BETWEEN -20 AND 150 THEN sicaklik ELSE NULL END) as avg_sicaklik,
                    AVG(CASE WHEN yogunluk BETWEEN 0 AND 2 THEN yogunluk ELSE NULL END) as avg_yogunluk
                FROM flowveri
                GROUP BY time_group, sensor_adi
                ORDER BY sensor_adi, time_group ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response_data = [];
        foreach ($results as $row) {
            $sensor = $row['sensor_adi'];
            if (!isset($response_data[$sensor])) {
                $response_data[$sensor] = ['time' => [], 'debi' => [], 'sicaklik' => [], 'yogunluk' => []];
            }
            
            $response_data[$sensor]['time'][] = $row['time_group'];
            $response_data[$sensor]['debi'][] = round($row['avg_debi'] ?? 0, 2);
            $response_data[$sensor]['sicaklik'][] = round($row['avg_sicaklik'] ?? 0, 2);
            $response_data[$sensor]['yogunluk'][] = round($row['avg_yogunluk'] ?? 0, 4);
        }
        echo json_encode($response_data);
        exit;

    } catch (PDOException $e) {
        send_json_error('Veritabanı sorgu hatası: ' . $e->getMessage());
    }
}

// --- YENİ: Boşluk Doldurma Mantığı ---
$now = new DateTime();
$start_date = new DateTime();
$end_date = new DateTime();

// Tarih aralığını belirle (switch/case bloğu aynı)
switch ($range) {
    case 'last_7_days': $start_date->modify('-6 days'); break;
    case 'this_week': $start_date->modify('this week'); break;
    case 'last_30_days': $start_date->modify('-29 days'); break;
    case 'this_month': $start_date->modify('first day of this month'); break;
    case 'last_month': $start_date->modify('first day of last month'); $end_date->modify('last day of last month'); break;
    case 'custom':
        $start_param = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
        $end_param = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
        if (!$start_param || !$end_param) { send_json_error('Özel aralık için tarihler gereklidir.', 400); }
        $start_date = new DateTime($start_param);
        $end_date = new DateTime($end_param);
        break;
    default:
        if (strpos($range, 'month_') === 0) {
            $month_num = (int)str_replace('month_', '', $range);
            $start_date = new DateTime(date('Y') . "-$month_num-01");
            $end_date = new DateTime($start_date->format('Y-m-t'));
        } else {
            send_json_error('Geçersiz tarih aralığı.', 400);
        }
        break;
}
// Saatleri başlangıç ve bitiş olarak ayarla
$start_date->setTime(0, 0, 0);
$end_date->setTime(23, 59, 59);

$params = [
    ':start_date' => $start_date->format('Y-m-d H:i:s'),
    ':end_date' => $end_date->format('Y-m-d H:i:s')
];

try {
    // 1. Veritabanından mevcut verileri çek
    $sql = "SELECT 
                DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(okuma_zamani) / (15 * 60)) * (15 * 60)), '%d.%m %H:%i') as time_group,
                sensor_adi,
                AVG(CASE WHEN debi BETWEEN 0 AND 50000 THEN debi ELSE NULL END) as avg_debi,
                AVG(CASE WHEN sicaklik BETWEEN -20 AND 150 THEN sicaklik ELSE NULL END) as avg_sicaklik,
                AVG(CASE WHEN yogunluk BETWEEN 0 AND 2 THEN yogunluk ELSE NULL END) as avg_yogunluk
            FROM flowveri
            WHERE okuma_zamani BETWEEN :start_date AND :end_date
            GROUP BY time_group, sensor_adi
            ORDER BY sensor_adi, time_group ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $db_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gelen veriyi daha hızlı erişim için yeniden yapılandır: [sensor][time] => data
    $indexed_results = [];
    foreach ($db_results as $row) {
        $indexed_results[$row['sensor_adi']][$row['time_group']] = $row;
    }
    $sensor_names = array_keys($indexed_results);

    // 2. Boşlukları doldurulmuş tam veri yapısını oluştur
    $response_data = [];
    // Her sensör için başlangıç dizilerini oluştur
    foreach ($sensor_names as $sensor) {
        $response_data[$sensor] = ['time' => [], 'debi' => [], 'sicaklik' => [], 'yogunluk' => []];
    }

    // 3. Başlangıçtan bitişe kadar 15'er dakikalık tüm aralıkları dön
    $interval = new DateInterval('PT15M'); // 15 dakikalık periyot
    $period = new DatePeriod($start_date, $interval, $end_date);

    foreach ($period as $dt) {
        $current_time_group = $dt->format('d.m H:i');
        
        // Her sensör için bu zaman dilimini işle
        foreach ($sensor_names as $sensor) {
            $response_data[$sensor]['time'][] = $current_time_group;

            // Bu zaman diliminde veri var mı?
            if (isset($indexed_results[$sensor][$current_time_group])) {
                // Veri varsa, onu kullan
                $row = $indexed_results[$sensor][$current_time_group];
                $response_data[$sensor]['debi'][] = round($row['avg_debi'] ?? 0, 2);
                $response_data[$sensor]['sicaklik'][] = round($row['avg_sicaklik'] ?? 0, 2);
                $response_data[$sensor]['yogunluk'][] = round($row['avg_yogunluk'] ?? 0, 4);
            } else {
                // Veri yoksa, 0 ata
                $response_data[$sensor]['debi'][] = 0;
                $response_data[$sensor]['sicaklik'][] = 0;
                $response_data[$sensor]['yogunluk'][] = 0;
            }
        }
    }

    echo json_encode($response_data);

} catch (PDOException $e) {
    send_json_error('Veritabanı sorgu hatası: ' . $e->getMessage());
}
?>