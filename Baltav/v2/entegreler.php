<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();

if($_SESSION['rol'] != 'admin') die("Yetkisiz Erişim");

require_once __DIR__ . '/includes/header.php';
$db = Database::getConnection();

// Ekleme
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ekle'])) {
    $unvan = guvenli($_POST['unvan']);
    $yetkili = guvenli($_POST['yetkili']);
    
    $stmt = $db->prepare("INSERT INTO entegre_firmalar (unvan, yetkili_kisi) VALUES (?, ?)");
    $stmt->execute([$unvan, $yetkili]);
}

// Listeleme
$firmalar = $db->query("SELECT * FROM entegre_firmalar ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-building text-warning"></i> Entegre Firmalar</h2>
        <button class="btn btn-warning text-white" data-bs-toggle="modal" data-bs-target="#entegreModal">
            <i class="fa-solid fa-plus"></i> Yeni Firma
        </button>
    </div>

    <div class="row g-4">
        <?php foreach($firmalar as $f): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($f['unvan']); ?></h5>
                        <i class="fa-solid fa-industry text-muted fa-2x opacity-25"></i>
                    </div>
                    <p class="card-text text-muted small">
                        <i class="fa-solid fa-user me-2"></i> <?php echo htmlspecialchars($f['yetkili_kisi']); ?><br>
                        <i class="fa-solid fa-phone me-2"></i> <?php echo htmlspecialchars($f['telefon'] ?? '-'); ?>
                    </p>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark">ID: <?php echo $f['id']; ?></span>
                        <button class="btn btn-sm btn-outline-primary">Düzenle</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="entegreModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Entegre Firma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Firma Ünvanı</label>
                    <input type="text" name="unvan" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Yetkili Kişi</label>
                    <input type="text" name="yetkili" class="form-control">
                </div>
                <input type="hidden" name="ekle" value="1">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning text-white">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
