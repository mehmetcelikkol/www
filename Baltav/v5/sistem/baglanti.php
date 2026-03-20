<?php
// Hataları ekrana bas (Geliştirme aşaması için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Oturum başlat

$veritabani_sunucu = 'localhost';
$veritabani_adi = 'rmtproje_silosense_v5';
$veritabani_kullanici = 'rmtproje_silosense_v5';
//$veritabani_kullanici = 'root';
$veritabani_sifre = '0120+0120aA';
//$veritabani_sifre = '';

try {
    $db = new PDO("mysql:host=$veritabani_sunucu;dbname=$veritabani_adi;charset=utf8mb4", $veritabani_kullanici, $veritabani_sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Ortak fonksiyonlar
function giris_yapmis_mi()
{
    return isset($_SESSION['kullanici_id']);
}

function yetki_kontrol($izin_verilen_roller)
{
    if (!giris_yapmis_mi()) {
        header('Location: giris.php');
        exit;
    }
    if (!in_array($_SESSION['kullanici_rolu'], $izin_verilen_roller)) {
        die("Bu sayfaya erişim yetkiniz yok!");
    }
}

require_once 'fonksiyonlar.php';
?>
