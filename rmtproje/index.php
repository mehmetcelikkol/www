<?php

// İstatistik takibi - doğrudan kod
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantısı
try {
    $host = 'localhost';
    $dbname = 'proje_rmt';
    $username = 'proje_rmt';
    $password = '0120a0120A';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İstatistik kaydet
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sayfa = $_SERVER['REQUEST_URI'] ?? '/';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $session_id = session_id();
    
    $stmt = $pdo->prepare("INSERT INTO site_istatistik (ip_adresi, sayfa, user_agent, referer, session_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ip, $sayfa, $user_agent, $referer, $session_id]);
    
} catch (Exception $e) {
    // Hata olursa sessizce devam et
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMT Proje</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">RMT Proje</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" onclick="kayitNavbar('Ana Sayfa')">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="hakkimizda.php" onclick="kayitNavbar('Hakkımızda')">Hakkımızda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="iletisim.php" onclick="kayitNavbar('İletişim')">İletişim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php" onclick="kayitNavbar('Admin')">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1>Hoşgeldiniz</h1>
                <p>RMT Proje ana sayfasına hoşgeldiniz.</p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar tıklama takibi
        function kayitNavbar(item) {
            fetch('kayit_navbar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    navbar_item: item
                })
            }).catch(error => {
                console.log('Navbar kayıt hatası:', error);
            });
        }
    </script>
</body>
</html>