<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa   = 'cihaz_ata';
$sayfa_basligi = 'Cihaz Atama';

// ---- POST İşlemleri ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_dogrula($_POST['csrf_token'] ?? '')) {
        flash_mesaj_ekle('danger', 'Güvenlik doğrulaması başarısız.');
    } else {
        $islem     = $_POST['islem'] ?? '';
        $cihaz_id  = (int)($_POST['cihaz_id'] ?? 0);
        $bayi_id   = !empty($_POST['bayi_id']) ? (int)$_POST['bayi_id'] : null;
        $entegre_id = !empty($_POST['entegre_id']) ? (int)$_POST['entegre_id'] : null;

        if ($islem === 'ata' && $cihaz_id > 0) {
            // Eğer bayi_id verilmişse, entegre_id de o bayinin entegreleri arasında mı diye kontrol et
            if ($bayi_id && $entegre_id) {
                $kontrol = $pdo->prepare("
                    SELECT COUNT(*) FROM bayi_entegre_iliski
                    WHERE bayi_id = ? AND entegre_id = ?
                ");
                $kontrol->execute([$bayi_id, $entegre_id]);
                if (!$kontrol->fetchColumn()) {
                    flash_mesaj_ekle('warning', 'Uyarı: Seçilen entegre firma bu bayiye bağlı değil. Yine de atama yapıldı.');
                }
            }

            $stmt = $pdo->prepare("
                UPDATE cihazlar
                SET sahip_bayi_id = ?, aktif_entegre_id = ?, guncelleme_tarihi = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$bayi_id, $entegre_id, $cihaz_id]);
            flash_mesaj_ekle('success', 'Cihaz ataması başarıyla güncellendi.');
        }

        elseif ($islem === 'kaldir' && $cihaz_id > 0) {
            $alan = $_POST['alan'] ?? '';
            if ($alan === 'bayi') {
                $pdo->prepare("UPDATE cihazlar SET sahip_bayi_id = NULL, aktif_entegre_id = NULL WHERE id = ?")->execute([$cihaz_id]);
                flash_mesaj_ekle('success', 'Bayi ve entegre ataması kaldırıldı.');
            } elseif ($alan === 'entegre') {
                $pdo->prepare("UPDATE cihazlar SET aktif_entegre_id = NULL WHERE id = ?")->execute([$cihaz_id]);
                flash_mesaj_ekle('success', 'Entegre ataması kaldırıldı.');
            }
        }
    }
    header('Location: /pages/admin/cihaz_ata.php');
    exit;
}

