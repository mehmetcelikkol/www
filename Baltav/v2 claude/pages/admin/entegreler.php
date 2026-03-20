<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa   = 'entegreler';
$sayfa_basligi = 'Entegre Firma Yönetimi';

// Silme
if (isset($_GET['sil'], $_GET['csrf']) && csrf_dogrula($_GET['csrf'])) {
    $pdo->prepare("DELETE FROM entegre_firmalar WHERE id = ?")->execute([(int)$_GET['sil']]);
    flash_mesaj_ekle('success', 'Entegre firma silindi.');
    header('Location: /pages/admin/entegreler.php'); exit;
}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_dogrula($_POST['csrf_token'] ?? '')) {
        flash_mesaj_ekle('danger', 'Güvenlik hatası.');
    } else {
        $islem = $_POST['islem'] ?? '';
        $unvan = trim($_POST['unvan'] ?? '');
        $logo  = trim($_POST['logo'] ?? '');

        if (empty($unvan)) {
            flash_mesaj_ekle('danger', 'Unvan zorunludur.');
        } elseif ($islem === 'ekle') {
            $pdo->prepare("INSERT INTO entegre_firmalar (unvan, logo) VALUES (?, ?)")
                ->execute([$unvan, $logo ?: null]);
            flash_mesaj_ekle('success', "'{$unvan}' eklendi.");
        } elseif ($islem === 'duzenle') {
            $id = (int)($_POST['entegre_id'] ?? 0);
            $pdo->prepare("UPDATE entegre_firmalar SET unvan=?, logo=? WHERE id=?")
                ->execute([$unvan, $logo ?: null, $id]);
            flash_mesaj_ekle('success', 'Entegre firma güncellendi.');
        }
    }
    header('Location: /pages/admin/entegreler.php'); exit;
}

$entegreler = $pdo->query("
    SELECT e.*,
           COUNT(DISTINCT bei.bayi_id) AS bayi_sayisi,
           COUNT(DISTINCT c.id) AS cihaz_sayisi
    FROM entegre_firmalar e
    LEFT JOIN bayi_entegre_iliski bei ON bei.entegre_id = e.id
    LEFT JOIN cihazlar c ON c.aktif_entegre_id = e.id
    GROUP BY e.id
    ORDER BY e.unvan
")->fetchAll();

$csrf = csrf_token_olustur();
include __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <button class="ss-btn ss-btn-birincil" onclick="entModal()">
        <i class="bi bi-plus-lg"></i> Yeni Entegre Firma
    </button>
</div>

<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-building" style="color:var(--renk-aksan)"></i>
        Entegre Firmalar
        <span class="ss-badge ss-badge-mor ms-auto"><?= count($entegreler) ?> firma</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead><tr>
                <th>Firma Adı</th>
                <th>Logo URL</th>
                <th>Bağlı Bayi</th>
                <th>Aktif Cihaz</th>
                <th style="text-align:right;">İşlem</th>
            </tr></thead>
            <tbody>
            <?php foreach ($entegreler as $e): ?>
            <tr>
                <td style="font-weight:600;">
                    <?php if ($e['logo']): ?>
                        <img src="<?= html_temizle($e['logo']) ?>" alt="" style="height:24px; margin-right:8px; border-radius:4px; vertical-align:middle;" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <?= html_temizle($e['unvan']) ?>
                </td>
                <td style="font-size:11px; font-family:var(--font-mono); color:var(--renk-metin-soluk); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= $e['logo'] ? html_temizle($e['logo']) : '—' ?>
                </td>
                <td><span class="ss-badge ss-badge-bilgi"><?= $e['bayi_sayisi'] ?></span></td>
                <td><span class="ss-badge ss-badge-mor"><?= $e['cihaz_sayisi'] ?></span></td>
                <td style="text-align:right;">
                    <button class="ss-btn ss-btn-ikincil ss-btn-sm"
                            onclick="entModal(<?= htmlspecialchars(json_encode(['id'=>$e['id'],'unvan'=>$e['unvan'],'logo'=>$e['logo']]),ENT_QUOTES) ?>)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="ss-btn ss-btn-tehlike ss-btn-sm ms-1"
                            onclick="SS.silmeOnayi('/pages/admin/entegreler.php?sil=<?= $e['id'] ?>&csrf=<?= $csrf ?>', '<?= html_temizle($e['unvan']) ?> firmasını silmek istiyor musunuz?')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="entModal_el" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="entModalBaslik">Yeni Entegre Firma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= html_temizle($csrf) ?>">
                <input type="hidden" name="islem" id="entIslem" value="ekle">
                <input type="hidden" name="entegre_id" id="entId">
                <div class="modal-body" style="padding:24px;">
                    <div class="ss-form-grup">
                        <label class="ss-label">Firma Unvanı *</label>
                        <input type="text" name="unvan" id="entUnvan" class="ss-input" required>
                    </div>
                    <div class="ss-form-grup">
                        <label class="ss-label">Logo URL (opsiyonel)</label>
                        <input type="url" name="logo" id="entLogo" class="ss-input" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ss-btn ss-btn-ikincil" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="ss-btn ss-btn-birincil">
                        <i class="bi bi-check-lg"></i> <span id="entBtn">Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $sayfa_js = "
function entModal(e = null) {
    if (e) {
        document.getElementById('entModalBaslik').textContent = 'Firmayı Düzenle';
        document.getElementById('entIslem').value = 'duzenle';
        document.getElementById('entId').value = e.id;
        document.getElementById('entUnvan').value = e.unvan;
        document.getElementById('entLogo').value = e.logo || '';
        document.getElementById('entBtn').textContent = 'Güncelle';
    } else {
        document.getElementById('entModalBaslik').textContent = 'Yeni Entegre Firma';
        document.getElementById('entIslem').value = 'ekle';
        document.getElementById('entId').value = '';
        document.getElementById('entUnvan').value = '';
        document.getElementById('entLogo').value = '';
        document.getElementById('entBtn').textContent = 'Kaydet';
    }
    new bootstrap.Modal(document.getElementById('entModal_el')).show();
}
"; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
