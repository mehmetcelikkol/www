<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_temizle($sayfa_basligi ?? 'SiloSense') ?> — SiloSense IoT</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Özel Stil -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- Sidebar + Ana İçerik Sarmalayıcı -->
<div class="ss-wrapper">

    <!-- ===== SIDEBAR ===== -->
    <nav class="ss-sidebar" id="sidebar">
        <!-- Logo -->
        <div class="ss-logo">
            <div class="ss-logo-icon">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="8" y="4" width="24" height="32" rx="4" fill="url(#siloGrad)"/>
                    <rect x="12" y="10" width="16" height="20" rx="2" fill="rgba(255,255,255,0.15)"/>
                    <rect class="silo-fill-anim" x="12" y="22" width="16" height="8" rx="1" fill="rgba(255,255,255,0.4)"/>
                    <defs>
                        <linearGradient id="siloGrad" x1="8" y1="4" x2="32" y2="36" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#6366f1"/>
                            <stop offset="1" stop-color="#8b5cf6"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div>
                <span class="ss-logo-text">SiloSense</span>
                <span class="ss-logo-sub">IoT Platform</span>
            </div>
        </div>

        <!-- Kullanıcı Bilgisi -->
        <div class="ss-user-badge">
            <div class="ss-user-avatar">
                <?= strtoupper(substr($_SESSION['ad_soyad'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="ss-user-info">
                <span class="ss-user-name"><?= html_temizle($_SESSION['ad_soyad'] ?? '') ?></span>
                <span class="ss-user-rol ss-rol-<?= html_temizle($_SESSION['rol'] ?? '') ?>">
                    <?= strtoupper($_SESSION['rol'] ?? '') ?>
                </span>
            </div>
        </div>

        <!-- Menü -->
        <ul class="ss-nav">
            <?php if (is_admin()): ?>
                <li class="ss-nav-section">YÖNETİM</li>
                <li><a href="/pages/admin/dashboard.php" class="ss-nav-link <?= aktif_menu('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
                </a></li>
                <li><a href="/pages/admin/cihazlar.php" class="ss-nav-link <?= aktif_menu('cihazlar') ?>">
                    <i class="bi bi-cpu-fill"></i><span>Cihazlar</span>
                </a></li>
                <li><a href="/pages/admin/cihaz_ata.php" class="ss-nav-link <?= aktif_menu('cihaz_ata') ?>">
                    <i class="bi bi-diagram-3-fill"></i><span>Cihaz Atama</span>
                </a></li>
                <li class="ss-nav-section">FİRMALAR</li>
                <li><a href="/pages/admin/bayiler.php" class="ss-nav-link <?= aktif_menu('bayiler') ?>">
                    <i class="bi bi-shop"></i><span>Bayiler</span>
                </a></li>
                <li><a href="/pages/admin/entegreler.php" class="ss-nav-link <?= aktif_menu('entegreler') ?>">
                    <i class="bi bi-building"></i><span>Entegre Firmalar</span>
                </a></li>
                <li><a href="/pages/admin/kullanicilar.php" class="ss-nav-link <?= aktif_menu('kullanicilar') ?>">
                    <i class="bi bi-people-fill"></i><span>Kullanıcılar</span>
                </a></li>
                <li class="ss-nav-section">SİSTEM</li>
                <li><a href="/pages/admin/ariza_log.php" class="ss-nav-link <?= aktif_menu('ariza_log') ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i><span>Arıza Logları</span>
                </a></li>
            <?php elseif (is_bayi()): ?>
                <li class="ss-nav-section">PANELİM</li>
                <li><a href="/pages/bayi/dashboard.php" class="ss-nav-link <?= aktif_menu('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
                </a></li>
                <li><a href="/pages/bayi/silolarim.php" class="ss-nav-link <?= aktif_menu('silolarim') ?>">
                    <i class="bi bi-archive-fill"></i><span>Silolarım</span>
                </a></li>
                <li><a href="/pages/bayi/raporlar.php" class="ss-nav-link <?= aktif_menu('raporlar') ?>">
                    <i class="bi bi-bar-chart-fill"></i><span>Raporlar</span>
                </a></li>
            <?php elseif (is_entegre()): ?>
                <li class="ss-nav-section">PANELİM</li>
                <li><a href="/pages/entegre/dashboard.php" class="ss-nav-link <?= aktif_menu('dashboard') ?>">
                    <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
                </a></li>
                <li><a href="/pages/entegre/cihazlar.php" class="ss-nav-link <?= aktif_menu('cihazlar') ?>">
                    <i class="bi bi-cpu-fill"></i><span>Bağlı Cihazlar</span>
                </a></li>
                <li><a href="/pages/entegre/bayiler.php" class="ss-nav-link <?= aktif_menu('bayiler') ?>">
                    <i class="bi bi-shop"></i><span>Bayilerim</span>
                </a></li>
            <?php endif; ?>
        </ul>

        <!-- Çıkış -->
        <div class="ss-sidebar-footer">
            <a href="/cikis.php" class="ss-cikis-btn">
                <i class="bi bi-box-arrow-left"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </nav>
    <!-- /SIDEBAR -->

    <!-- ===== ANA İÇERİK ===== -->
    <main class="ss-main">
        <!-- Topbar -->
        <header class="ss-topbar">
            <button class="ss-menu-toggle" id="menuToggle">
                <i class="bi bi-list"></i>
            </button>
            <div class="ss-topbar-title">
                <?= html_temizle($sayfa_basligi ?? 'Dashboard') ?>
            </div>
            <div class="ss-topbar-right">
                <div class="ss-clock" id="ssClock"></div>
                <!-- Flash Mesajları Topbar'da göster -->
            </div>
        </header>

        <!-- Flash Mesajlar -->
        <?php foreach (flash_mesajlari_al() as $flash): ?>
        <div class="alert alert-<?= html_temizle($flash['tur']) ?> alert-dismissible fade show mx-4 mt-3" role="alert">
            <?= html_temizle($flash['mesaj']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>

        <!-- İçerik Başlangıcı -->
        <div class="ss-content">
