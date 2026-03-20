<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();

// Sadece Admin Girebilir
if($_SESSION['rol'] != 'admin') {
    die("Yetkisiz Erişim");
}

require_once __DIR__ . '/includes/header.php';
$db = Database::getConnection();

// Kullanıcı Ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ekle'])) {
    $ad = guvenli($_POST['ad_soyad']);
    $email = guvenli($_POST['eposta']);
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    
    $stmt = $db->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$ad, $email, $sifre, $rol]);
        echo '<div class="alert alert-success">Kullanıcı eklendi!</div>';
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">Hata: '.$e->getMessage().'</div>';
    }
}

// Kullanıcı Silme
if(isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    if($id != $_SESSION['kullanici_id']) { // Kendini silemez
        $db->query("DELETE FROM kullanicilar WHERE id=$id");
        echo '<script>window.location="kullanicilar.php";</script>';
    }
}

$users = $db->query("SELECT * FROM kullanicilar ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-users text-primary"></i> Kullanıcı Yönetimi</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fa-solid fa-plus"></i> Yeni Kullanıcı
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-Posta</th>
                        <th>Rol</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-light text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:35px; height:35px;">
                                    <?php echo strtoupper(substr($u['ad_soyad'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($u['ad_soyad']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['eposta']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $u['rol']=='admin'?'danger':($u['rol']=='entegre'?'warning':'info'); ?>">
                                <?php echo strtoupper($u['rol']); ?>
                            </span>
                        </td>
                        <td><?php echo $u['olusturma_tarihi']; ?></td>
                        <td>
                            <?php if($u['id'] != $_SESSION['kullanici_id']): ?>
                            <a href="?sil=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silinsin mi?')"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Ad Soyad</label>
                    <input type="text" name="ad_soyad" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>E-Posta</label>
                    <input type="email" name="eposta" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Şifre</label>
                    <input type="password" name="sifre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Rol</label>
                    <select name="rol" class="form-select">
                        <option value="bayi">Bayi</option>
                        <option value="entegre">Entegre Firma</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <input type="hidden" name="ekle" value="1">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