// ---- Verileri Çek ----
$cihazlar = $pdo->query("
    SELECT c.*, b.unvan AS bayi_adi, e.unvan AS entegre_adi
    FROM cihazlar c
    LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
    LEFT JOIN entegre_firmalar e ON e.id = c.aktif_entegre_id
    ORDER BY c.cihaz_adi, c.cihaz_kodu
")->fetchAll();

$bayiler   = $pdo->query("SELECT id, unvan FROM bayiler ORDER BY unvan")->fetchAll();
$entegreler = $pdo->query("SELECT id, unvan FROM entegre_firmalar ORDER BY unvan")->fetchAll();

// Bayi - Entegre ilişki haritası (JS için JSON)
$bayi_entegre_haritasi = [];
$iliski = $pdo->query("SELECT bayi_id, entegre_id FROM bayi_entegre_iliski")->fetchAll();
foreach ($iliski as $row) {
    $bayi_entegre_haritasi[$row['bayi_id']][] = $row['entegre_id'];
}

$csrf = csrf_token_olustur();
include __DIR__ . '/../../includes/header.php';
?>

<!-- Açıklama Bandı -->
<div class="ss-kart mb-4" style="border-color:rgba(99,102,241,0.25); background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(139,92,246,0.04));">
    <div style="display:flex; align-items:center; gap:12px;">
        <i class="bi bi-diagram-3-fill" style="font-size:28px; color:var(--renk-aksan);"></i>
        <div>
            <div style="font-weight:600; font-size:15px;">Cihaz Atama Yönetimi</div>
            <div style="color:var(--renk-metin-soluk); font-size:13px; margin-top:2px;">
                Her cihaz aynı anda yalnızca <strong style="color:#a5b4fc;">bir bayiye</strong> ve
                <strong style="color:#a5b4fc;">bir entegre firmaya</strong> atanabilir.
                Bayi seçilince o bayinin entegreleri otomatik filtrelenir.
            </div>
        </div>
    </div>
</div>

<!-- Cihaz Tablosu -->
<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-cpu-fill" style="color:var(--renk-aksan)"></i>
        Tüm Cihazlar — Atama Durumu
        <span class="ss-badge ss-badge-mor ms-auto"><?= count($cihazlar) ?> cihaz</span>
    </div>

    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead>
                <tr>
                    <th>Cihaz</th>
                    <th>Konum</th>
                    <th>Atanan Bayi</th>
                    <th>Aktif Entegre</th>
                    <th>Durum</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cihazlar as $cihaz): ?>
                <tr>
                    <td>
                        <div style="font-weight:600; font-size:13.5px;">
                            <?= html_temizle($cihaz['cihaz_adi'] ?? '—') ?>
                        </div>
                        <div style="font-family:var(--font-mono); font-size:11px; color:var(--renk-metin-soluk); margin-top:2px;">
                            <?= html_temizle($cihaz['cihaz_kodu']) ?>
                        </div>
                    </td>
                    <td style="font-size:13px; color:var(--renk-metin-soluk);">
                        <?= html_temizle($cihaz['konum'] ?? '—') ?>
                    </td>
                    <td>
                        <?php if ($cihaz['bayi_adi']): ?>
                            <span class="ss-badge ss-badge-basari">
                                <i class="bi bi-shop"></i>
                                <?= html_temizle($cihaz['bayi_adi']) ?>
                            </span>
                        <?php else: ?>
                            <span class="ss-badge ss-badge-tehlike">Atanmamış</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($cihaz['entegre_adi']): ?>
                            <span class="ss-badge ss-badge-bilgi">
                                <i class="bi bi-building"></i>
                                <?= html_temizle($cihaz['entegre_adi']) ?>
                            </span>
                        <?php else: ?>
                            <span class="ss-badge ss-badge-uyari">Entegre Yok</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($cihaz['aktif_mi']): ?>
                            <span class="ss-durum-aktif"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Aktif</span>
                        <?php else: ?>
                            <span class="ss-durum-pasif"><i class="bi bi-circle-fill" style="font-size:8px;"></i> Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <button class="ss-btn ss-btn-birincil ss-btn-sm"
                                onclick="atamaModalAc(<?= htmlspecialchars(json_encode([
                                    'id'          => $cihaz['id'],
                                    'adi'         => $cihaz['cihaz_adi'] ?? $cihaz['cihaz_kodu'],
                                    'kod'         => $cihaz['cihaz_kodu'],
                                    'bayi_id'     => $cihaz['sahip_bayi_id'],
                                    'entegre_id'  => $cihaz['aktif_entegre_id'],
                                ]), ENT_QUOTES) ?>)">
                            <i class="bi bi-pencil-fill"></i> Ata
                        </button>
                        <?php if ($cihaz['sahip_bayi_id']): ?>
                        <button class="ss-btn ss-btn-tehlike ss-btn-sm ms-1"
                                onclick="SS.silmeOnayi(
                                    '/pages/admin/cihaz_ata.php?islem=kaldir&cihaz_id=<?= $cihaz['id'] ?>&alan=bayi&csrf=<?= $csrf ?>',
                                    'Bu cihazın tüm atamalarını kaldırmak istediğinizden emin misiniz?'
                                )">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ATAMA MODAL -->
