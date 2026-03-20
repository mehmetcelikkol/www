<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';
yetki_kontrol(['superadmin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['islem'])) {
        if ($_POST['islem'] == 'ekle') {
            $unvan = $_POST['unvan'];
            $adres = $_POST['adres'];
            $isletmeci_id = $_POST['isletmeci_id'];
            $entegre_id = empty($_POST['entegre_id']) ? NULL : $_POST['entegre_id'];
            
            $q = $db->prepare("INSERT INTO kumesler (isletmeci_id, entegre_id, unvan, adres) VALUES (?, ?, ?, ?)");
            $q->execute([$isletmeci_id, $entegre_id, $unvan, $adres]);
            sistem_log_yaz($db, 'Kümes Ekleme', "Sisteme ($unvan) adlı yeni fiziksel kümes binası kayıt edildi.");
            $basari = "Yeni kümes (bina) başarıyla eklendi.";
        } elseif ($_POST['islem'] == 'guncele') {
            $id = $_POST['id'];
            $entegre_id = empty($_POST['entegre_id']) ? NULL : $_POST['entegre_id'];
            $q = $db->prepare("UPDATE kumesler SET entegre_id = ? WHERE id = ?");
            $q->execute([$entegre_id, $id]);
            sistem_log_yaz($db, 'Bağlantı Guncelleme', "($id) ID'li Kümesin entegre firma bağlantısı güncellendi.");
            $basari = "Kümesin entegre bağlantısı güncellendi.";
        } elseif ($_POST['islem'] == 'sil' && $_SESSION['kullanici_rolu'] == 'superadmin') {
            $id = $_POST['id'];
            $q = $db->prepare("DELETE FROM kumesler WHERE id = ?");
            $q->execute([$id]);
            sistem_log_yaz($db, 'Kümes Silme', "Sistemden ($id) ID'li kümes binası tamamen silindi.");
            $basari = "Kümes başarıyla silindi.";
        } elseif ($_POST['islem'] == 'sil') {
            $hata = "Silme işlemi için sadece Superadmin yetkilidir!";
        }
    }
}

$kumesler = $db->query("SELECT k.*, i.unvan as isletmeci_adi, e.unvan as entegre_adi FROM kumesler k LEFT JOIN entegreler e ON k.entegre_id = e.id LEFT JOIN isletmeciler i ON k.isletmeci_id = i.id ORDER BY k.id DESC")->fetchAll();
$entegreler = $db->query("SELECT id, unvan FROM entegreler ORDER BY unvan ASC")->fetchAll();
$isletmeciler = $db->query("SELECT id, unvan FROM isletmeciler ORDER BY unvan ASC")->fetchAll();
?>
<div class="main-content">
    <div class="topbar"><h5 class="m-0 page-title">Fiziksel Kümes Binaları Yönetimi</h5></div>
    <?php if(isset($basari)): ?><div class="alert alert-success bg-success text-white border-0"><?= $basari ?></div><?php endif; ?>
    <?php if(isset($hata)): ?><div class="alert alert-danger bg-danger text-white border-0"><?= $hata ?></div><?php endif; ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="silo-card glass p-4">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Kümes Adı</th><th>Bağlı İşletmeci</th><th>Entegre Durumu</th><th>İşlemler</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($kumesler as $k): ?>
                        <tr>
                            <td><?= $k['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($k['unvan']) ?></td>
                            <td>
                                <?php if($k['isletmeci_adi']): 
                                    $iRenk = rozet_renk_getir($k['isletmeci_id']);
                                ?>
                                    <span class="badge fw-medium" style="background-color: <?= $iRenk['bg'] ?>; color: <?= $iRenk['text'] ?>; border: 1px solid <?= $iRenk['border'] ?>;"><i class="fa-solid fa-users"></i> <?= htmlspecialchars($k['isletmeci_adi']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-flex gap-2 align-items-center mb-0">
                                    <input type="hidden" name="islem" value="guncele">
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <select name="entegre_id" class="form-select form-select-sm border-0 bg-light" style="width: 150px;">
                                        <option value="">Bağımsız</option>
                                        <?php foreach($entegreler as $e): ?>
                                            <option value="<?= $e['id'] ?>" <?= $k['entegre_id'] == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['unvan']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Değiştir</button>
                                </form>
                            </td>
                            <td>
                                <?php if($_SESSION['kullanici_rolu'] == 'superadmin'): ?>
                                <form method="POST" class="d-inline m-0" onsubmit="return confirm('Silerken bağlı silolar da kopar. Emin misiniz?');">
                                    <input type="hidden" name="islem" value="sil">
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-sm btn-secondary disabled mb-0" title="Sadece Superadmin Silebilir"><i class="fa-solid fa-trash"></i></button>
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
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus text-primary"></i> Yeni Kümes Tanımla</h5>
                <form method="POST">
                    <input type="hidden" name="islem" value="ekle">
                    <div class="mb-3">
                        <label class="small text-muted">Sahip İşletmeci (Zorunlu)</label>
                        <select name="isletmeci_id" class="form-select" required>
                            <option value="">İşletmeci Seçiniz</option>
                            <?php foreach($isletmeciler as $i): ?>
                                <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Kümes Adı / Bina No</label>
                        <input type="text" name="unvan" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Adres / Şehir</label>
                        <input type="text" name="adres" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Şu An Çalıştığı Entegre</label>
                        <select name="entegre_id" class="form-select">
                            <option value="">Seçiniz / Bağımsız</option>
                            <?php foreach($entegreler as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'sistem/footer.php'; ?>
