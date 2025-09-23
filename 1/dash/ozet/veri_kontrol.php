<?php
// Hata raporlama açma
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantısını dahil et
include '../conn.php';

// Veritabanı bağlantısı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

header('Content-Type: application/json');

// SQL sorgusu
$sql = "SELECT 
    v.serino,
    v.temp,
    v.hum,
    v.wifi,
    v.versiyon,
    v.oturum,
    v.ip,
    v.kayit_tarihi,
    c.firmaid,
    c.konum,
    cr.unvan as firma_adi,
    TIMESTAMPDIFF(MINUTE, v.kayit_tarihi, NOW()) as dakika_once
FROM (
    SELECT serino, MAX(kayit_tarihi) as son_kayit
    FROM veriler 
    GROUP BY serino
) as son
JOIN veriler v ON v.serino = son.serino AND v.kayit_tarihi = son.son_kayit
LEFT JOIN cihazlar c ON v.serino = c.serino
LEFT JOIN cari cr ON c.firmaid = cr.id
ORDER BY v.kayit_tarihi DESC";

// SQL sorgusunu çalıştır
$result = $conn->query($sql);

// Sorgu hatası kontrolü
if (!$result) {
    die("SQL Hatası: " . $conn->error);
}

// Veri çekme işlemi
$data = [];
while($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Eğer veri yoksa mesaj göster
if (empty($data)) {
    echo json_encode(["message" => "Veri bulunamadı."]);
    exit;
}

// Veriyi JSON formatında döndür
echo json_encode($data);

?>
