<?php
$servername = "localhost";
$username = "havakont_espdht";
$password = "0120+0120aA";
$dbname = "havakont_espdht";

// SSL Sertifikası Ayarları (Gerekirse)
$ssl_ca = "/path/to/ca-cert.pem";
$ssl_cert = "/path/to/client-cert.pem";
$ssl_key = "/path/to/client-key.pem";

// Veritabanı bağlantısı oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// SSL Ayarlarını Yapılandırma (Gerekirse)
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
else
{
    echo "Bağlantı ok";
}

// SSL kullanarak bağlanmak için (Gerekirse)
$conn->ssl_set($ssl_key, $ssl_cert, $ssl_ca, NULL, NULL);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// POST metodu ile gelen verileri al
$serino = $_POST['serino'];
$temp = $_POST['temp'];
$hum = $_POST['hum'];
$wifi = $_POST['wifi'];
$versiyon = $_POST['versiyon'];
$oturum = $_POST['oturum'];
$kod1dk = $_POST['kod1dk'];
$ip = (string) $_SERVER["REMOTE_ADDR"];


// SQL sorgusunu hazırlama
$sql = "INSERT INTO veriler (serino, temp, hum, wifi, versiyon, oturum, kod1dk, ip) VALUES ('$serino', $temp, $hum, $wifi, $versiyon, $oturum, $kod1dk, '$ip')";


// Veritabanına veri ekleme
if ($conn->query($sql) === TRUE) {
    echo $sql;

} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Bağlantıyı kapatma
$conn->close();
?>
