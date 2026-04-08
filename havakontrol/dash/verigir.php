<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

$servername = "localhost";
$username   = "hava_espdht";
$password   = "0120a0120A";
$dbname     = "hava_espdht";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Sadece POST desteklenir.");
}

$serino   = $_POST['serino']   ?? 'Bilinmiyor';
$temp     = $_POST['temp']     ?? 0;
$hum      = $_POST['hum']      ?? 0;
$wifi     = $_POST['wifi']     ?? 0;
$versiyon = $_POST['versiyon'] ?? '1.0';
$oturum   = $_POST['oturum']   ?? 0;
$kod1dk   = $_POST['kod1dk']   ?? 0;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Baglanti hatasi");
}

$conn->set_charset("utf8mb4");

// IP
$ip = ($_POST['oturum'] == 0 || $_POST['oturum'] == 1) 
    ? $_SERVER['REMOTE_ADDR'] 
    : '';

$sql = "INSERT INTO veriler (serino, temp, hum, wifi, versiyon, oturum, kod1dk, ip, kayit_tarihi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param(
        "sddisiss",
        $serino,
        $temp,
        $hum,
        $wifi,
        $versiyon,
        $oturum,
        $kod1dk,
        $ip
    );
    
    if ($stmt->execute()) {
        echo "OK:" . $stmt->insert_id;
    } else {
        echo "HATA: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "HATA: " . $conn->error;
}

$conn->close();
?>