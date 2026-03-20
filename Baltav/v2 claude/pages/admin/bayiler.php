<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa   = 'bayiler';
$sayfa_basligi = 'Bayi Yönetimi';

// ---- Silme ----
if (isset($_GET['sil'], $_GET['csrf']) && csrf_dogrula($_GET['csrf'])) {
    $pdo->prepare("DELETE FROM bayiler WHERE id = ?")->execute([(int)$_GET['sil']]);
    flash_mesaj_ekle('success', 'Bayi silindi.');
    header('Location: /pages/admin/bayiler.php'); exit;
}

// ---- POST: Ekle / Düzenle ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_dogrula($_POST['csrf_token'] ?? '')) {
        flash_mesaj_ekle('danger', 'Güvenlik hatası.');
    } else {
        $islem   = $_POST['islem'] ?? '';
        $unvan   = trim($_POST['unvan'] ?? '');
        $yetkili = trim($_POST['yetkili'] ?? '');
        $tel     = trim($_POST['tel'] ?? '');
        $entegre_idler = array_map('intval', array_filter($_POST['entegre_idler'] ?? []));

        if (empty($unvan)) {
            flash_mesaj_ekle('danger', 'Bayi unvanı zorunludur.');
        } else {
            if ($islem === 'ekle') {
                $stmt = $pdo->prepare("INSERT INTO bayiler (unvan, yetkili, tel) VALUES (?, ?, ?)");
                $stmt->execute([$unvan, $yetkili ?: null, $tel ?: null]);
                $yeni_bayi_id = $pdo->lastInsertId();

                // Entegre ilişkileri ekle
                if (!empty($entegre_idler)) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO bayi_entegre_iliski (bayi_id, entegre_id) VALUES (?, ?)");
                    foreach ($entegre_idler as $eid) {
                        $ins->execute([$yeni_bayi_id, $eid]);
                    }
                }
                flash_mesaj_ekle('success', "'{$unvan}' bayisi eklendi.");

            } elseif ($islem === 'duzenle') {
                $bayi_id = (int)($_POST['bayi_id'] ?? 0);
                $pdo->prepare("UPDATE bayiler SET unvan=?, yetkili=?, tel=? WHERE id=?")
                    ->execute([$unvan, $yetkili ?: null, $tel ?: null, $bayi_id]);

                // Entegre ilişkilerini yenile
                $pdo->prepare("DELETE FROM bayi_entegre_iliski WHERE bayi_id=?")->execute([$bayi_id]);
                if (!empty($entegre_idler)) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO bayi_entegre_iliski (bayi_id, entegre_id) VALUES (?, ?)");
                    foreach ($entegre_idler as $eid) {
                        $ins->execute([$bayi_id, $eid]);
                    }
                }
                flash_mesaj_ekle('success', 'Bayi güncellendi.');
            }
        }
    }
    header('Location: /pages/admin/bayiler.php'); exit;
}

