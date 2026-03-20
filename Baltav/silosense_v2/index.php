<?php
require_once 'db.php';

// Oturum Kontrolü (Demo için kapalı)
// if(!isset($_SESSION['kullanici_id'])) { header("Location: login.php"); exit; }

// SAHTE VERİLER (Gerçek DB bağlantısı kurulana kadar)
$silolar = [
    ['id'=>1, 'ad'=>'Silo-1 (Banvit)', 'seviye'=>85, 'durum'=>'stabil'],
    ['id'=>2, 'ad'=>'Silo-2 (Kuluçka)', 'seviye'=>45, 'durum'=>'azaliyor'],
    ['id'=>3, 'ad'=>'Silo-3 (Yem)', 'seviye'=>15, 'durum'=>'kritik']
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SiloSense Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar p-3 d-flex flex-column">
    <h3 class="mb-4 fw-bold text-primary"><i class="fa-solid fa-layer-group"></i> SiloSense</h3>
    
    <a href="#" class="nav-link active"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
    <a href="#" class="nav-link"><i class="fa-solid fa-building me-2"></i> Entegreler</a>
    <a href="#" class="nav-link"><i class="fa-solid fa-store me-2"></i> Bayiler</a>
    <a href="#" class="nav-link"><i class="fa-solid fa-chart-line me-2"></i> Raporlar</a>
    
    <div class="mt-auto">
        <a href="login.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Çıkış</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Üst Bilgi -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Hoşgeldin, Admin 👋</h2>
            <p class="text-muted">Sistem Durumu: <span class="badge bg-success">Online</span></p>
        </div>
        <div>
            <button class="btn btn-white shadow-sm"><i class="fa-solid fa-bell text-primary"></i></button>
            <button class="btn btn-white shadow-sm"><i class="fa-solid fa-gear text-secondary"></i></button>
        </div>
    </div>

    <!-- Özet Kartlar -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Toplam Silo</p>
                        <h3 class="fw-bold">124</h3>
                    </div>
                    <div class="fs-1 text-primary opacity-25"><i class="fa-solid fa-database"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Kritik Seviye</p>
                        <h3 class="fw-bold text-danger">3</h3>
                    </div>
                    <div class="fs-1 text-danger opacity-25"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
            </div>
        </div>
        <!-- Diğer kartlar... -->
    </div>

    <!-- Silo Görselleştirme -->
    <h4 class="fw-bold mb-4">Canlı Silo Durumları</h4>
    <div class="row g-4">
        <?php foreach($silolar as $silo): 
            $renk_sinifi = "level-high";
            if($silo['seviye'] < 50) $renk_sinifi = "level-med";
            if($silo['seviye'] < 20) $renk_sinifi = "level-low";
        ?>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100 text-center p-3">
                <h6 class="fw-bold mb-3"><?php echo $silo['ad']; ?></h6>
                
                <!-- Animasyonlu Silo -->
                <div class="silo-container mb-3">
                    <div class="silo-liquid <?php echo $renk_sinifi; ?>" style="height: <?php echo $silo['seviye']; ?>%;"></div>
                </div>
                
                <h3 class="fw-bold mb-0">%<?php echo $silo['seviye']; ?></h3>
                <small class="text-muted"><?php echo $silo['seviye'] * 100; ?> kg</small>
                
                <?php if($silo['durum'] == 'azaliyor'): ?>
                    <span class="badge bg-light text-dark mt-2"><i class="fa-solid fa-arrow-trend-down text-danger"></i> Tüketim Var</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>
