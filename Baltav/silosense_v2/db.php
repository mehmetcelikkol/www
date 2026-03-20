<?php
// SiloSense V2 - Veritabanı Bağlantısı
// Ortam Algılama Sistemi

session_start();

$localhost_whitelist = array('127.0.0.1', '::1', 'localhost');

if(in_array($_SERVER['REMOTE_ADDR'], $localhost_whitelist) || strpos($_SERVER['HTTP_HOST'], '192.168') !== false){
    // LOKAL ORTAM (WAMP)
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = ''; // WAMP varsayılan
    $db_name = 'silosense'; // Yereldeki DB adı
} else {
    // CANLI SUNUCU (RMT Hosting)
    $db_host = 'localhost';
    $db_user = 'proje_tr_silosense';
    $db_pass = '0120a0120A';
    $db_name = 'proje_tr_silosense';
}

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("
    <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
        <h1>🔌 Bağlantı Hatası</h1>
        <p>Veritabanına ulaşılamıyor. Lütfen ayarları kontrol edin.</p>
        <small>".$e->getMessage()."</small>
    </div>
    ");
}

// Genel Fonksiyonlar
function guvenli($veri) {
    return htmlspecialchars(trim($veri));
}
?>
