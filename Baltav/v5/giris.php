<?php
require_once 'sistem/baglanti.php';

if (giris_yapmis_mi()) {
    header('Location: index.php');
    exit;
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');

    if (empty($kullanici_adi) || empty($sifre)) {
        $hata = 'Lütfen kullanıcı adı ve şifrenizi giriniz.';
    } else {
        $sorgu = $db->prepare("SELECT id, kullanici_adi, sifre, rol, entegre_id, isletmeci_id FROM kullanicilar WHERE kullanici_adi = ?");
        $sorgu->execute([$kullanici_adi]);
        $kullanici = $sorgu->fetch();

        // Kurulum esnasında test edebilmek için sabit şifreyi kullandık: '123456' password_hash edilmişti.
        if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            $_SESSION['kullanici_id'] = $kullanici['id'];
            $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
            $_SESSION['kullanici_rolu'] = $kullanici['rol'];
            $_SESSION['entegre_id'] = $kullanici['entegre_id'];
            $_SESSION['isletmeci_id'] = $kullanici['isletmeci_id'];
            
            sistem_log_yaz($db, 'Sisteme Giriş', 'Kullanıcı kendi paneline giriş yaptı.');
            
            header('Location: index.php');
            exit;
        } else {
            $hata = 'Hatalı kullanıcı adı veya şifre!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Silosense v5</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            /* Tarım, buğday veya silo hissi uyandıran modern, ücretsiz bir Unsplash görseli */
            background-image: url('https://images.unsplash.com/photo-1595844730298-b960fad17e2e?q=80&w=2070&auto=format&fit=crop'); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(20, 33, 61, 0.7) 0%, rgba(20, 33, 61, 0.4) 100%);
            z-index: 1;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            z-index: 2;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            color: white;
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container h1 {
            margin: 0;
            font-weight: 700;
            font-size: 32px;
            letter-spacing: 1px;
            color: #fca311; /* Yem Sarısı */
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .logo-container p {
            margin-top: 5px;
            font-size: 15px;
            color: #e5e5e5;
            font-weight: 300;
        }
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 400;
            color: #ddd;
        }
        .form-control {
            width: 100%;
            padding: 14px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 16px;
            outline: none;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #fca311;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 15px rgba(252, 163, 17, 0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #fca311;
            color: #14213d;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-submit:hover {
            background: #ffb703;
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(252, 163, 17, 0.4);
        }
        .btn-submit:active {
            transform: translateY(1px);
        }
        .alert {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ffcccc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .demo-info {
            margin-top: 25px;
            font-size: 12px;
            text-align: center;
            color: #aaa;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-overlay"></div>
    <div class="glass-panel">
        <div class="logo-container">
            <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px; background: rgba(255, 255, 255, 0.95); padding: 12px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                <img src="assets/img/btv-logo.png" alt="BTV Logo" style="height: 45px; object-fit: contain;">
                <img src="assets/img/rmt-logo.png" alt="RMT Logo" style="height: 45px; object-fit: contain;">
            </div>
            <h1>Silosense v5</h1>
            <p>Tavuk Yemi Stok Yönetim Sistemi</p>
        </div>
        
        <?php if ($hata): ?>
            <div class="alert"><?= htmlspecialchars($hata) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="kullanici_adi">Kullanıcı Adı</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" class="form-control" autocomplete="off" required>
            </div>
            <div class="form-group">
                <label for="sifre">Şifre</label>
                <input type="password" id="sifre" name="sifre" class="form-control" required>
            </div>
            <button type="submit" class="btn-submit">Sisteme Giriş Yap</button>
        </form>
    </div>
</body>
</html>
