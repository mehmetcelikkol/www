<?php
// Veritabanı bağlantısı ve ortak değişkenler

// Config dosyasının yolunu düzelt
$config_file = dirname(__DIR__) . '/config.php';

if (file_exists($config_file)) {
    $config = include $config_file;
} else {
    die("Config dosyası bulunamadı: " . $config_file);
}

// Config doğrulama
if (!is_array($config)) {
    die("Config dosyası geçersiz format!");
}

try {
    // PDO bağlantısı oluştur
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Başarılı bağlantı
    // echo "✅ Veritabanı bağlantısı başarılı! (scada1)";
    
} catch(PDOException $e) {
    // Hata durumunda detaylı bilgi
    echo "<div style='background: #fee; padding: 20px; margin: 20px; border: 1px solid #fcc; border-radius: 5px;'>";
    echo "<h3>❌ Veritabanı Bağlantı Hatası</h3>";
    echo "<p><strong>Veritabanı:</strong> {$config['dbname']}</p>";
    echo "<p><strong>Host:</strong> {$config['host']}</p>";
    echo "<p><strong>Hata:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Kontrol edilecekler:</strong></p>";
    echo "<ul>";
    echo "<li>WAMP servisleri çalışıyor mu?</li>";
    echo "<li>'scada1' veritabanı mevcut mu?</li>";
    echo "<li>MySQL'de UTF8 charset destekleniyor mu?</li>";
    echo "</ul>";
    echo "</div>";
    
    // Geliştirme için devam et
    $pdo = null;
}

// Tarih filtreleri - son 30 gün
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Debug modu
$debug_mode = true;

/*
// --- SORUNUN KAYNAĞI OLAN BU BLOK YORUM SATIRI HALİNE GETİRİLDİ ---
// Tablo isimlerini Türkçe karşılıkları
$table_names_tr = [
    'tank_verileri' => 'Tank Seviyeleri',
    'akis_hizi' => 'Akış Hızı Grafiği',
    'gemi_bosaltma' => 'Gemi Boşaltma Operasyonları',
    'tir_islemleri' => 'Tır İşlemleri'
];
*/
?>