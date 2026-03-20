<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Zaten giriş yapmışsa yönlendir
if (giris_yapildi_mi()) {
    $hedef = match(oturum_rol()) {
        'admin'   => '/pages/admin/dashboard.php',
        'bayi'    => '/pages/bayi/dashboard.php',
        'entegre' => '/pages/entegre/dashboard.php',
        default   => '/index.php'
    };
    header("Location: $hedef");
    exit;
}

$hata_mesaji = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Kontrolü
    if (!csrf_dogrula($_POST['csrf_token'] ?? '')) {
        $hata_mesaji = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    } else {
        $eposta = trim($_POST['eposta'] ?? '');
        $sifre  = $_POST['sifre'] ?? '';

        if (empty($eposta) || empty($sifre)) {
            $hata_mesaji = 'E-posta ve şifre alanları zorunludur.';
        } else {
            // Kullanıcıyı veritabanında ara (Prepared Statement)
            $stmt = $pdo->prepare("
                SELECT id, ad_soyad, eposta, sifre_hash, rol, bagli_id
                FROM kullanicilar
                WHERE eposta = ?
                LIMIT 1
            ");
            $stmt->execute([$eposta]);
            $kullanici = $stmt->fetch();

            if ($kullanici && password_verify($sifre, $kullanici['sifre_hash'])) {
                // Oturum aç
                session_regenerate_id(true);
                $_SESSION['kullanici_id'] = $kullanici['id'];
                $_SESSION['ad_soyad']     = $kullanici['ad_soyad'];
                $_SESSION['eposta']       = $kullanici['eposta'];
                $_SESSION['rol']          = $kullanici['rol'];
                $_SESSION['bagli_id']     = $kullanici['bagli_id'];

                // Role göre yönlendir
                $hedef = match($kullanici['rol']) {
                    'admin'   => '/pages/admin/dashboard.php',
                    'bayi'    => '/pages/bayi/dashboard.php',
                    'entegre' => '/pages/entegre/dashboard.php',
                    default   => '/index.php'
                };
                header("Location: $hedef");
                exit;
            } else {
                // Brute-force'u yavaşlatmak için kısa bekleme
                sleep(1);
                $hata_mesaji = 'E-posta veya şifre hatalı.';
            }
        }
    }
}

$csrf = csrf_token_olustur();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — SiloSense IoT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="ss-login-sayfa">
    <div class="ss-login-kart">

        <!-- Logo -->
        <div class="ss-login-logo">
            <div class="ss-logo-icon">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="8" y="4" width="24" height="32" rx="4" fill="url(#loginSiloGrad)"/>
                    <rect x="12" y="10" width="16" height="20" rx="2" fill="rgba(255,255,255,0.15)"/>
                    <rect x="12" y="22" width="16" height="8" rx="1" fill="rgba(255,255,255,0.35)"/>
                    <defs>
                        <linearGradient id="loginSiloGrad" x1="8" y1="4" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#6366f1"/>
                            <stop offset="1" stop-color="#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="ss-login-logo-metin">SiloSense</div>
            <div style="font-size:12px; color:var(--renk-metin-soluk); margin-top:4px; letter-spacing:0.1em;">
                IOT PLATFORM
            </div>
        </div>

        <!-- Hata Mesajı -->
        <?php if ($hata_mesaji): ?>
        <div class="alert mb-4" style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25); color:#fca5a5; border-radius:10px; font-size:13px;">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?= html_temizle($hata_mesaji) ?>
        </div>
        <?php endif; ?>

        <!-- Giriş Formu -->
        <form method="POST" action="/index.php">
            <input type="hidden" name="csrf_token" value="<?= html_temizle($csrf) ?>">

            <div class="ss-form-grup">
                <label class="ss-label">E-Posta Adresi</label>
                <div class="position-relative">
                    <input
                        type="email"
                        name="eposta"
                        class="ss-input"
                        placeholder="ornek@firma.com"
                        value="<?= html_temizle($_POST['eposta'] ?? '') ?>"
                        required
                        autocomplete="email"
                        style="padding-left: 40px;"
                    >
                    <i class="bi bi-envelope" style="position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--renk-metin-soluk);"></i>
                </div>
            </div>

            <div class="ss-form-grup">
                <label class="ss-label">Şifre</label>
                <div class="position-relative">
                    <input
                        type="password"
                        name="sifre"
                        id="sifreInput"
                        class="ss-input"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                        style="padding-left: 40px; padding-right: 44px;"
                    >
                    <i class="bi bi-lock" style="position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--renk-metin-soluk);"></i>
                    <button type="button" id="sifreGoster"
                        style="position:absolute; right:13px; top:50%; transform:translateY(-50%);
                               background:none; border:none; color:var(--renk-metin-soluk); cursor:pointer; padding:4px;">
                        <i class="bi bi-eye" id="sifreGozIkon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="ss-btn ss-btn-birincil w-100 justify-content-center mt-2" style="padding:12px;">
                <i class="bi bi-box-arrow-in-right"></i>
                Giriş Yap
            </button>
        </form>

        <!-- Alt Bilgi -->
        <div class="ss-login-alt">
            <i class="bi bi-shield-check me-1"></i>
            Güvenli bağlantı ile korunmaktadır
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sifreGoster').addEventListener('click', function() {
    const input = document.getElementById('sifreInput');
    const ikon  = document.getElementById('sifreGozIkon');
    if (input.type === 'password') {
        input.type = 'text';
        ikon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        ikon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
