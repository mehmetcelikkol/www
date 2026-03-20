<?php
// connect.php dosyasını include edin
include 'header.php';

// Form gönderildiğinde veritabanına veri eklemesi yapma
// ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firmaId = $_POST['firma'];
    $ekip1Id = $_POST['ekip1'];
    $ekip2Id = $_POST['ekip2'];
    $arama = isset($_POST['arama']) ? 1 : 0;
    $ziyaret = isset($_POST['ziyaret']) ? 1 : 0;
    $sonuc = $_POST['sonuc'];
    $aciklama = $_POST['aciklama'];
    $gerek = isset($_POST['gerek']) ? 1 : 0;
    $tarih = $_POST['tarih']; // Yeni eklenen tarih alanından veriyi alalım

    // Hareket tablosuna veri ekleme işlemi
    $query = "INSERT INTO hareket (firmaId, ekipId1, ekipId2, arama, ziyaret, sonuc, aciklama, gerek, tarih)
    VALUES (:firmaId, :ekipId1, :ekipId2, :arama, :ziyaret, :sonuc, :aciklama, :gerek, :tarih)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':firmaId', $firmaId, PDO::PARAM_INT);
    $stmt->bindParam(':ekipId1', $ekip1Id, PDO::PARAM_INT);
    $stmt->bindParam(':ekipId2', $ekip2Id, PDO::PARAM_INT);
    $stmt->bindParam(':arama', $arama, PDO::PARAM_INT);
    $stmt->bindParam(':ziyaret', $ziyaret, PDO::PARAM_INT);
    $stmt->bindParam(':sonuc', $sonuc, PDO::PARAM_STR);
    $stmt->bindParam(':aciklama', $aciklama, PDO::PARAM_STR);
    $stmt->bindParam(':gerek', $gerek, PDO::PARAM_INT);
    $stmt->bindParam(':tarih', $tarih); // Tarih değerini bind edelim


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
 

if ($stmt->execute()) {
    echo "Hareket başarıyla eklendi.";
} else {
    echo "Hata oluştu. Hareket eklenemedi.";
}
}
// ...

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hareket Ekleme Formu</title>
</head>
<body>

    <div class="container-fluid" align="center">
        <div class="col-xl-8 col-lg-4 col-md-3 col-sm-3">
            <div class="glasses_box">
                <h2>Hareket Ekleme Formu</h2>
                <form action="" method="POST">
                    <div class="form-row">
                        <div class="expanded-row">
                         <label for="firma">Firma Seçin:</label><br>
                         <select name="firma" id="firma">

                            <?php
                // connect.php dosyasını burada include edin
                            include 'connect.php';

                // Firma tablosundaki isim sütunundan verileri çekiyoruz
                            $query = "SELECT id, isim FROM firma";
                            $stmt = $pdo->query($query);
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$row['id']}\">{$row['isim']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <br><br>
                <label for="ekip1">Ekip Üyesi 1:</label>
                <select name="ekip1" id="ekip1">
                    <?php
                // Ekip tablosundaki verileri çekiyoruz
                    $query = "SELECT id, isim FROM ekip";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value=\"{$row['id']}\">{$row['isim']}</option>";
                    }
                    ?>
                </select>
                <br><br>
                <label for="ekip2">Ekip Üyesi 2 (Opsiyonel):</label>
                <select name="ekip2" id="ekip2">
                    <option value="">Seçilmedi</option>
                    <?php
                // Ekip tablosundaki verileri tekrar çekiyoruz
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value=\"{$row['id']}\">{$row['isim']}</option>";
                    }
                    ?>
                </select>
                <br><br>
                <label for="tarih">Tarih:</label>
                <input type="date" name="tarih" id="tarih">
                <br><br>
                <label for="arama">Arama:</label>
                <input type="checkbox" name="arama" id="arama">
                <br><br>
                <label for="ziyaret">Ziyaret:</label>
                <input type="checkbox" name="ziyaret" id="ziyaret">
                <br><br>
                <label for="sonuc">Sonuç:</label>
                <input type="text" name="sonuc" id="sonuc">
                <br><br>
                <label for="aciklama">Açıklama:</label>
                <input type="text" name="aciklama" id="aciklama">
                <br><br>
                <label for="gerek">Tekrar Gerekli mi?</label>
                <input type="checkbox" name="gerek" id="gerek">
                <br><br>
                <input type="submit" value="Hareket Ekle">
            </form>
        </div>
    </div>
</div>

<?php 
require_once 'footer.php';
?>

