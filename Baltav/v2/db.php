<?php
// SiloSense V2 - Veritabanı Bağlantısı
// Düzeltilmiş Versiyon

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata Raporlama (Geliştirme İçin Açık)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ortam Algılama
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168') !== false);

if($is_local){
    // LOKAL ORTAM (WAMP)
    $db_host = 'localhost'; // Veya 127.0.0.1
    $db_user = 'root';
    $db_pass = ''; 
    $db_name = 'silosense'; // İkinci deneme
} else {
    // CANLI SUNUCU (RMT Hosting)
    $db_host = 'localhost';
    $db_user = 'proje_tr_silosense';
    $db_pass = '0120a0120A';
    $db_name = 'proje_tr_silosense';
}

class Database {
    private static $connection = null;

    public static function getConnection() {
        global $db_host, $db_name, $db_user, $db_pass;

        if (self::$connection === null) {
            try {
                self::$connection = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("<div style='color:red; font-family:sans-serif; padding:20px; border:1px solid red;'>
                        <h3>🔌 DB Bağlantı Hatası:</h3>
                        <p>" . $e->getMessage() . "</p>
                        <small>Host: $db_host | DB: $db_name | User: $db_user</small>
                     </div>");
            }
        }
        return self::$connection;
    }
}

// Helper Fonksiyonlar
function guvenli($veri) {
    return htmlspecialchars(trim($veri));
}
?>
