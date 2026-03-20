<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();

if($_SESSION['rol'] != 'admin') die("Yetkisiz Erişim");

require_once __DIR__ . '/includes/header.php';
$db = Database::getConnection();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ekle'])) {
    $unvan = guvenli($_POST['unvan']);
    $sehir = guvenli($_POST['sehir']);
    $db->prepare("INSERT INTO bayiler (unvan, sehir) VALUES (?, ?)")->execute([$unvan, $sehir]);
}

$bayiler = $db->query("SELECT * FROM bayiler ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-store text-success"></i> Bayiler (Kümesler)</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bayiModal">
            <i class="fa-solid fa-plus"></i> Yeni Bayi
        </button>
    </div>

    <div class="row g-4">
        <?php foreach($bayiler as $b): ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar bg-light-success text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                        <i class="fa-solid fa-shop fa-2x"></i>
                    </div>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($b['unvan']); ?></h5>
                    <p class="text-muted small"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($b['sehir']); ?></p>
                    <a href="#" class="btn btn-sm btn-outline-success rounded-pill px-4">Detay</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="bayiModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Bayi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Bayi Adı</label>
                    <input type="text" name="unvan" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Şehir</label>
                    <input type="text" name="sehir" class="form-control">
                </div>
                <input type="hidden" name="ekle" value="1">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
