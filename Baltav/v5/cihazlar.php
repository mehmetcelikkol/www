<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';

// POST İşlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST' && in_array($_SESSION['kullanici_rolu'], ['superadmin', 'admin'])) {
    if (isset($_POST['islem'])) {
        if ($_POST['islem'] == 'ekle') {
            $cihaz_kodu = trim($_POST['cihaz_kodu']);
            $cihaz_adi = trim($_POST['cihaz_adi']);
            $kumes_id = empty($_POST['kumes_id']) ? NULL : $_POST['kumes_id'];
            $kapasite = $_POST['kapasite_kg'];
            
            $q = $db->prepare("INSERT INTO cihazlar (cihaz_kodu, cihaz_adi, kumes_id, kapasite_kg) VALUES (?, ?, ?, ?)");
            $q->execute([$cihaz_kodu, $cihaz_adi, $kumes_id, $kapasite]);
            sistem_log_yaz($db, 'Silo Ekleme', "Sisteme yeni silo cihazı ($cihaz_kodu) tanımlandı.");
            $basari = "Yeni silo cihazı başarıyla eklendi.";
        } elseif ($_POST['islem'] == 'guncele') {
            $id = $_POST['id'];
            $yeni_kumes = empty($_POST['kumes_id']) ? NULL : $_POST['kumes_id'];
            $q = $db->prepare("UPDATE cihazlar SET kumes_id = ? WHERE id = ?");
            $q->execute([$yeni_kumes, $id]);
            sistem_log_yaz($db, 'Silo Atama', "Sistemden ($id) ID'li silo cihazının kümes bağlantısı güncellendi.");
            $basari = "Cihaz ait olduğu kümese başarıyla atandı.";
        } elseif ($_POST['islem'] == 'sil' && $_SESSION['kullanici_rolu'] == 'superadmin') {
            $id = $_POST['id'];
            $q = $db->prepare("DELETE FROM cihazlar WHERE id = ?");
            $q->execute([$id]);
            sistem_log_yaz($db, 'Silo Silme', "Sistemden ($id) ID'li silo cihazı tamamen silindi.");
            $basari = "Cihaz başarıyla silindi.";
        } elseif ($_POST['islem'] == 'sil') {
            $hata = "Silme işlemi için sadece Superadmin yetkilidir!";
        }
    }
}

$kumes_sorgu_ek = "";
if ($_SESSION['kullanici_rolu'] == 'entegre') {
    $kumes_sorgu_ek = " WHERE c.kumes_id IN (SELECT id FROM kumesler WHERE entegre_id = " . (int)$_SESSION['entegre_id'] . ") ";
} elseif ($_SESSION['kullanici_rolu'] == 'isletmeci') {
    $kumes_sorgu_ek = " WHERE c.kumes_id IN (SELECT id FROM kumesler WHERE isletmeci_id = " . (int)$_SESSION['isletmeci_id'] . ") ";
}

