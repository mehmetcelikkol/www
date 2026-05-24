<?php 
require_once 'header.php';
?>




<div class="container-fluid">
  <div class="row">
   <?php
   $sql = "SELECT h.firmaid, h.ekipid1, h.ekipid2, h.tarih, h.arama, h.ziyaret, h.sonuc, h.aciklama, h.gerek,
   f.isim AS firma_isim,
   k.onyuz AS kart_onyuz, k.arkayuz AS kart_arkayuz
   FROM hareket h
   LEFT JOIN firma f ON h.firmaid = f.id
   LEFT JOIN kart k ON h.firmaid = k.firmaid
   ORDER BY h.tarih DESC";

   try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
      $firmaid = $result['firmaid'];
      $ekipid1 = $result['ekipid1'];
      $ekipid2 = $result['ekipid2'];
      $tarih = $result['tarih'];
      
      if ($tarih === "0000-00-00") {
    $formatliTarih = "boş"; // Eğer veri boşsa veya "oooo-oo-oo" ise, "boş" bir değer ata
  } else {
    $formatliTarih = date('d.m.Y', strtotime($tarih)); // Değilse tarihi formatla
  }

  $arama = $result['arama'];
  $ziyaret = $result['ziyaret'];
  $sonuc = $result['sonuc'];
  $aciklama = $result['aciklama'];
  $gerek = $result['gerek'] == 1 ? "EVET" : "HAYIR";
  $firma_isim = $result['firma_isim'];

  $kart_onyuz = $result['kart_onyuz'];
  $kart_arkayuz = $result['kart_arkayuz'];

        // Burada ekip isimlerini almak için ayrıca sorgu yapmanız gerekecek
  $sql_ekip1 = "SELECT isim FROM ekip WHERE id = :ekipid";
  $stmt_ekip1 = $pdo->prepare($sql_ekip1);
  $stmt_ekip1->bindParam(':ekipid', $ekipid1);
  $stmt_ekip1->execute();
  $ekip1_isim = $stmt_ekip1->fetchColumn();

  $sql_ekip2 = "SELECT isim FROM ekip WHERE id = :ekipid";
  $stmt_ekip2 = $pdo->prepare($sql_ekip2);
  $stmt_ekip2->bindParam(':ekipid', $ekipid2);
  $stmt_ekip2->execute();
  $ekip2_isim = $stmt_ekip2->fetchColumn();

  echo '<div class="col-xl-4 col-lg-4 col-md-3 col-sm-3">';
  echo '<div class="glasses_box">';
  if (!empty($kart_onyuz)) {
   echo '<figure><img src="' . $kart_onyuz . '" alt="#" width="400" height="300"/></figure>';
 }
 if (!empty($kart_arkayuz)) {
   echo '<figure><img src="' . $kart_arkayuz . '" alt="#" width="400" height="300"/></figure>';
 }

  //        if (!empty($ekip1_isim)) {
 echo '<h3><span class="blu">Personel 1: </span>' . $ekip1_isim . '</h3>';

  //      }
  //      if (!empty($ekip2_isim)) {
 echo '<h3><span class="blu">Personel 2: </span>' . $ekip2_isim . '</h3>';
  //      }

 echo '<h3><span class="blu">Firma:  </span>' . $firma_isim . '<a href="caritablo.php?firmaid=' . $firmaid . ' " target=”_blank”><i class="fa fa-hourglass-end" aria-hidden="true"></i></a></h3>';    
 echo '<h3><span class="blu">Tarih: </span>' . $formatliTarih . ' <a href="tarihtablo.php?formatliTarih=' . urlencode($tarih) . ' " target=”_blank”><i class="fa fa-binoculars" aria-hidden="true"></i></a></h3>';
 echo '<h3><span class="blu">Sonuç:  </span>' . $sonuc . '</h3>';
 echo '<h3><span class="blu">Tekrar gerekli mi?:  </span>' . $gerek . '<a href="tekrargerek.php?gerek=' . $result['gerek'] . ' " target=”_blank”><i class="fa fa-binoculars" aria-hidden="true"></i></a></h3>';
     //   echo '<p>Ön Yüz: ' . $kart_onyuz . ' Arka Yüz: ' . $kart_arkayuz . '</p>';
 echo '</div>';
 echo '</div>';
}

} catch (PDOException $e) {
 die("Veritabanı hatası: " . $e->getMessage());
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

</div>
</div>

<style>
  /* Sayfanın yüksekliğini şu ankinin 2 katı olarak ayarlıyoruz */
  html, body {
    height: 200%;
  }
</style>


         <!--
         <div class="col-md-12">
            <a class="read_more" href="#">Read More</a>
         </div>
       -->
     </div>
   </div>
 </div>


 <?php 
 require_once 'footer.php';
?>