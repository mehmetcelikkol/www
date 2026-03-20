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
$sql = "SELECT `id`, `sayfa`, `ip`, `userid`, `tarih` FROM `log`";
if (!empty($searchTerm)) {
    $sql .= " WHERE `sayfa` LIKE :searchTerm";
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


<!-- Arama kutusu ekleyin -->
<form method="post">
    <input type="text" name="searchTerm" placeholder="sayfa İsmi ile Ara" value="<?= $searchTerm; ?>">
    <button type="submit" name="search">Ara</button>
    <button type="submit" name="clear">Temizle</button>
</form>

    <table border="1">
        <tr>
            <th>Satır no</th>
            <th>Sayfa</th>
            <th>IP</th>
            <th>Kullanıcı id</th>
            <th>Tarih</th>
        </tr>
        <?php foreach ($results as $result) : ?>
            <tr>
                <td><?= $result['id']; ?></td>
                <td><?= $result['sayfa']; ?></td>
                <td><?= $result['ip']; ?></td>
                <td><?= $result['userid']; ?></td>
                <td><?= $result['tarih']; ?></td>
                <td>
                    <form action="firmaguncelle.php" method="post">
                        <input type="hidden" name="firma_id" value="<?= $result['id']; ?>">
                        <input type="hidden" name="isim" value="<?= $result['sayfa']; ?>">
                        <input type="hidden" name="yetkili1" value="<?= $result['ip']; ?>">
                        <input type="hidden" name="mail1" value="<?= $result['userid']; ?>">
                        <input type="hidden" name="tel1" value="<?= $result['tarih']; ?>">
<!--
                        <button type="submit">Güncelle!</button>
-->
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <!--
        <tr>
            <br>
            <center>
                <td colspan="10" style="text-align: center" ><a href="firmakaydet.php" class="btn btn-primary">Aradığım Yukarı da Yok Yeni Firma Kaydet</a></td>
            </center>
            <br>
        </tr>
        --> 
    </table>



<?php require_once 'footer.php'; ?>