$cihazlar = $db->query("SELECT c.*, k.unvan as kumes_adi, i.unvan as isletmeci_adi,
    (SELECT alinan_zaman FROM cihaz_paketleri cp WHERE cp.cihaz_kodu = c.cihaz_kodu ORDER BY alinan_zaman DESC LIMIT 1) as son_gorulme
    FROM cihazlar c LEFT JOIN kumesler k ON c.kumes_id = k.id LEFT JOIN isletmeciler i ON k.isletmeci_id = i.id $kumes_sorgu_ek ORDER BY c.id DESC")->fetchAll();
$kumesler = $db->query("SELECT k.id, k.unvan, i.unvan as isletmeci_adi FROM kumesler k LEFT JOIN isletmeciler i ON k.isletmeci_id = i.id ORDER BY k.unvan ASC")->fetchAll();
?>

<div class="main-content">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h5 class="m-0 page-title">Silo Envanteri (Cihazlar)</h5>
        <div class="btn-group">
            <button onclick="tabloKopyala('cihazTablosu')" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-copy"></i> Kopyala</button>
            <button onclick="tabloExcelIndir('cihazTablosu', 'Silo_Envanteri')" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel"></i> Excel</button>
            <button onclick="tabloPdfIndir('cihazTablosu', 'Silo_Envanteri')" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf"></i> PDF</button>
        </div>
    </div>
    <?php if(isset($basari)): ?><div class="alert alert-success bg-success text-white border-0"><?= $basari ?></div><?php endif; ?>
    <?php if(isset($hata)): ?><div class="alert alert-danger bg-danger text-white border-0"><?= $hata ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="silo-card glass p-4">
                <table class="table table-hover align-middle" id="cihazTablosu">
                    <thead class="table-light">
                        <tr><th>Durum</th><th>Cihaz Kodu</th><th>Adı</th><th>Kapasite</th><th>Bulunduğu Kümes</th><th>İşlemler</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($cihazlar as $c): 
                            $online = false;
                            if ($c['son_gorulme']) {
                                $fark = time() - strtotime($c['son_gorulme']);
                                if ($fark < 1800) $online = true;
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="status-indicator <?= $online ? 'status-online' : 'status-offline' ?>" title="<?= $online ? 'Bağlı (Son veri: ' . date('H:i', strtotime($c['son_gorulme'])) . ')' : 'Devre Dışı (Son veri: ' . ($c['son_gorulme'] ? date('d.m H:i', strtotime($c['son_gorulme'])) : 'Yok') . ')' ?>"></span>
                            </td>
                            <td><strong><?= htmlspecialchars($c['cihaz_kodu']) ?></strong></td>
                            <td><i class="fa-solid fa-wheat-awn text-warning"></i> <?= htmlspecialchars($c['cihaz_adi'] ?? 'İsimsiz Cihaz') ?></td>
                            <td><?= number_format((float)($c['kapasite_kg'] ?? 0), 0, ',', '.') ?> kg</td>
                            <td>
                                <?php if(in_array($_SESSION['kullanici_rolu'], ['superadmin', 'admin'])): ?>
                                    <form method="POST" class="d-flex gap-1 mb-0 align-items-center w-100">
                                        <input type="hidden" name="islem" value="guncele">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <select name="kumes_id" class="form-select form-select-sm text-sm" style="max-width: 250px; font-size: 0.85rem;">
                                            <option value="">Atanmamış Cihaz</option>
                                            <?php foreach($kumesler as $tk): ?>
                                                <option value="<?= $tk['id'] ?>" <?= ($c['kumes_id'] == $tk['id']) ? 'selected' : '' ?>>
                                                    [<?= htmlspecialchars($tk['isletmeci_adi'] ?? 'İşletmecisiz') ?>] - <?= htmlspecialchars($tk['unvan']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-link"></i></button>
                                    </form>
                                <?php else: ?>
                                    <?php if($c['kumes_adi']): 
                                        $kRenk = rozet_renk_getir($c['kumes_id']);
                                    ?>
                                        <span class="badge fw-medium" style="background-color: <?= $kRenk['bg'] ?>; color: <?= $kRenk['text'] ?>; border: 1px solid <?= $kRenk['border'] ?>;">
                                            <i class="fa-solid fa-house-chimney"></i> <?= htmlspecialchars($c['kumes_adi']) ?> 
                                            <small class="opacity-75 ms-1">(<?= htmlspecialchars($c['isletmeci_adi'] ?? '') ?>)</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Bağımsız Cihaz</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="cihaz_detay.php?kodu=<?= $c['cihaz_kodu'] ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-chart-area"></i></a>
                                <?php if($_SESSION['kullanici_rolu'] == 'superadmin'): ?>
                                <form method="POST" class="d-inline m-0" onsubmit="return confirm('Emin misiniz?');">
                                    <input type="hidden" name="islem" value="sil">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

        <?php if(in_array($_SESSION['kullanici_rolu'], ['superadmin', 'admin'])): ?>
        <div class="col-lg-4">
            <div class="silo-card glass p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-tower-broadcast text-primary"></i> Yeni Silo Tanımla</h5>
                <form method="POST">
                    <input type="hidden" name="islem" value="ekle">
                    <div class="mb-3">
                        <label class="small text-muted">Cihaz Kodu (Örn: SILO-3000)</label>
                        <input type="text" name="cihaz_kodu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Silo Adı</label>
                        <input type="text" name="cihaz_adi" class="form-control" value="Tavuk Yemi Silosu" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Kapasite (kg)</label>
                        <input type="number" name="kapasite_kg" class="form-control" value="25000" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted">Bulunduğu Kümes</label>
                        <select name="kumes_id" class="form-select">
                            <option value="">Bağımsız Bırak (Sonra Ata)</option>
                            <?php foreach($kumesler as $k): ?>
                                <option value="<?= $k['id'] ?>">[<?= htmlspecialchars($k['isletmeci_adi'] ?? 'İşletmeci Yok') ?>] - <?= htmlspecialchars($k['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Cihazı Ekle</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'sistem/footer.php'; ?>
