<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $metaDescription ?? 'RMT Proje - Endüstriyel otomasyon çözümleri, PLC programlama, SCADA sistemleri ve teknik destek hizmetleri'; ?>">
    <meta name="keywords" content="<?php echo $metaKeywords ?? 'endüstriyel otomasyon, PLC programlama, SCADA, HMI, teknik destek, otomasyon yazılımları'; ?>">
    <title><?php echo $title ?? 'RMT Proje'; ?> - Endüstriyel Otomasyon Çözümleri</title>
    <link rel="stylesheet" href="/rmtproje/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/rmtproje/public/assets/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/rmtproje/public/assets/favicon/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/rmtproje/public/assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/rmtproje/public/assets/favicon/android-chrome-192x192.png">
</head>
<body>
    <!-- Preloader -->
    <div class="preloader">
        <div class="loader">
            <div class="gear-box">
                <i class="fas fa-cog fa-spin"></i>
                <i class="fas fa-cog fa-spin-reverse"></i>
            </div>
            <p>Yükleniyor...</p>
        </div>
    </div>
    
    <header>
        <nav>
            <div class="nav-brand">
                <a href="/rmtproje/public/">
                    <img src="/rmtproje/public/assets/images/logo.png" alt="RMT Proje Logo" class="logo">
                </a>
            </div>
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="nav-links">
                <a href="/rmtproje/public/" class="<?php echo $currentPage === 'home' ? 'active' : ''; ?>">Ana Sayfa</a>
                <a href="/rmtproje/public/hizmetler" class="<?php echo $currentPage === 'services' ? 'active' : ''; ?>">Hizmetler</a>
                <a href="/rmtproje/public/projeler" class="<?php echo $currentPage === 'projects' ? 'active' : ''; ?>">Projeler</a>
                <a href="/rmtproje/public/is-ortakligi" class="partnership-link <?php echo $currentPage === 'partnership' ? 'active' : ''; ?>">
                    <i class="fas fa-handshake"></i> İş Ortaklığı
                </a>
                <a href="/rmtproje/public/iletisim" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>">İletişim</a>
            </div>
        </nav>
    </header>

    <main>
        <?php echo $content; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> RMT Proje | Tüm Hakları Saklıdır</p>
    </footer>
    <script src="/rmtproje/public/assets/js/main.js"></script>
</body>
</html>