// ---- Veri Çek ----
$bayiler = $pdo->query("
    SELECT b.*,
           COUNT(DISTINCT c.id) AS cihaz_sayisi,
           COUNT(DISTINCT bei.entegre_id) AS entegre_sayisi
    FROM bayiler b
    LEFT JOIN cihazlar c ON c.sahip_bayi_id = b.id
    LEFT JOIN bayi_entegre_iliski bei ON bei.bayi_id = b.id
    GROUP BY b.id
    ORDER BY b.unvan
")->fetchAll();

$entegreler = $pdo->query("SELECT id, unvan FROM entegre_firmalar ORDER BY unvan")->fetchAll();

// Bayi - entegre mevcut ilişkiler
$iliski_harita = [];
$rows = $pdo->query("SELECT bayi_id, entegre_id FROM bayi_entegre_iliski")->fetchAll();
foreach ($rows as $row) {
    $iliski_harita[$row['bayi_id']][] = $row['entegre_id'];
}

$csrf = csrf_token_olustur();
include __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <button class="ss-btn ss-btn-birincil" onclick="bayiModalAc()">
        <i class="bi bi-plus-lg"></i> Yeni Bayi
    </button>
</div>

<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-shop" style="color:var(--renk-aksan)"></i>
        Kayıtlı Bayiler
        <span class="ss-badge ss-badge-mor ms-auto"><?= count($bayiler) ?> bayi</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead>
                <tr>
                    <th>Bayi Adı</th>
                    <th>Yetkili</th>
                    <th>Telefon</th>
                    <th>Cihaz</th>
                    <th>Entegre</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bayiler as $bayi): ?>
                <tr>
                    <td style="font-weight:600;"><?= html_temizle($bayi['unvan']) ?></td>
                    <td><?= html_temizle($bayi['yetkili'] ?? '—') ?></td>
                    <td style="font-family:var(--font-mono); font-size:12px;"><?= html_temizle($bayi['tel'] ?? '—') ?></td>
                    <td><span class="ss-badge ss-badge-bilgi"><?= $bayi['cihaz_sayisi'] ?> cihaz</span></td>
                    <td><span class="ss-badge ss-badge-mor"><?= $bayi['entegre_sayisi'] ?> entegre</span></td>
                    <td style="text-align:right;">
                        <button class="ss-btn ss-btn-ikincil ss-btn-sm"
                                onclick="bayiModalAc(<?= htmlspecialchars(json_encode([
                                    'id'       => $bayi['id'],
                                    'unvan'    => $bayi['unvan'],
                                    'yetkili'  => $bayi['yetkili'],
                                    'tel'      => $bayi['tel'],
                                    'entegreler' => $iliski_harita[$bayi['id']] ?? [],
                                ]), ENT_QUOTES) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="ss-btn ss-btn-tehlike ss-btn-sm ms-1"
                                onclick="SS.silmeOnayi('/pages/admin/bayiler.php?sil=<?= $bayi['id'] ?>&csrf=<?= $csrf ?>', '<?= html_temizle($bayi['unvan']) ?> bayisini silmek istiyor musunuz?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="bayiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bayiModalBaslik">Yeni Bayi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= html_temizle($csrf) ?>">
                <input type="hidden" name="islem" id="bayiIslem" value="ekle">
                <input type="hidden" name="bayi_id" id="bayiId">
                <div class="modal-body" style="padding:24px;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="ss-label">Bayi Unvanı *</label>
                            <input type="text" name="unvan" id="bayiUnvan" class="ss-input" required>
                        </div>
                        <div class="col-6">
                            <label class="ss-label">Yetkili Kişi</label>
                            <input type="text" name="yetkili" id="bayiYetkili" class="ss-input">
                        </div>
                        <div class="col-6">
                            <label class="ss-label">Telefon</label>
                            <input type="text" name="tel" id="bayiTel" class="ss-input">
                        </div>
                        <div class="col-12">
                            <label class="ss-label">Bağlı Entegre Firmalar</label>
                            <div style="display:flex; flex-direction:column; gap:8px; background:rgba(255,255,255,0.03); border:1px solid var(--renk-kenar); border-radius:10px; padding:12px;">
                                <?php foreach ($entegreler as $ent): ?>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
                                    <input type="checkbox" name="entegre_idler[]"
                                           value="<?= $ent['id'] ?>"
                                           class="entegre-checkbox"
                                           data-entegre-id="<?= $ent['id'] ?>"
                                           style="accent-color:var(--renk-aksan); width:16px; height:16px;">
                                    <?= html_temizle($ent['unvan']) ?>
                                </label>
                                <?php endforeach; ?>
                                <?php if (empty($entegreler)): ?>
                                    <div style="color:var(--renk-metin-soluk); font-size:12px;">Kayıtlı entegre firma yok.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ss-btn ss-btn-ikincil" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="ss-btn ss-btn-birincil">
                        <i class="bi bi-check-lg"></i> <span id="bayiFormBtn">Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$sayfa_js = "
function bayiModalAc(bayi = null) {
    // Checkboxları temizle
    document.querySelectorAll('.entegre-checkbox').forEach(cb => cb.checked = false);

    if (bayi) {
        document.getElementById('bayiModalBaslik').textContent = 'Bayiyi Düzenle';
        document.getElementById('bayiIslem').value = 'duzenle';
        document.getElementById('bayiId').value = bayi.id;
        document.getElementById('bayiUnvan').value = bayi.unvan;
        document.getElementById('bayiYetkili').value = bayi.yetkili || '';
        document.getElementById('bayiTel').value = bayi.tel || '';
        document.getElementById('bayiFormBtn').textContent = 'Güncelle';

        if (bayi.entegreler) {
            bayi.entegreler.forEach(eid => {
                const cb = document.querySelector('.entegre-checkbox[data-entegre-id=\"' + eid + '\"]');
                if (cb) cb.checked = true;
            });
        }
    } else {
        document.getElementById('bayiModalBaslik').textContent = 'Yeni Bayi';
        document.getElementById('bayiIslem').value = 'ekle';
        document.getElementById('bayiId').value = '';
        document.getElementById('bayiUnvan').value = '';
        document.getElementById('bayiYetkili').value = '';
        document.getElementById('bayiTel').value = '';
        document.getElementById('bayiFormBtn').textContent = 'Kaydet';
    }

    new bootstrap.Modal(document.getElementById('bayiModal')).show();
}
";
?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
