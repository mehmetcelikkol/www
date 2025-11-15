<?php
// filepath: c:\wamp64\www\limanrapor\api\get_tank_data.php

// --- HATA YAKALAMA BAŞLANGICI ---
// Olası PHP hatalarını gizleyerek JSON yapısının bozulmasını engelle
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Standart bir JSON hata çıktısı için yardımcı fonksiyon
function send_json_error($message, $http_code = 500) {
    http_response_code($http_code);
    echo json_encode(['error' => $message]);
    exit;
}
// --- HATA YAKALAMA SONU ---

// DOĞRU DOSYAYI DAHİL ET: Veritabanı bağlantısını kuran 'database.php' olmalı.
// '@' işareti, dosya bulunamazsa veya içinde bir hata olursa oluşacak uyarıyı bastırır.
@require_once '../includes/database.php';

// $pdo nesnesinin, dahil edilen dosya tarafından başarıyla oluşturulup oluşturulmadığını kontrol et.
if (!isset($pdo)) {
    send_json_error('Veritabanı bağlantısı kurulamadı. `includes/database.php` dosyasının yolu veya içeriği hatalı olabilir.');
}

// --- Buradan sonrası sizin kodunuz, sadece hata yönetimi eklendi ---

$tank_id = filter_input(INPUT_GET, 'tank_id', FILTER_VALIDATE_INT);
$range = filter_input(INPUT_GET, 'range', FILTER_SANITIZE_STRING);

if (!$tank_id || !$range) {
    send_json_error('Geçersiz parametreler: tank_id ve range gereklidir.', 400);
}

try {
    $today = new DateTimeImmutable('now', new DateTimeZone('Europe/Istanbul'));
} catch (Exception $e) {
    send_json_error('Sunucu saat dilimi yapılandırılamadı.');
}

$start_date = null;
$end_date = null;

switch ($range) {
    case 'this_week':
        $start_date = $today->modify('monday this week');
        $end_date = $today;
        break;
    case 'last_7_days':
        $start_date = $today->modify('-6 days');
        $end_date = $today;
        break;
    case 'this_month':
        $start_date = $today->modify('first day of this month');
        $end_date = $today;
        break;
    case 'last_month':
        $start_date = $today->modify('first day of last month');
        $end_date = $start_date->modify('last day of this month');
        break;
    case 'this_year':
        $start_date = $today->modify('first day of january this year');
        $end_date = $today;
        break;
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

if (!$start_date || !$end_date) {
    send_json_error('Geçersiz tarih aralığı: ' . htmlspecialchars($range), 400);
}

$chart_data = [
    'time' => [],
    'radar_cm' => [],
    'basinc_bar' => [],
    'sicaklik' => []
];

try {
    $sql = "SELECT tarihsaat, rdr, bsnc, pt100 
            FROM tank_verileri 
            WHERE tank = :tank_id 
              AND tarihsaat BETWEEN :start_date AND :end_date 
            ORDER BY tarihsaat ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tank_id' => $tank_id,
        ':start_date' => $start_date->format('Y-m-d 00:00:00'),
        ':end_date' => $end_date->format('Y-m-d 23:59:59')
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $chart_data['time'][] = date('d.m H:i', strtotime($row['tarihsaat']));
        $chart_data['radar_cm'][] = ($row['rdr'] ?? 0) / 10;
        $chart_data['basinc_bar'][] = ($row['bsnc'] ?? 0) / 100;
        $chart_data['sicaklik'][] = ($row['pt100'] ?? 0) / 10;
    }

    echo json_encode($chart_data);

} catch (PDOException $e) {
    send_json_error('Veritabanı sorgu hatası: ' . $e->getMessage());
}
?>