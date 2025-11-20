<?php
// Çalışma ortamını kontrol et
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost') {
    // WAMP üzerinde çalışıyorsanız 'root' kullanıcısını kullan
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "havakont_espdht";
} else {
    // Diğer ortamlarda 'hava_espdht' kullanıcısını kullan
    $servername = "localhost";  // veya sunucu adresi
    $username = "havakont_espdht";
    $password = "0120+0120aA";
    $dbname = "havakont_espdht";
}

// Veritabanı bağlantısını oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sunucu saat dilimini Türkiye (UTC+3) olarak ayarla
$conn->query("SET time_zone = '+03:00'");
?>
