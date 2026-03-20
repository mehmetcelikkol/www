<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';

$rol = $_SESSION['kullanici_rolu'];
$sorgu_ek = " WHERE 1=1 ";
$parametreler = [];

if ($rol != 'superadmin') {
    $sorgu_ek .= " AND kullanici_id = ? ";
    $parametreler[] = $_SESSION['kullanici_id'];
}

// Filtreler
if (!empty($_GET['tip'])) {
    $sorgu_ek .= " AND islem_tipi = ? ";
    $parametreler[] = $_GET['tip'];
}
if (!empty($_GET['baslangic'])) {
    $sorgu_ek .= " AND DATE(tarih) >= ? ";
    $parametreler[] = $_GET['baslangic'];
}
if (!empty($_GET['bitis'])) {
    $sorgu_ek .= " AND DATE(tarih) <= ? ";
    $parametreler[] = $_GET['bitis'];
}

// Mevcut Işlem Tipleri (Filtre menüsü için)
if ($rol == 'superadmin') {
     $tipler = $db->query("SELECT DISTINCT islem_tipi FROM sistem_loglari ORDER BY islem_tipi ASC")->fetchAll(PDO::FETCH_COLUMN);
} else {
     $tipler_sorgu = $db->prepare("SELECT DISTINCT islem_tipi FROM sistem_loglari WHERE kullanici_id = ? ORDER BY islem_tipi ASC");
     $tipler_sorgu->execute([$_SESSION['kullanici_id']]);
     $tipler = $tipler_sorgu->fetchAll(PDO::FETCH_COLUMN);
}

$sorgu = $db->prepare("SELECT * FROM sistem_loglari $sorgu_ek ORDER BY id DESC LIMIT 500");
$sorgu->execute($parametreler);
$loglar = $sorgu->fetchAll();
?>
<div class="main-content">

    <div class="topbar d-flex justify-content-between align-items-center">
        <h5 class="m-0 page-title">Sistem İşlem Logları</h5>
        <div class="btn-group">
            <button onclick="tabloPdfIndir('logTablosu', 'Sistem_Loglari')" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Çıktı Al (PDF)</button>
            <button onclick="tabloExcelIndir('logTablosu', 'Sistem_Loglari')" class="btn btn-success"><i class="fa-solid fa-file-excel"></i> Çıktı Al (Excel)</button>
        </div>
    </div>
    
    <div class="silo-card glass p-4 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small">İşlem Tipi</label>
                <select name="tip" class="form-select">
                    <option value="">Tüm İşlemler</option>
                    <?php foreach($tipler as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= (isset($_GET['tip']) && $_GET['tip'] == $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">Başlangıç Tarihi</label>
                <input type="date" name="baslangic" class="form-control" value="<?= htmlspecialchars($_GET['baslangic'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">Bitiş Tarihi</label>
                <input type="date" name="bitis" class="form-control" value="<?= htmlspecialchars($_GET['bitis'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i> Filtrele</button>
            </div>
        </form>
    </div>

    <div class="silo-card glass p-4" id="logTablosu">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <th>İşlem Tipi</th>
                    <?php if($rol == 'superadmin'): ?><th>Yetkili Hesabı</th><?php endif; ?>
                    <th>Açıklama</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($loglar as $l): ?>
                <tr>
                    <td><i class="fa-regular fa-clock text-muted"></i> <?= date('d.m.Y H:i', strtotime($l['tarih'])) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($l['islem_tipi']) ?></span></td>
                    <?php if($rol == 'superadmin'): ?>
                    <td><strong><?= htmlspecialchars($l['kullanici_adi']) ?></strong> <span class="small text-muted">(<?= htmlspecialchars($l['rol']) ?>)</span></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($l['aciklama']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($loglar) === 0): ?>
                <tr><td colspan="<?= ($rol == 'superadmin') ? '4' : '3' ?>" class="text-center text-muted py-4">Bu filtrelere uygun log bulunamadı.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'sistem/footer.php'; ?>
