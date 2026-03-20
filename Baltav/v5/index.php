<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';

// Kullanici yetki filtreleri
$rol = $_SESSION['kullanici_rolu'];
$cihaz_sorgu_ek = "";
$kumes_sorgu_ek = "";

if ($rol == 'entegre') {
    $cihaz_sorgu_ek = " AND c.kumes_id IN (SELECT id FROM kumesler WHERE entegre_id = " . (int)$_SESSION['entegre_id'] . ") ";
    $kumes_sorgu_ek = " WHERE entegre_id = " . (int)$_SESSION['entegre_id'];
} elseif ($rol == 'isletmeci') {
    $cihaz_sorgu_ek = " AND c.kumes_id IN (SELECT id FROM kumesler WHERE isletmeci_id = " . (int)$_SESSION['isletmeci_id'] . ") ";
    $kumes_sorgu_ek = " WHERE isletmeci_id = " . (int)$_SESSION['isletmeci_id'];
}

// Temel İstatistikleri Çekme
$cihaz_say_q = "SELECT COUNT(*) as sayi FROM cihazlar c WHERE c.aktif_mi=1 " . $cihaz_sorgu_ek;
$kumes_say_q = "SELECT COUNT(*) as sayi FROM kumesler " . $kumes_sorgu_ek;

$cihaz_sayisi_sorgu = $db->query($cihaz_say_q)->fetch();
$kumes_sayisi_sorgu = $db->query($kumes_say_q)->fetch();

// Cihaz Anlık Değerleri ve Tavuk Yemi Hesabı
$sql_cihazlar = "SELECT c.cihaz_kodu, c.cihaz_adi, c.kumes_id, k.entegre_id,
    (SELECT agirlik_degeri FROM cihaz_paketleri cp WHERE cp.cihaz_kodu = c.cihaz_kodu ORDER BY alinan_zaman DESC LIMIT 1) as mevcut_agirlik,
    c.kapasite_kg,
    k.unvan as kumes_adi,
    e.unvan as entegre_adi
    FROM cihazlar c 
    LEFT JOIN kumesler k ON c.kumes_id = k.id
    LEFT JOIN entegreler e ON k.entegre_id = e.id
    WHERE c.aktif_mi=1 " . $cihaz_sorgu_ek;

$cihazlar = $db->query($sql_cihazlar)->fetchAll();

