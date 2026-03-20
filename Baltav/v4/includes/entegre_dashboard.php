<?php
// entegre_dashboard.php - V4 Showtime Edition

require_once __DIR__ . '/../db.php';

$kullanici_rol = $_SESSION['rol'] ?? 'kumes';
$bagli_id = $_SESSION['bagli_id'] ?? 0;

if ($kullanici_rol !== 'entegre' || $bagli_id === 0) {
    echo '<div class="alert alert-danger">Yetkisiz erişim.</div>';
    exit;
}

try {
    $db = Database::getConnection();
    $sql = "SELECT 
                c.id AS cihaz_id,
                c.cihaz_kodu AS cihaz_kimligi, 
                c.cihaz_adi, 
                p.agirlik_degeri, 
                p.alinan_zaman, 
                l.max_agirlik,
                l.min_agirlik,
                c.konum, 
                c.lat, 
                c.lon  
            FROM cihazlar c
            LEFT JOIN (
                SELECT *, ROW_NUMBER() OVER(PARTITION BY cihaz_kimligi ORDER BY alinan_zaman DESC) as rn
                FROM cihaz_paketleri
            ) p ON c.cihaz_kodu = p.cihaz_kimligi AND p.rn = 1
            LEFT JOIN cihaz_limitleri l ON c.cihaz_kodu = l.cihaz_kimligi
            WHERE c.aktif_mi = 1 AND c.aktif_entegre_id = :entegre_id
            ORDER BY p.alinan_zaman DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute(['entegre_id' => $bagli_id]);
    $silolar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $toplam_silo = count($silolar);
    $kritik_silo = 0;
    $toplam_agirlik = 0;
    
    foreach($silolar as &$s) {
        $agirlik = isset($s['agirlik_degeri']) ? floatval($s['agirlik_degeri']) : 0;
        $toplam_agirlik += $agirlik;
        
        $max = (isset($s['max_agirlik']) && $s['max_agirlik'] > 0) ? floatval($s['max_agirlik']) : 20000;
        $yuzde = ($max > 0) ? ($agirlik / $max) * 100 : 0;
        $s['yuzde'] = max(0, min(100, $yuzde));
        
        if($s['yuzde'] < 20) $kritik_silo++;

        $son_gorulme = strtotime($s['alinan_zaman'] ?? 'now');
        $fark_dk = round((time() - $son_gorulme) / 60);
        $s['online'] = ($fark_dk < 10);
        $s['zaman_metni'] = ($fark_dk < 2) ? "Şimdi" : (($fark_dk > 1440) ? round($fark_dk/1440)." gün önce" : $fark_dk . " dk önce");

        $s['renk_class'] = "liquid-success"; 
        if($s['yuzde'] < 50) $s['renk_class'] = "liquid-warning";
        if($s['yuzde'] < 20) $s['renk_class'] = "liquid-danger";
    }

} catch (Exception $e) {
    $silolar = [];
}
?>

<div class="container-fluid animate__animated animate__fadeIn">
    
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="neon-text mb-2">PARTNER PORTALI</h2>
            <p class="text-muted">Bağlı Kümeslerin Operasyonel Durumu</p>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-5">
        <div class="col-xl-4 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small mb-2">BAĞLI SİLOLAR</h6>
                        <div class="stat-value text-primary"><?php echo $toplam_silo; ?></div>
                    </div>
                    <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-layer-group fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small mb-2">TOPLAM YEM REZERVİ</h6>
                        <div class="stat-value text-success"><?php echo number_format($toplam_agirlik/1000, 1, ',', '.'); ?> <span class="fs-4">Ton</span></div>
                    </div>
                    <div class="p-3 rounded bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-boxes-stacked fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="card p-4 h-100 border-danger border-opacity-25">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small mb-2">ACİL DOLUM GEREKEN</h6>
                        <div class="stat-value text-danger"><?php echo $kritik_silo; ?></div>
                    </div>
                    <div class="p-3 rounded bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-truck-field fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Canlı Silo İzleme -->
    <h4 class="neon-text mb-4" style="font-size: 1.2rem;"><i class="fa-solid fa-eye me-2"></i> SAHA GÖZLEM</h4>
    
    <?php if(empty($silolar)): ?>
        <div class="card p-5 text-center bg-dark">
            <i class="fa-solid fa-triangle-exclamation fa-3x mb-3 text-warning"></i>
            <h5 class="text-muted">Bağlı aktif bir sistem bulunamadı.</h5>
        </div>
    <?php else: ?>
    
    <div class="row g-4">
        <?php foreach($silolar as $silo): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card h-100 text-center py-4">
                <div class="px-3 d-flex justify-content-between align-items-center mb-3">
                    <span class="badge <?php echo $silo['online'] ? 'bg-success' : 'bg-danger'; ?> shadow-sm">
                        <?php echo $silo['online'] ? 'ONLINE' : 'OFFLINE'; ?>
                    </span>
                    <small class="text-muted"><?php echo $silo['cihaz_kimligi']; ?></small>
                </div>

                <h6 class="fw-bold px-2 text-truncate"><?php echo !empty($silo['cihaz_adi']) ? $silo['cihaz_adi'] : $silo['cihaz_kimligi']; ?></h6>
                
                <div class="silo-container" style="transform: scale(0.85); margin: 5px auto;">
                    <div class="silo-liquid <?php echo $silo['renk_class']; ?>" style="height: <?php echo round($silo['yuzde']); ?>%;">
                        <div class="silo-wave"></div>
                    </div>
                    <div class="silo-label"><?php echo round($silo['yuzde']); ?>%</div>
                </div>

                <div class="px-3">
                    <div class="fw-bold" style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem;">
                        <?php echo number_format($silo['agirlik_degeri'], 0, ',', '.'); ?> <span class="small text-muted">kg</span>
                    </div>
                    <div class="small text-muted mt-1"><?php echo $silo['zaman_metni']; ?></div>
                </div>

                <div class="mt-3 border-top border-opacity-10 pt-3 px-3 d-grid">
                    <a href="cihaz.php?id=<?php echo $silo['cihaz_kimligi']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">SİSTEM ANALİZİ</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Harita Bölümü -->
    <div class="row g-4 mt-4 mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-0 overflow-hidden">
                <div class="card-header border-0">
                    <h5 class="m-0 neon-text" style="font-size: 1rem;"><i class="fa-solid fa-map-location-dot me-2"></i> SAHA HARİTASI</h5>
                </div>
                <div id="map" style="height: 400px; background: #020617;"></div>
            </div>
        </div>
    </div>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map = L.map('map', { zoomControl: false }).setView([39.6484, 27.8826], 10);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OSM & CARTO'
    }).addTo(map);

    <?php foreach($silolar as $s):
        $lat = !empty($s['lat']) ? $s['lat'] : 39.6484 + (mt_rand(-50, 50) / 1000);
        $lon = !empty($s['lon']) ? $s['lon'] : 27.8826 + (mt_rand(-50, 50) / 1000);
        $durum_color = ($s['online']) ? "#22c55e" : "#ef4444";
    ?>
        L.circleMarker([<?php echo $lat; ?>, <?php echo $lon; ?>], {
            radius: 8, fillColor: "<?php echo $durum_color; ?>", color: "#fff", weight: 2, fillOpacity: 0.8
        }).addTo(map).bindPopup("<b><?php echo $s['cihaz_adi']; ?></b><br>Kapasite: %<?php echo round($s['yuzde']); ?>");
    <?php endforeach; ?>
</script>
