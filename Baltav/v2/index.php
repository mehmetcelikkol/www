<?php
require_once __DIR__ . '/includes/header.php';

// Canlı Veri Sorgusu (Cihazlar Bazlı)
try {
    $db = Database::getConnection();
    
    // Tüm aktif cihazları çek, yanına son paket verisini ekle
    $sql = "SELECT 
                c.cihaz_kodu AS cihaz_kimligi, 
                c.cihaz_adi, 
                p.agirlik_degeri, 
                p.alinan_zaman, 
                l.max_agirlik,
                l.min_agirlik
            FROM cihazlar c
            LEFT JOIN (
                SELECT * FROM cihaz_paketleri WHERE id IN (
                    SELECT MAX(id) FROM cihaz_paketleri GROUP BY cihaz_kimligi
                )
            ) p ON c.cihaz_kodu = p.cihaz_kimligi
            LEFT JOIN cihaz_limitleri l ON c.cihaz_kodu = l.cihaz_kimligi
            WHERE c.aktif_mi = 1
            ORDER BY p.alinan_zaman DESC";
            
    $stmt = $db->query($sql);
    $silolar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İstatistikler
    $toplam_silo = count($silolar);
    $kritik_silo = 0;
    $toplam_agirlik = 0;
    
    foreach($silolar as $s) {
        $agirlik = isset($s['agirlik_degeri']) ? floatval($s['agirlik_degeri']) : 0;
        $toplam_agirlik += $agirlik;
        
        // Kapasite hesabı
        $max = ($s['max_agirlik'] > 0) ? floatval($s['max_agirlik']) : 0;
        $min = ($s['min_agirlik'] > 0) ? floatval($s['min_agirlik']) : 0;
        
        // Hatalı veri koruması (Max < Min ise)
        if($max > 0 && $max < $min) $max = $min * 2; 
        
        if($max > 0) {
            $yuzde = ($agirlik / $max) * 100;
            if($yuzde > 100) $yuzde = 100; // Taşmayı engelle
            if($yuzde < 0) $yuzde = 0;
            
            if($yuzde < 20) $kritik_silo++;
        }
    }

} catch (Exception $e) {
    $silolar = [];
    $toplam_silo = 0;
    $kritik_silo = 0;
    $toplam_agirlik = 0;
}
?>

