<?php
$servername = "localhost";
$username = "proje_dht";
$password = "0120a0120A";
$dbname = "proje_dht";

// Veritabanı bağlantısını oluşturma
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol etme
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Toplam veri boyutunu hesaplama
$sql = "SELECT SUM(CHAR_LENGTH(CONCAT(serino, temp, hum, analog))) AS total_data_size FROM veriler";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_size = $row['total_data_size'];

    echo "Toplam veri boyutu: " . $total_size . " karakter";
} else {
    echo "No data available";
}

// Bağlantıyı kapatma
$conn->close();
?>
