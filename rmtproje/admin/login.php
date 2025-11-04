<?php
session_start();

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_POST) {
    include '../config/database.php';
    
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE kullanici_adi = ?");
    $stmt->execute([$kullanici_adi]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($sifre, $admin['sifre'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_kullanici'] = $admin['kullanici_adi'];
        header('Location: dashboard.php');
        exit;
    } else {
        $hata = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - RMT Proje</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center">Admin Girişi</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($hata)): ?>
                            <div class="alert alert-danger"><?php echo $hata; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
                            </div>
                            <div class="mb-3">
                                <label for="sifre" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="sifre" name="sifre" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Varsayılan: admin / admin123</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>