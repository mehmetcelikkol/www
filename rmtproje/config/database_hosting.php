<?php
// Hosting için veritabanı ayarları
$host = 'localhost'; // Hosting sağlayıcınızın verdiği host
$dbname = 'proje_rmt'; // Hosting'deki veritabanı adı
$username = 'proje_rmt'; // Hosting'deki kullanıcı adı
$password = '0120a0120A'; // Hosting'deki şifre

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>