<?php
// api_endpoint.php

session_start();
header('Content-Type: application/json');

// Kullanıcının giriş yapıp yapmadığını kontrol et
if (!isset($_SESSION['mail'])) {
    http_response_code(401); // Yetkisiz erişim
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim. Lütfen giriş yapın.'
    ]);
    exit;
}

// Veritabanı bağlantısını dahil et
require_once 'conn.php'; // Doğru dosya adı ve yolunu kontrol edin

// Tablodan veri çekmek için sorguyu tanımla
$query = "SELECT * FROM cihazlar ORDER BY kayit_tarihi DESC"; // 'your_table_name' yerine gerçek tablo adını yazın

$result = $conn->query($query);

if (!$result) {
    http_response_code(500); // Sunucu Hatası
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanından veri alınamadı.'
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Verileri JSON formatında döndür
http_response_code(200); // Başarılı
echo json_encode([
    'success' => true,
    'data' => $data
]);

?>