<div class="modal fade" id="atamaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-diagram-3-fill me-2" style="color:var(--renk-aksan)"></i>
                    Cihaz Atama
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/pages/admin/cihaz_ata.php">
                <input type="hidden" name="csrf_token" value="<?= html_temizle($csrf) ?>">
                <input type="hidden" name="islem" value="ata">
                <input type="hidden" name="cihaz_id" id="modal_cihaz_id">

                <div class="modal-body" style="padding:24px;">
                    <div id="modal_cihaz_bilgi" style="background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.15); border-radius:10px; padding:12px 16px; margin-bottom:20px;">
                        <div style="font-weight:600;" id="modal_cihaz_adi"></div>
                        <div style="font-family:var(--font-mono); font-size:11px; color:var(--renk-metin-soluk);" id="modal_cihaz_kod"></div>
                    </div>

                    <div class="ss-form-grup">
                        <label class="ss-label">Bayi Seç</label>
                        <select name="bayi_id" id="modal_bayi_select" class="ss-select" onchange="bayiDegisince(this.value)">
                            <option value="">— Atamasız Bırak —</option>
                            <?php foreach ($bayiler as $bayi): ?>
                                <option value="<?= $bayi['id'] ?>"><?= html_temizle($bayi['unvan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ss-form-grup">
                        <label class="ss-label">Entegre Firma Seç</label>
                        <select name="entegre_id" id="modal_entegre_select" class="ss-select">
                            <option value="">— Entegresiz Bırak —</option>
                            <?php foreach ($entegreler as $entegre): ?>
                                <option value="<?= $entegre['id'] ?>"
                                        data-entegre-id="<?= $entegre['id'] ?>">
                                    <?= html_temizle($entegre['unvan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size:11px; color:var(--renk-metin-soluk); margin-top:6px;">
                            <i class="bi bi-info-circle"></i>
                            Bayi seçildiğinde yalnızca o bayinin bağlı entegreleri listelenir.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="ss-btn ss-btn-ikincil" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="ss-btn ss-btn-birincil">
                        <i class="bi bi-check-lg"></i> Atamayı Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Kaldırma işlemi GET parametresiyle geldiyse
if ($_GET['islem'] ?? '' === 'kaldir') {
    $cihaz_id_g = (int)($_GET['cihaz_id'] ?? 0);
    $alan_g     = $_GET['alan'] ?? '';
    $csrf_g     = $_GET['csrf'] ?? '';

    if (csrf_dogrula($csrf_g) && $cihaz_id_g > 0) {
        if ($alan_g === 'bayi') {
            $pdo->prepare("UPDATE cihazlar SET sahip_bayi_id = NULL, aktif_entegre_id = NULL WHERE id = ?")->execute([$cihaz_id_g]);
        } elseif ($alan_g === 'entegre') {
            $pdo->prepare("UPDATE cihazlar SET aktif_entegre_id = NULL WHERE id = ?")->execute([$cihaz_id_g]);
        }
    }
    header('Location: /pages/admin/cihaz_ata.php');
    exit;
}

$sayfa_js = "
const bayiEntegreler = " . json_encode($bayi_entegre_haritasi) . ";
const tumEntegreler  = " . json_encode(array_column($entegreler, 'unvan', 'id')) . ";

function atamaModalAc(cihaz) {
    document.getElementById('modal_cihaz_id').value  = cihaz.id;
    document.getElementById('modal_cihaz_adi').textContent = cihaz.adi;
    document.getElementById('modal_cihaz_kod').textContent = cihaz.kod;

    const bayiSel    = document.getElementById('modal_bayi_select');
    const entegreSel = document.getElementById('modal_entegre_select');

    bayiSel.value = cihaz.bayi_id || '';
    bayiDegisince(cihaz.bayi_id || '', cihaz.entegre_id);

    new bootstrap.Modal(document.getElementById('atamaModal')).show();
}

function bayiDegisince(bayiId, seciliEntegre = null) {
    const entegreSel = document.getElementById('modal_entegre_select');
    const izinliEntegreler = bayiEntegreler[bayiId] || null;

    // Tüm seçenekleri sıfırla
    Array.from(entegreSel.options).forEach(opt => {
        if (!opt.value) return; // Boş seçeneği atla
        if (izinliEntegreler === null) {
            // Bayi seçilmedi: tümünü göster
            opt.style.display = '';
        } else {
            // Sadece bu bayinin entegrelerini göster
            opt.style.display = izinliEntegreler.includes(parseInt(opt.value)) ? '' : 'none';
        }
    });

    // Önceki değeri koru ya da sıfırla
    if (seciliEntegre) {
        entegreSel.value = seciliEntegre;
    } else if (izinliEntegreler !== null) {
        // Seçili olan artık görünmüyorsa sıfırla
        const secilenGorulebilir = izinliEntegreler.includes(parseInt(entegreSel.value));
        if (!secilenGorulebilir) entegreSel.value = '';
    }
}
";
?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
