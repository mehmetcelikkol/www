<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';
yetki_kontrol(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['islem'])) {
        if ($_POST['islem'] == 'ekle') {
            $unvan = trim($_POST['unvan']);
            $yetkili = trim($_POST['yetkili']);
            $tel = trim($_POST['telefon']);
            
            $q = $db->prepare("INSERT INTO entegreler (unvan, yetkili, telefon) VALUES (?, ?, ?)");
            $q->execute([$unvan, $yetkili, $tel]);
            sistem_log_yaz($db, 'Entegre Ekleme', "Sisteme ($unvan) adlı yeni entegre firma kayıt edildi.");
            $basari = "Entegre firma başarıyla eklendi.";
        } elseif ($_POST['islem'] == 'sil' && $_SESSION['kullanici_rolu'] == 'superadmin') {
            $id = $_POST['id'];
            $q = $db->prepare("DELETE FROM entegreler WHERE id = ?");
            $q->execute([$id]);
            sistem_log_yaz($db, 'Entegre Silme', "Sistemden ($id) ID'li entegre firma tamamen silindi.");
            $basari = "Entegre firma başarıyla silindi.";
        } elseif ($_POST['islem'] == 'sil') {
            $hata = "Silme işlemi için sadece Superadmin yetkilidir!";
        }
    }
}

$liste = $db->query("SELECT * FROM entegreler ORDER BY id DESC")->fetchAll();
?>
<div class="main-content">
    <div class="topbar"><h5 class="m-0 page-title">Entegre Firmalar Yönetimi</h5></div>
    <?php if(isset($basari)): ?><div class="alert alert-success bg-success text-white border-0"><?= $basari ?></div><?php endif; ?>
    <?php if(isset($hata)): ?><div class="alert alert-danger bg-danger text-white border-0"><?= $hata ?></div><?php endif; ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="silo-card glass p-4">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Firma Ünvanı</th><th>Yetkili</th><th>Telefon</th><th>İşlemler</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($liste as $i): ?>
                        <tr>
                            <td><?= $i['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($i['unvan']) ?></td>
                            <td><?= htmlspecialchars($i['yetkili']) ?></td>
                            <td><?= htmlspecialchars($i['telefon']) ?></td>
                            <td>
                                <?php if($_SESSION['kullanici_rolu'] == 'superadmin'): ?>
                                <form method="POST" class="d-inline m-0" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="islem" value="sil">
                                    <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="silo-card glass p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus text-primary"></i> Yeni Entegre Ekle</h5>
                <form method="POST">
                    <input type="hidden" name="islem" value="ekle">
                    <div class="mb-3"><label class="small text-muted">Firma Ünvanı</label><input type="text" name="unvan" class="form-control" required></div>
                    <div class="mb-3"><label class="small text-muted">Yetkili Kişi</label><input type="text" name="yetkili" class="form-control"></div>
                    <div class="mb-3"><label class="small text-muted">Telefon</label><input type="text" name="telefon" class="form-control"></div>
                    <button type="submit" class="btn btn-success w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'sistem/footer.php'; ?>
