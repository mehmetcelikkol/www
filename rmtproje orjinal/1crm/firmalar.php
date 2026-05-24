<?php require_once 'header.php'; 

// Arama işlemi için kullanılacak değişken
$searchTerm = '';

// Arama kutusu ile gönderilen veriyi alın
if (isset($_POST['search'])) {
    $searchTerm = $_POST['searchTerm'];
}

// Temizle butonu ile arama kutusunu sıfırlayın
if (isset($_POST['clear'])) {
    $searchTerm = '';
}

// SQL sorgusu oluşturun ve firmaları getirin
$sql = "SELECT `id`, `isim`, `yetkili1`, `mail1`, `tel1`, `yetkili2`, `mail2`, `tel2`, `adres` FROM `firma`";
if (!empty($searchTerm)) {
    $sql .= " WHERE `isim` LIKE :searchTerm";
}

try {
    $stmt = $pdo->prepare($sql);
    
    if (!empty($searchTerm)) {
        $searchTerm = '%' . $searchTerm . '%';
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Hata oluştu: " . $e->getMessage();
}
?>


<?php 
// Log atma işlemleri
if (!$alreadyLogged) {

             // log bilgilerini kaydet
    $user_ip = $_SERVER['REMOTE_ADDR']; // kullanıcının ip adresini  al

$sayfa = basename($_SERVER['PHP_SELF']); // Aktif sayfa adını al

$log_sql = "INSERT INTO log (ip, sayfa) VALUES (?, ?)";
$log_stmt = $pdo->prepare($log_sql);
$log_stmt->execute([$user_ip, $sayfa]);

   $alreadyLogged = true; // Log atıldı olarak işaretle
 }
 ?>


<center>
<!-- Arama kutusu ekleyin -->
<form method="post" >
    <input type="text" name="searchTerm" placeholder="Firma İsmi ile Ara" value="<?= $searchTerm; ?>">
    <button type="submit" name="search">Ara</button>
    <button type="submit" name="clear">Temizle</button>
</form>
</center>
    <table border="1" align="center">
        <tr>
            <th>Firma Sn.</th>
            <th>Firma İsim</th>
            <th>Yetkili1</th>
            <th>Mail1</th>
            <th>Telefon1</th>
            <th>Yetkili2</th>
            <th>Mail2</th>
            <th>Telefon2</th>
            <th>Adres</th>
            <th>Güncelle</th>
        </tr>
        <?php foreach ($results as $result) : ?>
            <tr>
                <td><?= $result['id']; ?></td>
                <td><?= $result['isim']; ?></td>
                <td><?= $result['yetkili1']; ?></td>
                <td><?= $result['mail1']; ?></td>
                <td><?= $result['tel1']; ?></td>
                <td><?= $result['yetkili2']; ?></td>
                <td><?= $result['mail2']; ?></td>
                <td><?= $result['tel2']; ?></td>
                <td><?= $result['adres']; ?></td>
                <td>
                    <form action="firmaguncelle.php" method="post">
                        <input type="hidden" name="firma_id" value="<?= $result['id']; ?>">
                        <input type="hidden" name="isim" value="<?= $result['isim']; ?>">
                        <input type="hidden" name="yetkili1" value="<?= $result['yetkili1']; ?>">
                        <input type="hidden" name="mail1" value="<?= $result['mail1']; ?>">
                        <input type="hidden" name="tel1" value="<?= $result['tel1']; ?>">
                        <input type="hidden" name="yetkili2" value="<?= $result['yetkili2']; ?>">
                        <input type="hidden" name="mail2" value="<?= $result['mail2']; ?>">
                        <input type="hidden" name="tel2" value="<?= $result['tel2']; ?>">
                        <input type="hidden" name="adres" value="<?= $result['adres']; ?>">
                        <button type="submit">Güncelle!</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <br>
            <center>
                <td colspan="10" style="text-align: center" ><a href="firmakaydet.php" class="btn btn-primary">Aradığım Yukarı da Yok Yeni Firma Kaydet</a></td>
            </center>
            <br>
        </tr> 
    </table>



<?php require_once 'footer.php'; ?>
