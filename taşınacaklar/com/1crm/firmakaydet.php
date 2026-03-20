
<?php require_once 'header.php'; ?>


<div class="container-fluid">
	<div class="row">

		<?php

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$isim = $_POST['isim'];
			$yetkili1 = $_POST['yetkili1'];
			$mail1 = $_POST['mail1'];
			$tel1 = $_POST['tel1'];
			$yetkili2 = $_POST['yetkili2'];
			$mail2 = $_POST['mail2'];
			$tel2 = $_POST['tel2'];
			$adres = $_POST['adres'];
			//$ydk = $_POST['ydk'];

    // SQL sorgusu oluşturup veritabanına kayıt ekleme işlemleri
			try {
			$sql = "INSERT INTO firma (isim, yetkili1, mail1, tel1, yetkili2, mail2, tel2, adres/*, ydk*/) VALUES (?, ?, ?, ?, ?, ?, ?, ?/*,  ? */)";
			$stmt = $pdo->prepare($sql);
		$stmt->execute([$isim, $yetkili1, $mail1, $tel1, $yetkili2, $mail2, $tel2, $adres/*, $ydk*/]);
		echo "Firma bilgileri başarıyla kaydedildi.";
	} catch (PDOException $e) {
		echo "Hata oluştu: " . $e->getMessage();
	}

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
}
?>

<div class="container-fluid" align="center">
	<div class="col-xl-4 col-lg-4 col-md-3 col-sm-3">
		<div class="glasses_box">
			<h2>Firma Bilgi Ekleme Formu</h2>
			<form action="" method="POST">
				<div class="form-row">
					<div class="expanded-row">
						<label for="isim">Firma İsmi:</label>
						<br>
						<input type="text" name="isim" required>
						<br><br>
						<label for="adres">Adres:</label>
						<br>
						<input type="text" name="adres">

					</div>
				</div>
				<div class="form-row">
					<div class="form-column">
						<label for="yetkili1">Yetkili 1 İsmi:</label>
						<input type="text" name="yetkili1">

						<label for="mail1">Yetkili 1 E-Posta:</label>
						<input type="email" name="mail1">

						<label for="tel1">Yetkili 1 Telefon:</label>
						<input type="tel" name="tel1">
					</div>
					<div class="form-column">
						<label for="yetkili2">Yetkili 2 İsmi:</label>
						<input type="text" name="yetkili2">

						<label for="mail2">Yetkili 2 E-Posta:</label>
						<input type="email" name="mail2">

						<label for="tel2">Yetkili 2 Telefon:</label>
						<input type="tel" name="tel2">
					</div>
				</div>
				<br>
				<?php echo $user_ip = $_SERVER['REMOTE_ADDR']; ?>

<!--
						<div class="expanded-row">
							<div class="form-column"> 
								

								<label for="ydk">Yerel Dağıtım Kodu (YDK):</label>
								<input type="text" name="ydk">
							</div>
						</div>   
					-->
					<div class="form-row">  
						<div class="form-column">
							<input type="submit" value="Kaydet">
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>
</div>
</div>


<?php require_once 'footer.php'; ?>