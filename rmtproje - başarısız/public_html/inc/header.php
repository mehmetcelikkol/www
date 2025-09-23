<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMT Proje</title>
    <!-- CSS yollarını düzeltelim -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <!-- Logo yolunu düzeltelim -->
                <a href="/"><img src="/assets/images/logo.png" alt="RMT Proje Logo"></a>
            </div>
            <nav>
                <div class="nav-links">
                    <a href="/" class="<?php echo $page == 'anasayfa' ? 'active' : ''; ?>">Ana Sayfa</a>
                    <a href="?sayfa=hizmetler" class="<?php echo $page == 'hizmetler' ? 'active' : ''; ?>">Hizmetler</a>
                    <a href="?sayfa=projeler" class="<?php echo $page == 'projeler' ? 'active' : ''; ?>">Projeler</a>
                    <a href="?sayfa=is-ortakligi" class="<?php echo $page == 'is-ortakligi' ? 'active' : ''; ?>">İş Ortaklığı</a>
                    <a href="?sayfa=iletisim" class="<?php echo $page == 'iletisim' ? 'active' : ''; ?>">İletişim</a>
                </div>
            </nav>
        </div>
    </header>
    <main>