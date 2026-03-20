<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Oturum Kontrolü
if(!isset($_SESSION['kullanici_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}

// Cihaz Listesi (Sidebar İçin)
require_once __DIR__ . '/../db.php';
$cihazlar_menu = [];
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT cihaz_kimligi, cihaz_adi FROM cihazlar LIMIT 10");
    $cihazlar_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
?>
<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiloSense V4 - Operations Center</title>
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Özel Stil -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Grafikler -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>

<div class="wrapper">
    <!-- SIDEBAR -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fa-solid fa-atom fa-spin me-2" style="color: #3b82f6;"></i> SILOSENSE</h3>
            <small class="text-muted" style="letter-spacing: 2px;">V4 NEXT-GEN</small>
        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php"><i class="fa-solid fa-gauge-high me-2"></i> KONTROL PANELİ</a>
            </li>

            <li>
                <a href="#gostergeSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-satellite-dish me-2"></i> SAHA CİHAZLARI
                </a>
                <ul class="collapse list-unstyled" id="gostergeSubmenu">
                    <li><a href="cihazlar.php"><i class="fa-solid fa-list-check me-2"></i> Envanter</a></li>
                    <?php foreach($cihazlar_menu as $c): ?>
                        <li>
                            <a href="cihaz.php?id=<?php echo $c['cihaz_kimligi']; ?>">
                                <i class="fa-solid fa-microchip me-2" style="font-size: 0.8rem; color: #3b82f6;"></i> 
                                <?php echo htmlspecialchars($c['cihaz_adi'] ?? $c['cihaz_kimligi']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>

            <li>
                <a href="#istatistikSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-brain me-2"></i> VERİ ANALİZİ
                </a>
                <ul class="collapse list-unstyled" id="istatistikSubmenu">
                    <li><a href="istatistik.php"><i class="fa-solid fa-chart-area me-2"></i> Trend Analizi</a></li>
                    <li><a href="raporlar.php"><i class="fa-solid fa-file-invoice me-2"></i> Tüketim Raporları</a></li>
                </ul>
            </li>

            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
            <li class="sidebar-divider">SİSTEM YÖNETİMİ</li>
            <li><a href="entegreler.php"><i class="fa-solid fa-building-shield me-2"></i> ENTEGRELER</a></li>
            <li><a href="bayiler.php"><i class="fa-solid fa-truck-ramp-box me-2"></i> BAYİ AĞI</a></li>
            <li><a href="kullanicilar.php"><i class="fa-solid fa-user-gear me-2"></i> ERİŞİM YETKİLERİ</a></li>
            <?php endif; ?>
        </ul>

        <div class="p-3" style="position: absolute; bottom: 0; width: 100%; border-top: 1px solid var(--border-color);">
            <a href="logout.php" class="btn btn-outline-danger w-100"><i class="fa-solid fa-power-off me-2"></i> SİSTEMİ KAPAT</a>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div id="content">
        <!-- Modern Top Header -->
        <div class="d-flex align-items-center mb-4 p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
            <button type="button" id="sidebarCollapse" class="btn btn-outline-primary border-0">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            
            <div class="ms-3">
                <span class="text-muted small d-block">HOŞ GELDİNİZ,</span>
                <span class="fw-bold neon-text" style="font-size: 1.1rem;"><?php echo $_SESSION['ad_soyad'] ?? 'Operatör'; ?></span>
            </div>

            <div class="ms-auto d-flex align-items-center">
                <!-- Canlı Bağlantı İndikatörü -->
                <div class="me-4 d-none d-md-flex align-items-center">
                    <span class="pulse-live me-2"></span>
                    <span class="text-success small fw-bold">CANLI BAĞLANTI AKTİF</span>
                </div>

                <!-- Saat -->
                <div class="me-4 text-end d-none d-lg-block">
                    <div id="clock" class="fw-bold" style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">00:00:00</div>
                    <div class="small text-muted"><?php echo date('d.m.Y'); ?></div>
                </div>

                <div class="dropdown">
                    <div class="avatar-box dropdown-toggle" data-bs-toggle="dropdown" style="cursor: pointer;">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['ad_soyad'] ?? 'U'); ?>&background=3b82f6&color=fff" class="rounded-circle shadow-lg" width="45">
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow border-0 p-2">
                        <li><a class="dropdown-item rounded" href="profil.php"><i class="fa-solid fa-id-card me-2"></i> Profilim</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item rounded text-danger" href="logout.php"><i class="fa-solid fa-door-open me-2"></i> Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </div>

<!-- Pulse Styles Moved to style.css -->
<script>
function updateClock() {
    const now = new Date();
    const time = now.getHours().toString().padStart(2, '0') + ':' + 
                 now.getMinutes().toString().padStart(2, '0') + ':' + 
                 now.getSeconds().toString().padStart(2, '0');
    document.getElementById('clock').textContent = time;
}
setInterval(updateClock, 1000);
updateClock();

document.getElementById('sidebarCollapse').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('active');
});
</script>
