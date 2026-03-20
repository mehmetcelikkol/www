<?php require_once 'header.php'; ?>

<?php
// Veritabanı bağlantısı
// require_once 'db_connect.php';

// Değişkenleri tanımlayın
$isim = $yetkili1 = $mail1 = $tel1 = $yetkili2 = $mail2 = $tel2 = $adres = '';
$firma_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // POST yöntemiyle gönderilen verileri alın
    $firma_id = $_POST['firma_id'];
    $isim = $_POST['isim'];
    $yetkili1 = $_POST['yetkili1'];
    $mail1 = $_POST['mail1'];
    $tel1 = $_POST['tel1'];
    $yetkili2 = $_POST['yetkili2'];
    $mail2 = $_POST['mail2'];
    $tel2 = $_POST['tel2'];
    $adres = $_POST['adres'];
    
    // SQL sorgusu oluşturup veritabanında güncelleme işlemi
    try {
        $sql = "UPDATE firma SET
            isim = :isim,
            yetkili1 = :yetkili1,
            mail1 = :mail1,
            tel1 = :tel1,
            yetkili2 = :yetkili2,
            mail2 = :mail2,
            tel2 = :tel2,
            adres = :adres
            WHERE id = :firma_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':firma_id', $firma_id, PDO::PARAM_INT);
        $stmt->bindParam(':isim', $isim, PDO::PARAM_STR);
        $stmt->bindParam(':yetkili1', $yetkili1, PDO::PARAM_STR);
        $stmt->bindParam(':mail1', $mail1, PDO::PARAM_STR);
        $stmt->bindParam(':tel1', $tel1, PDO::PARAM_STR);
        $stmt->bindParam(':yetkili2', $yetkili2, PDO::PARAM_STR);
        $stmt->bindParam(':mail2', $mail2, PDO::PARAM_STR);
        $stmt->bindParam(':tel2', $tel2, PDO::PARAM_STR);
        $stmt->bindParam(':adres', $adres, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
          //  echo "Firma bilgileri başarıyla güncellendi.";
        } else {
            echo "Veriyi güncellerken bir hata oluştu.";
        }
    } catch (PDOException $e) {
        echo "Hata oluştu: " . $e->getMessage();
    }
}

// Firma verilerini getirin
if (isset($_GET['edit'])) {
    $firma_id = $_GET['edit'];
    $sql = "SELECT * FROM firma WHERE id = :firma_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':firma_id', $firma_id, PDO::PARAM_INT);
    $stmt->execute();
    $firma = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($firma) {
        $isim = $firma['isim'];
        $yetkili1 = $firma['yetkili1'];
        $mail1 = $firma['mail1'];
        $tel1 = $firma['tel1'];
        $yetkili2 = $firma['yetkili2'];
        $mail2 = $firma['mail2'];
        $tel2 = $firma['tel2'];
        $adres = $firma['adres'];
    }
}
?>

<!-- Kullanıcıya düzenleme formunu göster -->
<form method="post" >
    <label for="isim">Firma İsim:</label>
    <input type="text" id="isim" name="isim" value="<?= $isim; ?>"><br>

    <label for="yetkili1">Yetkili 1:</label>
    <input type="text" id="yetkili1" name="yetkili1" value="<?= $yetkili1; ?>"><br>

    <label for="mail1">Mail 1:</label>
    <input type="text" id="mail1" name="mail1" value="<?= $mail1; ?>"><br>

    <label for="tel1">Telefon 1:</label>
    <input type="text" id="tel1" name="tel1" value="<?= $tel1; ?>"><br>

    <label for="yetkili2">Yetkili 2:</label>
    <input type="text" id="yetkili2" name="yetkili2" value="<?= $yetkili2; ?>"><br>

    <label for="mail2">Mail 2:</label>
    <input type="text" id="mail2" name="mail2" value="<?= $mail2; ?>"><br>

    <label for="tel2">Telefon 2:</label>
    <input type="text" id="tel2" name="tel2" value="<?= $tel2; ?>"><br>

    <label for="adres">Adres:</label>
    <textarea id="adres" name="adres"><?= $adres; ?></textarea><br>

    <input type="hidden" name="firma_id" value="<?= $firma_id; ?>">
    
    <input type="submit" value="Güncelle">
</form>

<?php require_once 'footer.php'; ?>
