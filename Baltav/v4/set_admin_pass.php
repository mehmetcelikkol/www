<?php
// set_admin_pass.php
// Güvenli şekilde admin kullanıcısının parola hash'ini günceller.
// Kullanın: tarayıcıda http://localhost/Baltav/v2/set_admin_pass.php?email=admin@ornek.local&pass=yeniParola

require_once __DIR__ . '/db.php';
$db = Database::getConnection();

$email = $_GET['email'] ?? null;
$pass = $_GET['pass'] ?? null;
if (!$email || !$pass) {
    echo "Kullanım: ?email=...&pass=...\n";
    exit;
}

$hash = $pass; // GEÇİCİ: düz metin parola saklama
$stmt = $db->prepare("UPDATE kullanicilar SET sifre_hash = :h WHERE eposta = :e");
$stmt->execute([':h'=>$hash, ':e'=>$email]);
if ($stmt->rowCount()) {
    echo "Parola güncellendi for $email\n";
} else {
    echo "Kayıt bulunamadı veya parola aynı. Lütfen e-posta değerini kontrol edin.\n";
}

?>