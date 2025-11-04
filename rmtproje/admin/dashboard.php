<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

include '../config/database.php';

// Günlük istatistikler
$bugun_ziyaret = $pdo->query("SELECT COUNT(DISTINCT ip_adresi) FROM site_istatistik WHERE DATE(tarih) = CURDATE()")->fetchColumn();
$toplam_ziyaret = $pdo->query("SELECT COUNT(*) FROM site_istatistik")->fetchColumn();
$bugun_toplam_hit = $pdo->query("SELECT COUNT(*) FROM site_istatistik WHERE DATE(tarih) = CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RMT Proje</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="istatistikler.php">
                                <i class="fas fa-chart-bar"></i> İstatistikler
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Yenile</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Bugünkü Ziyaretçiler</h5>
                                <h2><?php echo $bugun_ziyaret; ?></h2>
                                <small>Benzersiz IP adresi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Bugünkü Hit Sayısı</h5>
                                <h2><?php echo $bugun_toplam_hit; ?></h2>
                                <small>Toplam sayfa görüntüleme</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Toplam Ziyaret</h5>
                                <h2><?php echo $toplam_ziyaret; ?></h2>
                                <small>Tüm zamanlar</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Son Ziyaretler</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>IP Adresi</th>
                                            <th>Sayfa</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $son_ziyaretler = $pdo->query("SELECT ip_adresi, sayfa, tarih FROM site_istatistik ORDER BY tarih DESC LIMIT 10")->fetchAll();
                                        foreach($son_ziyaretler as $ziyaret):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ziyaret['ip_adresi']); ?></td>
                                            <td><?php echo htmlspecialchars($ziyaret['sayfa']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($ziyaret['tarih'])); ?></td>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>