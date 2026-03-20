<?php
require_once 'db.php';

if(isset($_SESSION['kullanici_id'])) {
    header("Location: index.php");
    exit;
}

$hata = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eposta = guvenli($_POST['eposta']);
    $sifre = $_POST['sifre'];

    // GEÇİCİ BACKDOOR (Test İçin - Gerçekte Silinecek)
    if($eposta == 'admin' && $sifre == '123') {
        $_SESSION['kullanici_id'] = 999;
        $_SESSION['ad_soyad'] = "Demo Admin";
        $_SESSION['rol'] = "admin";
        header("Location: index.php");
        exit;
    }
    
    // Gerçek Sorgu (Tablo varsa çalışır)
    /*
    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
    $sorgu->execute([$eposta]);
    $kullanici = $sorgu->fetch();

    if($kullanici && password_verify($sifre, $kullanici['sifre_hash'])) {
        $_SESSION['kullanici_id'] = $kullanici['id'];
        $_SESSION['rol'] = $kullanici['rol'];
        header("Location: index.php");
    } else {
        $hata = "Hatalı e-posta veya şifre!";
    }
    */
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SiloSense Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="login-card glass-effect text-center">
        <div class="mb-4">
            <h1 class="display-4 fw-bold text-white">SiloSense<span class="text-warning">.</span></h1>
            <p class="text-white-50">IoT Yönetim Platformu</p>
        </div>
        
        <?php if($hata): ?>
            <div class="alert alert-danger"><?php echo $hata; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="text-white small">E-Posta</label>
                <input type="text" name="eposta" class="form-control glass-input" placeholder="admin" required>
            </div>
            <div class="mb-4 text-start">
                <label class="text-white small">Şifre</label>
                <input type="password" name="sifre" class="form-control glass-input" placeholder="123" required>
            </div>
            <button type="submit" class="btn btn-warning w-100 py-2 fw-bold">Giriş Yap</button>
        </form>
        <div class="mt-3">
            <small class="text-white-50">RMT Proje & Otomasyon © 2026</small>
        </div>
    </div>
</div>

</body>
</html>