$toplam_yem_kg = 0;
foreach($cihazlar as $c) {
    if($c['mevcut_agirlik']) $toplam_yem_kg += $c['mevcut_agirlik'];
}
?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="topbar-search">
                    <h5 class="m-0 page-title">Genel Durum (Özet)</h5>
                </div>
                <div class="topbar-user">
                    <span class="user-role-badge"><?= htmlspecialchars($rol); ?></span>
                    <div class="fw-bold text-dark">
                        Hoş Geldiniz, <?= htmlspecialchars($_SESSION['kullanici_adi']); ?>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="silo-card stat-card">
                        <div class="stat-icon">
                            <i class="fa-solid fa-wheat-awn"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Toplam Tavuk Yemi (Mevcut)</h4>
                            <h2><?= number_format($toplam_yem_kg, 0, ',', '.') ?> kg</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="silo-card stat-card">
                        <div class="stat-icon bg-success text-white">
                            <i class="fa-solid fa-tower-observation"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Sorumlu Olunan Silo</h4>
                            <h2><?= $cihaz_sayisi_sorgu['sayi'] ?> Adet</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="silo-card stat-card">
                        <div class="stat-icon bg-info text-white">
                            <i class="fa-solid fa-house-chimney-window"></i>
                        </div>
                        <div class="stat-details">
                            <h4>Erişilen Kümes Sayısı</h4>
                            <h2><?= $kumes_sayisi_sorgu['sayi'] ?> Adet</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafikler -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="silo-card">
                        <h5 class="mb-4 fw-bold">Ortalama Yem Tüketimi (Geçmiş 48 Saat)</h5>
                        <div id="anaTuketimGrafik"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="silo-card">
                        <h5 class="mb-4 fw-bold">Silo Doluluk Oranları (%)</h5>
                        <div id="dolulukGrafik"></div>
                    </div>
                </div>
            </div>

            <!-- Silo Listesi Özeti -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="silo-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="m-0 fw-bold"><i class="fa-solid fa-boxes-stacked text-primary"></i> Detaylı Yem Envanteri (Anlık)</h5>
                            <div class="btn-group">
                                <button onclick="tabloPdfIndir('envanterTablosu', 'Anlik_Envanter_Raporu')" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf"></i> PDF Aktar</button>
                                <button onclick="tabloExcelIndir('envanterTablosu', 'Anlik_Envanter_Raporu')" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Excel Aktar</button>
                            </div>
                        </div>
                        <div class="table-responsive" id="envanterTablosu">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Silo Cihaz Kodu</th>
                                        <th>Silo Adı</th>
                                        <th>Bağlı Olduğu Kümes</th>
                                        <th>Veri Erişimi (Entegre)</th>
                                        <th>Tam Kapasite</th>
                                        <th>Mevcut Yem Miktarı</th>
                                        <th>Tahmini Bitiş Süresi</th>
                                        <th>Doluluk Durumu</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($cihazlar as $c): 
                                        $yuzde = ($c['kapasite_kg'] > 0) ? round(($c['mevcut_agirlik'] / $c['kapasite_kg']) * 100) : 0;
                                        $renk = 'success';
                                        if($yuzde < 20) $renk = 'danger';
                                        else if($yuzde < 50) $renk = 'warning';
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['cihaz_kodu']) ?></strong></td>
                                        <td><?= htmlspecialchars($c['cihaz_adi'] ?? 'İsimsiz Cihaz') ?></td>
                                        <td>
                                            <?php if($c['kumes_adi']): 
                                                $kRenk = rozet_renk_getir($c['kumes_id']);
                                            ?>
                                                <span class="badge fw-medium" style="background-color: <?= $kRenk['bg'] ?>; color: <?= $kRenk['text'] ?>; border: 1px solid <?= $kRenk['border'] ?>;"><i class="fa-solid fa-house-chimney"></i> <?= htmlspecialchars($c['kumes_adi']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Bağımsız</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($c['entegre_adi']): 
                                                $eRenk = rozet_renk_getir($c['entegre_id']);
                                            ?>
                                                <span class="badge fw-medium px-2 py-1" style="background-color: <?= $eRenk['bg'] ?>; color: <?= $eRenk['text'] ?>; border: 1px solid <?= $eRenk['border'] ?>;"><i class="fa-solid fa-eye"></i> <?= htmlspecialchars($c['entegre_adi']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">Sadece Siz</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format((float)($c['kapasite_kg'] ?? 0), 0, ',', '.') ?> kg</td>
                                        <td class="fw-bold fs-5"><?= number_format((float)($c['mevcut_agirlik'] ?? 0), 0, ',', '.') ?> kg</td>
                                        <td><?= tahmini_bitis_suresi($db, $c['cihaz_kodu'], $c['mevcut_agirlik']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?= $yuzde ?>%</span>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-<?= $renk ?>" role="progressbar" style="width: <?= $yuzde ?>%;" aria-valuenow="<?= $yuzde ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="cihaz_detay.php?kodu=<?= htmlspecialchars($c['cihaz_kodu']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-chart-line"></i> İncele
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(count($cihazlar) === 0): ?>
                                    <tr><td colspan="8" class="text-center text-muted">Bağlı cihaz bulunamadı.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

<script>
// ApexCharts Render İşlemleri
document.addEventListener("DOMContentLoaded", function() {
    
    // Donut Grafiği (Dinamil PHP Verisi)
    var siloIsimleri = [<?php foreach($cihazlar as $c) echo "'" . htmlspecialchars($c['cihaz_adi']) . "',"; ?>];
    var siloDoluluklar = [<?php foreach($cihazlar as $c) { $y = ($c['kapasite_kg'] > 0) ? round(($c['mevcut_agirlik'] / $c['kapasite_kg']) * 100) : 0; echo $y . ","; } ?>];

    if (siloDoluluklar.length > 0) {
        var optionsDoluluk = {
            series: siloDoluluklar,
            labels: siloIsimleri,
            chart: {
                type: 'donut',
                height: 350
            },
            colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'bottom'
            }
        };
        var chartDoluluk = new ApexCharts(document.querySelector("#dolulukGrafik"), optionsDoluluk);
        chartDoluluk.render();
    } else {
        document.querySelector("#dolulukGrafik").innerHTML = "<p class='text-muted text-center mt-4'>Veri yok</p>";
    }

    // Tüketim Trendi Çizgi Grafiği
    // İleride API ile saatlik veya günlük gerçek okumalar eklenebilir.
    // Simülasyon verisi (1 haftalık düşüş eğilimi)
    var optionsTrend = {
        series: [{
            name: 'Toplam Tüketilen Yem',
            data: [1500, 1400, 1350, 1200, 1100, 900, 850]
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: {
                show: false
            }
        },
        colors: ['#fca311'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: ['-48s', '-40s', '-32s', '-24s', '-16s', '-8s', 'Şimdi'],
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " kg"
                }
            }
        }
    };

    var chartTrend = new ApexCharts(document.querySelector("#anaTuketimGrafik"), optionsTrend);
    chartTrend.render();
});
</script>

<?php require_once 'sistem/footer.php'; ?>
