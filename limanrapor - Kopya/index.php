<?php
// Ana sayfa - Sayfa yönlendirmesi
require_once 'includes/database.php';

// Sayfa parametresini al
$page = $_GET['page'] ?? 'tank_izleme';

// Geçerli sayfalar
$valid_pages = [
    'tank_izleme' => 'Tank İzleme',
    'akis_hizi' => 'Akış Hızı',
    'gemi_bosaltma' => 'Gemi Boşaltma',
    'tir_islemleri' => 'Tır İşlemleri'
];

// Sayfa kontrolü
if (!array_key_exists($page, $valid_pages)) {
    $page = 'tank_izleme';
}

// Header'ı dahil et
require_once 'includes/header.php';

// İlgili sayfa dosyasını dahil et
$page_file = "pages/{$page}.php";
if (file_exists($page_file)) {
    require_once $page_file;
} else {
    echo "<div class='error'>Sayfa bulunamadı: {$page}</div>";
}

// Footer'ı dahil et
require_once 'includes/footer.php';
?>

<?php
// filepath: c:\wamp64\www\limanrapor\includes\database.php
// Veritabanı bağlantısı ve ortak değişkenler

// Veritabanı yapılandırmasını yükle - YOL DÜZELTMESİ
$config_file = __DIR__ . '/../config.php';

if (file_exists($config_file)) {
    $config = include $config_file;
} else {
    // Config dosyası bulunamazsa varsayılan değerler
    $config = [
        'host' => 'localhost',
        'dbname' => 'scada_data',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',  // Karakterset düzeltmesi
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
}

// Config doğrulama
if (!is_array($config)) {
    die("Config dosyası geçersiz format!");
}

// Eksik anahtarları kontrol et ve varsayılanları ekle
$defaults = [
    'host' => 'localhost',
    'dbname' => 'scada_data',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',  // utf8 yerine utf8mb4 kullan
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

foreach ($defaults as $key => $value) {
    if (!isset($config[$key])) {
        $config[$key] = $value;
    }
}

try {
    // DSN oluştur - charset düzeltmesi
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Bağlantı başarılı
    // echo "Veritabanı bağlantısı başarılı!";
    
} catch(PDOException $e) {
    // Detaylı hata bilgisi
    $error_details = [
        'Host' => $config['host'],
        'Database' => $config['dbname'],
        'Username' => $config['username'],
        'Charset' => $config['charset'],
        'Error' => $e->getMessage()
    ];
    
    echo "<div style='background: #fee; padding: 20px; margin: 20px; border: 1px solid #fcc; border-radius: 5px;'>";
    echo "<h3>❌ Veritabanı Bağlantı Hatası</h3>";
    echo "<pre>" . print_r($error_details, true) . "</pre>";
    echo "<p><strong>Çözüm önerileri:</strong></p>";
    echo "<ul>";
    echo "<li>WAMP/XAMPP servislerinin çalıştığından emin olun</li>";
    echo "<li>config.php dosyasındaki veritabanı bilgilerini kontrol edin</li>";
    echo "<li>MySQL charset ayarlarını kontrol edin</li>";
    echo "</ul>";
    echo "</div>";
    
    // Geliştirme için bağlantı olmadan devam et
    $pdo = null;
}

// Tarih filtreleri - sabit aralık (son 30 gün)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Debug modu
$debug_mode = true;

// Tablo isimlerini Türkçe karşılıkları
$table_names_tr = [
    'tank_verileri' => 'Tank Seviyeleri',
    'akis_hizi' => 'Akış Hızı Grafiği',
    'gemi_bosaltma' => 'Gemi Boşaltma Operasyonları',
    'tir_islemleri' => 'Tır İşlemleri'
];
?>