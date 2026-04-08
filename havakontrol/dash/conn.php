<?php
// Çalışma ortamını kontrol et
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost') {
    // WAMP üzerinde çalışıyorsanız 'root' kullanıcısını kullan
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "hava_espdht";
} else {
    // Diğer ortamlarda 'hava_espdht' kullanıcısını kullan
    $servername = "localhost";  // veya sunucu adresi
    $username = "hava_espdht";
    $password = "0120a0120A";
    $dbname = "hava_espdht";
}

// Veritabanı bağlantısını oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
