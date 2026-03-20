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
    // SaaS Yetki Kontrolü (Buraya eklenecek)
    $stmt = $db->query("SELECT cihaz_kimligi, cihaz_adi FROM cihazlar LIMIT 10"); // Örnek limit
    $cihazlar_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // DB hatası olursa menü boş kalır, sorun yok
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiloSense V2</title>
    
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
            <h3><i class="fa-solid fa-layer-group"></i> SiloSense</h3>
        </div>

        <ul class="list-unstyled components">
            <!-- Dashboard -->
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
            </li>

            <!-- Gösterge (Dropdown) -->
            <li>
                <a href="#gostergeSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-eye me-2"></i> Gösterge
                </a>
                <ul class="collapse list-unstyled" id="gostergeSubmenu">
                    <li><a href="cihazlar.php">📍 Tüm Cihazlar</a></li>
                    <?php foreach($cihazlar_menu as $c): ?>
                        <li>
                            <a href="cihaz.php?id=<?php echo $c['cihaz_kimligi']; ?>">
                                🔹 <?php echo htmlspecialchars($c['cihaz_adi'] ?? $c['cihaz_kimligi']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>

            <!-- İstatistikler -->
            <li>
                <a href="#istatistikSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fa-solid fa-chart-line me-2"></i> İstatistikler
                </a>
                <ul class="collapse list-unstyled" id="istatistikSubmenu">
                    <li><a href="istatistik.php">📈 Genel Analiz</a></li>
                    <li><a href="raporlar.php">📉 Tüketim Raporu</a></li>
                </ul>
            </li>

            <!-- Admin Menüsü (Sadece Admin Görür) -->
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
            <li class="sidebar-divider">YÖNETİM</li>
            <li><a href="entegreler.php"><i class="fa-solid fa-building me-2"></i> Entegreler</a></li>
            <li><a href="bayiler.php"><i class="fa-solid fa-store me-2"></i> Bayiler</a></li>
            <li><a href="kullanicilar.php"><i class="fa-solid fa-users me-2"></i> Kullanıcılar</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-danger w-100"><i class="fa-solid fa-power-off"></i> Çıkış</a>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm rounded">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    
                    <!-- Bildirimler -->
                    <div class="dropdown me-3">
                        <button class="btn btn-light rounded-circle shadow-sm position-relative" id="notifDropdown" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-bell text-primary"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifCount" style="display:none;">
                                0
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="notifList" style="width: 300px;">
                            <li><h6 class="dropdown-header">Bildirimler</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="text-center small text-muted py-2">Bildirim yok.</li>
                        </ul>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="btn btn-light rounded-circle me-3 shadow-sm">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <span class="me-3 fw-bold text-secondary"><?php echo $_SESSION['ad_soyad'] ?? 'Misafir'; ?></span>
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </div>
        </nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('darkModeToggle');
    const icon = toggle.querySelector('i');
    
    // Kayıtlı Temayı Yükle
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateIcon(savedTheme);

    toggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon(newTheme);
    });

    function updateIcon(theme) {
        if(theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
});
</script>
