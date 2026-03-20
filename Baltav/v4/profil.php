<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
require_once __DIR__ . '/includes/header.php';

$mesaj = "";
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sifre1 = $_POST['sifre1'];
    $sifre2 = $_POST['sifre2'];
    
    if($sifre1 == $sifre2 && !empty($sifre1)) {
        $hash = password_hash($sifre1, PASSWORD_DEFAULT);
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE kullanicilar SET sifre_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['kullanici_id']]);
        $mesaj = '<div class="alert alert-success">Şifre güncellendi!</div>';
    } else {
        $mesaj = '<div class="alert alert-danger">Şifreler uyuşmuyor!</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center mb-4">
                    <div class="avatar bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:80px; height:80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($_SESSION['ad_soyad'], 0, 1)); ?>
                    </div>
                    <h3><?php echo $_SESSION['ad_soyad']; ?></h3>
                    <span class="badge bg-secondary"><?php echo strtoupper($_SESSION['rol']); ?></span>
                </div>
                
                <?php echo $mesaj; ?>
                
                <form method="POST">
                    <h5 class="mb-3">Şifre Değiştir</h5>
                    <div class="mb-3">
                        <label>Yeni Şifre</label>
                        <input type="password" name="sifre1" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Yeni Şifre (Tekrar)</label>
                        <input type="password" name="sifre2" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
