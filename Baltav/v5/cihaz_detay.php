<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';

if (!isset($_GET['kodu'])) {
    echo "<div class='main-content'><h2>Cihaz bulunamadı!</h2></div>";
    require_once 'sistem/footer.php';
    exit;
}

$kodu = $_GET['kodu'];

// Yetki Kontrolü
$rol = $_SESSION['kullanici_rolu'];
$cihaz_sorgu_ek = "";
if ($rol == 'entegre') {
    $cihaz_sorgu_ek = " AND kumes_id IN (SELECT id FROM kumesler WHERE entegre_id = " . (int)$_SESSION['entegre_id'] . ") ";
} elseif ($rol == 'isletmeci') {
    $cihaz_sorgu_ek = " AND kumes_id IN (SELECT id FROM kumesler WHERE isletmeci_id = " . (int)$_SESSION['isletmeci_id'] . ") ";
}

$sorgu = $db->prepare("SELECT * FROM cihazlar WHERE cihaz_kodu = ? $cihaz_sorgu_ek LIMIT 1");
$sorgu->execute([$kodu]);
$cihaz = $sorgu->fetch();

if (!$cihaz) {
    echo "<div class='main-content'><div class='alert alert-danger m-4'>Bu cihaza erişim yetkiniz yok veya cihaz bulunamadı.</div></div>";
    require_once 'sistem/footer.php';
    exit;
}

// Geçmiş Verileri Çekelim (Tarih Filtresi varsa uygula)
$tarih_sorgu = "";
$parametreler = [$kodu];
$limit = "LIMIT 120";

if (!empty($_GET['baslangic']) && !empty($_GET['bitis'])) {
    $tarih_sorgu = " AND DATE(alinan_zaman) >= ? AND DATE(alinan_zaman) <= ? ";
    $parametreler[] = $_GET['baslangic'];
    $parametreler[] = $_GET['bitis'];
    $limit = "LIMIT 500";
}

$gecmis_sorgu = $db->prepare("SELECT agirlik_degeri, alinan_zaman FROM cihaz_paketleri WHERE cihaz_kodu = ? $tarih_sorgu ORDER BY alinan_zaman ASC $limit");
$gecmis_sorgu->execute($parametreler);
$gecmis = $gecmis_sorgu->fetchAll();

$zamanlar = [];
$agirliklar = [];
$mevcut_agirlik = 0;
foreach($gecmis as $g) {
    $zamanlar[] = date('d.m H:i', strtotime($g['alinan_zaman']));
    $agirliklar[] = round($g['agirlik_degeri']);
    $mevcut_agirlik = $g['agirlik_degeri'];
}

$yuzde = ($cihaz['kapasite_kg'] > 0) ? round(($mevcut_agirlik / $cihaz['kapasite_kg']) * 100) : 0;
?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-search">
            <h5 class="m-0 page-title"><i class="fa-solid fa-wheat-awn text-warning"></i> Silo Detayı: <?= htmlspecialchars($cihaz['cihaz_adi'] ?? 'İsimsiz Cihaz') ?></h5>
        </div>
        <div class="topbar-user">
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
            <span class="user-role-badge"><?= htmlspecialchars($rol); ?></span>
        </div>
    </div>

    <!-- Özet Kartları -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="silo-card glass p-4 text-center">
                <i class="fa-solid fa-weight-scale fs-1 text-primary mb-3"></i>
                <h6 class="text-muted">Mevcut Yem</h6>
                <h3 class="fw-bold"><?= number_format($mevcut_agirlik, 0, ',', '.') ?> kg</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="silo-card glass p-4 text-center">
                <i class="fa-solid fa-battery-three-quarters fs-1 text-success mb-3"></i>
                <h6 class="text-muted">Doluluk Oranı</h6>
                <h3 class="fw-bold">%<?= $yuzde ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="silo-card glass p-4 text-center">
                <i class="fa-solid fa-stopwatch fs-1 text-danger mb-3"></i>
                <h6 class="text-muted">Tahmini Bitiş</h6>
                <h3 class="fw-bold fs-5"><?= tahmini_bitis_suresi($db, $cihaz['cihaz_kodu'], $mevcut_agirlik) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="silo-card glass p-4 text-center">
                <i class="fa-solid fa-boxes-stacked fs-1 text-info mb-3"></i>
                <h6 class="text-muted">Tam Kapasite</h6>
                <h3 class="fw-bold"><?= number_format((float)($cihaz['kapasite_kg'] ?? 0), 0, ',', '.') ?> kg</h3>
            </div>
        </div>
    </div>

    <!-- Büyük Grafik -->
    <div class="row">
        <div class="col-12">
            <div class="silo-card glass p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 fw-bold">Yem Tüketimi Analizi (<span class="text-primary">Son Durum</span>)</h4>
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="kodu" value="<?= htmlspecialchars($kodu) ?>">
                        <input type="date" name="baslangic" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['baslangic'] ?? '') ?>">
                        <input type="date" name="bitis" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['bitis'] ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Uygula</button>
                        <?php if(!empty($_GET['baslangic'])): ?>
                            <a href="?kodu=<?= htmlspecialchars($kodu) ?>" class="btn btn-sm btn-secondary">Temizle</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div id="detayGrafik" style="min-height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var options = {
        series: [{
            name: 'Mevcut Tavuk Yemi (kg)',
            data: <?= json_encode($agirliklar) ?>
        }],
        chart: {
            type: 'area',
            height: 450,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true
                }
            },
            fontFamily: 'Inter, sans-serif',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        },
        colors: ['#00E396'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.8,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 4
        },
        xaxis: {
            categories: <?= json_encode($zamanlar) ?>,
            labels: {
                style: {
                    colors: '#888',
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return value.toLocaleString('tr-TR') + " kg";
                },
                style: {
                    colors: '#888',
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function (val) {
                    return val.toLocaleString('tr-TR') + " kg";
                }
            }
        },
        markers: {
            size: 0,
            hover: {
                size: 6
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#detayGrafik"), options);
    chart.render();
});
</script>

<?php require_once 'sistem/footer.php'; ?>
