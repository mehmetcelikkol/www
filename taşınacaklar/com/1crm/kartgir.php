<?php
// connect.php dosyasını include edin
include 'header.php';

// Form gönderildiğinde verileri işleme
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Firma ID'sini alın
    $firmaID = $_POST['firma'];

    // Ön yüz ve arka yüz resimlerini yüklemek için dosya yolunu belirleyin
    $onYuzDosyaYolu = 'kart/' . basename($_FILES['on_yuz']['name']);
    $arkaYuzDosyaYolu = 'kart/' . basename($_FILES['arka_yuz']['name']);


    // Kart bilgilerini veritabanına kaydetme
    $query = "INSERT INTO kart (firmaid, onyuz, arkayuz) VALUES (:firmaid, :onyuz, :arkayuz)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':firmaid', $firmaID);
    $stmt->bindParam(':onyuz', $onYuzDosyaYolu);
    $stmt->bindParam(':arkayuz', $arkaYuzDosyaYolu);

 
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
        // Dosyaları yükleme
   move_uploaded_file($_FILES['on_yuz']['tmp_name'], $onYuzDosyaYolu);
   move_uploaded_file($_FILES['arka_yuz']['tmp_name'], $arkaYuzDosyaYolu);



   echo "Kart bilgileri başarıyla kaydedildi.";
} else {
    echo "Kart bilgileri kaydedilirken bir hata oluştu.";
}
}
?>

<div class="container-fluid" align="center">
    <div class="col-xl-8 col-lg-4 col-md-3 col-sm-3">
        <div class="glasses_box">
            <h2>Firma Kart Bilgileri Ekleme</h2>
            <form method="POST" enctype="multipart/form-data">
                <label for="firma">Firma Seçin:</label>
                <select name="firma" id="firma">
                    <?php
                // Firma tablosundaki id ve isim sütunlarından verileri çekiyoruz
                    $query = "SELECT id, isim FROM firma";
                    $stmt = $pdo->query($query);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value=\"{$row['id']}\">{$row['isim']}</option>";
                    }
                    ?>
                </select>
                <br><br>
                <label for="on_yuz">Birinci Kartın Resmi:</label>
                <input type="file" name="on_yuz" accept="image/*">
                
                <label for="arka_yuz">ikinci Kartın Resmi:</label>
                <input type="file" name="arka_yuz" accept="image/*">
                <br><br>
                <input type="submit" value="Kaydet">
            </form>
        </div>
    </div>
</div>
<?php 
require_once 'footer.php';
?>