<div class="container-fluid">
    
    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card bg-white p-4 rounded shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1">Aktif Cihaz</h6>
                    <h2 class="fw-bold mb-0"><?php echo $toplam_silo; ?></h2>
                </div>
                <div class="icon-box bg-light-primary text-primary rounded-circle p-3">
                    <i class="fa-solid fa-layer-group fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-white p-4 rounded shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1">Kritik Seviye</h6>
                    <h2 class="fw-bold text-danger mb-0"><?php echo $kritik_silo; ?></h2>
                </div>
                <div class="icon-box bg-light-danger text-danger rounded-circle p-3">
                    <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-white p-4 rounded shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1">Toplam Yem</h6>
                    <h2 class="fw-bold text-success mb-0"><?php echo number_format($toplam_agirlik/1000, 1, ',', '.'); ?> Ton</h2>
                </div>
                <div class="icon-box bg-light-success text-success rounded-circle p-3">
                    <i class="fa-solid fa-wheat-awn fa-2x"></i>
                </div>
            </div>
        </div>
        <!-- ... -->
    </div>

    <!-- Canlı Silo Durumları -->
    <h4 class="fw-bold mb-4 text-secondary">Canlı Silo İzleme</h4>
    
    <?php if(empty($silolar)): ?>
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i> Henüz veri gelmiş cihaz yok.
        </div>
    <?php else: ?>
    
    <div class="row g-4">
        <?php foreach($silolar as $silo): 
            // Hesaplamalar
            $agirlik = floatval($silo['agirlik_degeri']);
            $max = ($silo['max_agirlik'] > 0) ? floatval($silo['max_agirlik']) : 20000; // Varsayılan
            $min = ($silo['min_agirlik'] > 0) ? floatval($silo['min_agirlik']) : 0;

            // Hatalı veri koruması
            if($max < $min) $max = $min * 2;

            $yuzde = ($agirlik / $max) * 100;
            if($yuzde > 100) $yuzde = 100;
            if($yuzde < 0) $yuzde = 0;
            
            // Renk Belirleme
            $renk = "bg-success"; 
            if($yuzde < 50) $renk = "bg-warning";
            if($yuzde < 20) $renk = "bg-danger";
            
            // Zaman Farkı
            $zaman_veri = !empty($silo['alinan_zaman']) ? $silo['alinan_zaman'] : date('Y-m-d H:i:s');
            $son_gorulme = strtotime($zaman_veri);
            $fark_dk = round((time() - $son_gorulme) / 60);
            $zaman_metni = ($fark_dk < 2) ? "Şimdi" : (($fark_dk > 1440) ? round($fark_dk/1440)." gün önce" : $fark_dk . " dk önce");
            $online = ($fark_dk < 10);
        ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 silo-card">
                <div class="card-body text-center">
                    
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge <?php echo $online ? 'bg-success' : 'bg-secondary'; ?> rounded-pill" style="font-size: 0.6rem;">
                            <?php echo $online ? 'ONLINE' : 'OFFLINE'; ?>
                        </span>
                        <small class="text-muted" style="font-size: 0.7rem;"><?php echo $silo['cihaz_kimligi']; ?></small>
                    </div>

                    <h6 class="fw-bold mb-3 text-truncate" title="<?php echo $silo['cihaz_adi']; ?>">
                        <?php echo !empty($silo['cihaz_adi']) ? $silo['cihaz_adi'] : $silo['cihaz_kimligi']; ?>
                    </h6>
                    
                    <!-- SIVI ANİMASYONU -->
                    <div class="liquid-tank mx-auto mb-3">
                        <div class="liquid <?php echo $renk; ?>" style="height: <?php echo $yuzde; ?>%;"></div>
                        <div class="percentage"><?php echo round($yuzde); ?>%</div>
                    </div>

                    <h4 class="fw-bold mb-0"><?php echo number_format($agirlik, 0, ',', '.'); ?> <small class="fs-6 text-muted">kg</small></h4>
                    
                    <div class="d-flex justify-content-between mt-2 px-3 small text-muted">
                        <span>Min: <?php echo number_format($min, 0, ',', '.'); ?></span>
                        <span>Max: <?php echo number_format($max, 0, ',', '.'); ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-2">
                        <small class="text-muted"><i class="fa-regular fa-clock"></i> <?php echo $zaman_metni; ?></small>
                        <a href="cihaz.php?id=<?php echo $silo['cihaz_kimligi']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detay</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Harita Bölümü -->
    <div class="row g-4 mt-4 mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-3">
                <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-map-location-dot"></i> Saha Haritası</h5>
                <div id="map" style="height: 400px; border-radius: 10px;"></div>
            </div>
        </div>
    </div>

</div>

<!-- Leaflet JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Harita Başlat (Balıkesir)
    var map = L.map('map').setView([39.6484, 27.8826], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Cihaz Pinleri
    <?php foreach($silolar as $s): 
        if(!empty($s['lat']) && !empty($s['lon'])):
            $durum = ($s['agirlik_degeri'] > 0) ? "Online" : "Offline";
            $renk = ($durum == "Online") ? "green" : "grey";
    ?>
        L.marker([<?php echo $s['lat']; ?>, <?php echo $s['lon']; ?>])
         .addTo(map)
         .bindPopup("<b><?php echo $s['cihaz_adi']; ?></b><br>Durum: <?php echo $durum; ?><br><a href='cihaz.php?id=<?php echo $s['cihaz_kimligi']; ?>'>Detay</a>");
    <?php endif; endforeach; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
