<?php
require_once 'conn.php';

// Kullanıcı oturumu başlatılmış olmalı
session_start();
if (!isset($_SESSION['mail'])) {
    die("Lütfen giriş yapınız.");
}

$mail = $_SESSION['mail'];

// `mail` değerinden `id` almak için `cari` tablosunda sorgu
$sql = "SELECT id FROM cari WHERE mail = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Veritabanı hatası: " . $conn->error);
}

$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Kullanıcı bulunamadı.");
}

$user = $result->fetch_assoc();
$user_id = $user['id']; // Artık `cari` tablosundan `id` alındı

$stmt->close();

// Yeni API Key oluştur
$api_key = bin2hex(random_bytes(32)); // 64 karakterlik rastgele bir key

// API Key'i `api_keys` tablosuna kaydet
$sql = "INSERT INTO api_keys (user_id, api_key) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("API Key oluşturulamadı: " . $conn->error);
}

// Nihai SQL sorgusunu görmek için
$final_sql = sprintf(
    "INSERT INTO api_keys (user_id, api_key) VALUES (%d, '%s')",
    $user_id,
    $api_key
);

// echo "Tam SQL sorgusu: $final_sql\n";

$stmt->bind_param("is", $user_id, $api_key);

if ($stmt->execute()) {
    echo "API Key oluşturuldu: $api_key";
} else {
    echo "API Key oluşturulurken hata oluştu.";
}

$stmt->close();

?>


<a href="index.php" class="btn btn-primary btn-round">Ana Sayfa</a>


