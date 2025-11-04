<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

include '../config/database.php';

// Son 30 günün istatistiklerini al
$query = "SELECT 
    DATE(tarih) as gun,
    COUNT(*) as ziyaret_sayisi,
    COUNT(DISTINCT ip_adresi) as benzersiz_ziyaretci
FROM site_istatistik 
WHERE tarih >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(tarih)
ORDER BY tarih DESC";

$istatistikler = $pdo->query($query)->fetchAll();

// En çok ziyaret edilen sayfalar
$sayfa_query = "SELECT sayfa, COUNT(*) as ziyaret_sayisi 
FROM site_istatistik 
WHERE tarih >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY sayfa 
ORDER BY ziyaret_sayisi DESC 
LIMIT 10";

$sayfalar = $pdo->query($sayfa_query)->fetchAll();

// Navbar tıklama istatistikleri
$navbar_query = "SELECT navbar_item, COUNT(*) as tiklanma_sayisi 
FROM navbar_tiklama 
WHERE tarih >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY navbar_item 
ORDER BY tiklanma_sayisi DESC";

$navbar_stats = $pdo->query($navbar_query)->fetchAll();

// Aylık istatistikler
$aylik_query = "SELECT 
    MONTH(tarih) as ay,
    YEAR(tarih) as yil,
    COUNT(*) as ziyaret_sayisi,
    COUNT(DISTINCT ip_adresi) as benzersiz_ziyaretci
FROM site_istatistik 
GROUP BY YEAR(tarih), MONTH(tarih)
ORDER BY yil DESC, ay DESC
LIMIT 12";

$aylik_stats = $pdo->query($aylik_query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site İstatistikleri - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">RMT Admin</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Hoşgeldin, <?php echo $_SESSION['admin_kullanici']; ?></span>
                <a class="nav-link" href="logout.php">Çıkış</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="istatistikler.php">İstatistikler</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Site İstatistikleri</h1>
                </div>
                
                <!-- Genel İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Bugünkü Ziyaretçiler</h5>
                                <h3 class="text-primary">
                                    <?php
                                    $bugun = $pdo->query("SELECT COUNT(DISTINCT ip_adresi) FROM site_istatistik WHERE DATE(tarih) = CURDATE()")->fetchColumn();
                                    echo $bugun;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Bu Ay Toplam</h5>
                                <h3 class="text-success">
                                    <?php
                                    $bu_ay = $pdo->query("SELECT COUNT(*) FROM site_istatistik WHERE MONTH(tarih) = MONTH(NOW())")->fetchColumn();
                                    echo $bu_ay;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Toplam Navbar Tıklama</h5>
                                <h3 class="text-info">
                                    <?php
                                    $navbar_toplam = $pdo->query("SELECT COUNT(*) FROM navbar_tiklama")->fetchColumn();
                                    echo $navbar_toplam;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Toplam Sayfa Görüntüleme</h5>
                                <h3 class="text-warning">
                                    <?php
                                    $toplam_hit = $pdo->query("SELECT COUNT(*) FROM site_istatistik")->fetchColumn();
                                    echo $toplam_hit;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ziyaret Grafiği -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Son 30 Gün Ziyaret Grafiği</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ziyaretGrafik" width="400" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- En Çok Ziyaret Edilen Sayfalar -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>En Çok Ziyaret Edilen Sayfalar</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Sayfa</th>
                                            <th>Ziyaret</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($sayfalar as $sayfa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sayfa['sayfa']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $sayfa['ziyaret_sayisi']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Navbar Tıklama İstatistikleri -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Navbar Tıklama İstatistikleri</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Menu Item</th>
                                            <th>Tıklanma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($navbar_stats)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">Henüz navbar tıklama verisi yok</td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach($navbar_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['navbar_item']); ?></td>
                                            <td><span class="badge bg-success"><?php echo $stat['tiklanma_sayisi']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aylık İstatistikler -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Aylık İstatistikler</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ay/Yıl</th>
                                            <th>Toplam Ziyaret</th>
                                            <th>Benzersiz Ziyaretçi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
                                        foreach($aylik_stats as $stat): 
                                        ?>
                                        <tr>
                                            <td><?php echo $aylar[$stat['ay']-1] . ' ' . $stat['yil']; ?></td>
                                            <td><?php echo $stat['ziyaret_sayisi']; ?></td>
                                            <td><?php echo $stat['benzersiz_ziyaretci']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Ziyaret grafiği
        const ctx = document.getElementById('ziyaretGrafik').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $labels = array_map(function($item) { 
                        return "'" . date('d/m', strtotime($item['gun'])) . "'"; 
                    }, array_reverse($istatistikler));
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Ziyaret Sayısı',
                    data: [<?php 
                        $data = array_map(function($item) { 
                            return $item['ziyaret_sayisi']; 
                        }, array_reverse($istatistikler));
                        echo implode(',', $data);
                    ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Benzersiz Ziyaretçi',
                    data: [<?php 
                        $data2 = array_map(function($item) { 
                            return $item['benzersiz_ziyaretci']; 
                        }, array_reverse($istatistikler));
                        echo implode(',', $data2);
                    ?>],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Son 30 Gün Ziyaret İstatistikleri'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>