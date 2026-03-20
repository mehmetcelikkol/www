<?php
// kumes_dashboard.php - V4 Showtime Edition

require_once __DIR__ . '/../db.php';

$kullanici_rol = $_SESSION['rol'] ?? 'kumes';
$kullanici_id = $_SESSION['kullanici_id'] ?? 0;

if ($kullanici_rol !== 'kumes') {
    echo '<div class="alert alert-danger">Yetkisiz erişim.</div>';
    exit;
}

$silo = [];
$entegre_adi = 'Bilinmiyor';

try {
    $db = Database::getConnection();
    $cihaz_kodu = 'ilk prototip'; 

    $sql = "SELECT 
                c.id AS cihaz_id,
                c.cihaz_kodu AS cihaz_kimligi, 
                c.cihaz_adi, 
                p.agirlik_degeri, 
                p.alinan_zaman, 
                l.max_agirlik,
                l.min_agirlik,
                c.konum, 
                e.unvan AS entegre_adi
            FROM cihazlar c
            LEFT JOIN (
                SELECT *, ROW_NUMBER() OVER(PARTITION BY cihaz_kimligi ORDER BY alinan_zaman DESC) as rn
                FROM cihaz_paketleri
            ) p ON c.cihaz_kodu = p.cihaz_kimligi AND p.rn = 1
            LEFT JOIN cihaz_limitleri l ON c.cihaz_kodu = l.cihaz_kimligi
            LEFT JOIN entegreler e ON c.aktif_entegre_id = e.id
            WHERE c.aktif_mi = 1 AND c.cihaz_kodu = :cihaz_kodu";
            
    $stmt = $db->prepare($sql);
    $stmt->execute(['cihaz_kodu' => $cihaz_kodu]);
    $silo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($silo) {
        $entegre_adi = $silo['entegre_adi'] ?? 'Bilinmiyor';
        $agirlik = floatval($silo['agirlik_degeri'] ?? 0);
        $max = floatval($silo['max_agirlik'] ?? 20000);
        
        $yuzde = ($max > 0) ? ($agirlik / $max) * 100 : 0;
        $yuzde = max(0, min(100, $yuzde));
        $silo['yuzde'] = $yuzde;

        $fark_dk = round((time() - strtotime($silo['alinan_zaman'] ?? 'now')) / 60);
        $silo['online'] = ($fark_dk < 10);
        
        $silo['renk_class'] = "liquid-success"; 
        if($yuzde < 50) $silo['renk_class'] = "liquid-warning";
        if($yuzde < 20) $silo['renk_class'] = "liquid-danger";
    }

} catch (Exception $e) {}

?>

<div class="container-fluid animate__animated animate__fadeIn">
    
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="neon-text mb-2">CANLI VERİ AKIŞI</h2>
            <p class="text-muted">Bağlı Silo ve Operasyonel Veri Analizi</p>
        </div>
    </div>

    <?php if(empty($silo)): ?>
        <div class="card p-4 text-center">
            <i class="fa-solid fa-satellite-dish fa-3x mb-3 text-warning"></i>
            <h4>Sinyal Bekleniyor...</h4>
            <p class="text-muted">Cihazınızdan henüz veri paketi alınmadı.</p>
        </div>
    <?php else: ?>

    <div class="row g-4">
        <!-- SOL PANEL: SİLO GÖRSELLEŞTİRME -->
        <div class="col-xl-4 col-lg-5">
            <div class="card h-100 text-center py-4">
                <div class="px-3 d-flex justify-content-between">
                    <span class="badge <?php echo $silo['online'] ? 'bg-success' : 'bg-danger'; ?> shadow-sm">
                        <?php echo $silo['online'] ? 'ONLINE' : 'OFFLINE'; ?>
                    </span>
                    <span class="text-muted small"><?php echo $silo['cihaz_kimligi']; ?></span>
                </div>
                
                <h4 class="mt-3 fw-bold"><?php echo htmlspecialchars($silo['cihaz_adi']); ?></h4>
                
                <!-- INDUSTRIAL SILO ANIMATION -->
                <div class="silo-container">
                    <div class="silo-liquid <?php echo $silo['renk_class']; ?>" style="height: <?php echo round($silo['yuzde']); ?>%;">
                        <div class="silo-wave"></div>
                    </div>
                    <div class="silo-label"><?php echo round($silo['yuzde']); ?>%</div>
                </div>

                <div class="mt-3">
                    <div class="stat-value neon-text text-primary">
                        <?php echo number_format($agirlik, 0, ',', '.'); ?>
                        <span class="stat-unit">KG</span>
                    </div>
                    <div class="progress mx-4 bg-dark" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $silo['yuzde']; ?>%"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-around mt-4">
                    <div>
                        <div class="small text-muted">MİN</div>
                        <div class="fw-bold"><?php echo number_format($silo['min_agirlik'] ?? 0, 0, ',', '.'); ?></div>
                    </div>
                    <div style="border-left: 1px solid var(--border-color);"></div>
                    <div>
                        <div class="small text-muted">MAX</div>
                        <div class="fw-bold"><?php echo number_format($silo['max_agirlik'] ?? 20000, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ PANEL: DETAYLAR VE AKSİYONLAR -->
        <div class="col-xl-8 col-lg-7">
            <div class="row g-4">
                <!-- Entegre Kartı -->
                <div class="col-md-6">
                    <div class="card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fa-solid fa-handshake fa-2x"></i>
                            </div>
                            <div>
                                <div class="small text-muted">İŞ ORTAĞI / ENTEGRE</div>
                                <h4 class="mb-0 fw-bold"><?php echo htmlspecialchars($entegre_adi); ?></h4>
                            </div>
                        </div>
                        <button class="btn btn-outline-warning w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#entegreTalepModal">
                            <i class="fa-solid fa-shuffle me-2"></i> DEĞİŞİKLİK TALEBİ
                        </button>
                    </div>
                </div>

                <!-- Son Güncelleme Kartı -->
                <div class="col-md-6">
                    <div class="card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded bg-success bg-opacity-10 text-success me-3">
                                <i class="fa-solid fa-clock-rotate-left fa-2x"></i>
                            </div>
                            <div>
                                <div class="small text-muted">SON VERİ ALIMI</div>
                                <h4 class="mb-0 fw-bold"><?php echo date('H:i:s', strtotime($silo['alinan_zaman'])); ?></h4>
                                <div class="small text-muted"><?php echo date('d.m.Y'); ?></div>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Bağlantı Kalitesi</span>
                                <span class="text-success">Mükemmel</span>
                            </div>
                            <div class="progress bg-dark" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: 95%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik Alanı (Quick Stats) -->
                <div class="col-12">
                    <div class="card p-3">
                        <div id="quick-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Dashboard Grafik (Sunum için şov amaçlı örnek veri)
var options = {
    series: [{
        name: 'Silo Seviyesi (KG)',
        data: [15200, 14800, 14200, 14100, 13900, 13700, 13500]
    }],
    chart: {
        height: 250,
        type: 'area',
        toolbar: { show: false },
        background: 'transparent'
    },
    theme: { mode: 'dark' },
    stroke: { curve: 'smooth', colors: ['#3b82f6'] },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0 }
    },
    dataLabels: { enabled: false },
    grid: { borderColor: '#1e293b' },
    xaxis: {
        categories: ['04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00'],
        axisBorder: { show: false },
        axisTicks: { show: false }
    }
};

var chart = new ApexCharts(document.querySelector("#quick-chart"), options);
chart.render();
</script>
