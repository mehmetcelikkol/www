<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
if (!$auth->hasRole(['admin'])) { header('Location: index.php'); exit; }
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id>0;
$user = null;
if ($editing) {
    $stmt = $db->prepare("SELECT id, ad_soyad, eposta, rol, bagli_id FROM kullanicilar WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $user = $stmt->fetch();
    if (!$user) { echo '<div class="alert alert-warning">Kullanıcı bulunamadı.</div>'; require_once __DIR__ . '/includes/footer.php'; exit; }
}

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad_soyad'] ?? '');
    $ep = trim($_POST['eposta'] ?? '');
    $sifre = $_POST['sifre'] ?? null;
    $rol = $_POST['rol'] ?? 'bayi';
    $bagli = $_POST['bagli_id'] ? (int)$_POST['bagli_id'] : null;

    if (!$ad || !$ep) { $err = 'Ad soyad ve eposta gerekli.'; }
    else {
        if ($editing) {
            if ($sifre) {
              // GEÇİCİ: düz metin parola saklama
              $hash = $sifre;
              $stmt = $db->prepare("UPDATE kullanicilar SET ad_soyad=:ad, eposta=:ep, sifre_hash=:hash, rol=:rol, bagli_id=:bagli, updated_at=NOW() WHERE id=:id");
              $stmt->execute([':ad'=>$ad,':ep'=>$ep,':hash'=>$hash,':rol'=>$rol,':bagli'=>$bagli,':id'=>$id]);
            } else {
                $stmt = $db->prepare("UPDATE kullanicilar SET ad_soyad=:ad, eposta=:ep, rol=:rol, bagli_id=:bagli, updated_at=NOW() WHERE id=:id");
                $stmt->execute([':ad'=>$ad,':ep'=>$ep,':rol'=>$rol,':bagli'=>$bagli,':id'=>$id]);
            }
        } else {
            if (!$sifre) { $err = 'Yeni kullanıcı için parola gerekli.'; }
            else {
              // GEÇİCİ: düz metin parola saklama
              $hash = $sifre;
              $stmt = $db->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol, bagli_id) VALUES (:ad,:ep,:hash,:rol,:bagli)");
              $stmt->execute([':ad'=>$ad,':ep'=>$ep,':hash'=>$hash,':rol'=>$rol,':bagli'=>$bagli]);
                $newId = $db->lastInsertId();
                header('Location: kullanicilar.php'); exit;
            }
        }
        if (!$err) { header('Location: kullanicilar.php'); exit; }
    }
}

?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $editing? 'Kullanıcı Düzenle':'Yeni Kullanıcı'; ?></h2>
    <div>
      <a href="kullanicilar.php" class="btn btn-sm btn-secondary">Geri</a>
    </div>
  </div>

  <?php if($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

  <div class="card p-3" style="max-width:700px">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="ad_soyad" class="form-control" value="<?php echo $user['ad_soyad'] ?? ''; ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="eposta" class="form-control" value="<?php echo $user['eposta'] ?? ''; ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Parola <?php if($editing) echo '<small class="text-muted">(boş bırakılırsa değişmez)</small>'; ?></label>
        <input type="password" name="sifre" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Rol</label>
        <select name="rol" class="form-select">
          <option value="admin" <?php if(($user['rol']??'')==='admin') echo 'selected'; ?>>Admin</option>
          <option value="entegre" <?php if(($user['rol']??'')==='entegre') echo 'selected'; ?>>Entegre</option>
          <option value="bayi" <?php if(($user['rol']??'')==='bayi') echo 'selected'; ?>>Bayi</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Bağlı ID (bayi/entegre için)</label>
        <input type="number" name="bagli_id" class="form-control" value="<?php echo $user['bagli_id'] ?? ''; ?>">
      </div>
      <div class="d-flex justify-content-end">
        <button class="btn btn-primary"><?php echo $editing? 'Güncelle':'Oluştur'; ?></button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>