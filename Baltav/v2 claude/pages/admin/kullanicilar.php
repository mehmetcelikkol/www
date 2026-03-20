<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa   = 'kullanicilar';
$sayfa_basligi = 'Kullanıcı Yönetimi';

// ---- Silme ----
if (isset($_GET['sil']) && isset($_GET['csrf'])) {
    if (!csrf_dogrula($_GET['csrf'])) {
        flash_mesaj_ekle('danger', 'Güvenlik hatası.');
    } else {
        $sil_id = (int)$_GET['sil'];
        if ($sil_id === oturum_kullanici_id()) {
            flash_mesaj_ekle('danger', 'Kendinizi silemezsiniz.');
        } else {
            $pdo->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$sil_id]);
            flash_mesaj_ekle('success', 'Kullanıcı silindi.');
        }
    }
    header('Location: /pages/admin/kullanicilar.php');
    exit;
}

// ---- Ekleme / Düzenleme POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_dogrula($_POST['csrf_token'] ?? '')) {
        flash_mesaj_ekle('danger', 'Güvenlik doğrulaması başarısız.');
    } else {
        $islem    = $_POST['islem'] ?? '';
        $ad_soyad = trim($_POST['ad_soyad'] ?? '');
        $eposta   = trim($_POST['eposta'] ?? '');
        $rol      = $_POST['rol'] ?? 'bayi';
        $bagli_id = !empty($_POST['bagli_id']) ? (int)$_POST['bagli_id'] : null;
        $sifre    = $_POST['sifre'] ?? '';

        if (empty($ad_soyad) || empty($eposta) || !in_array($rol, ['admin','bayi','entegre'])) {
            flash_mesaj_ekle('danger', 'Zorunlu alanları doldurun.');
        } else {
            if ($islem === 'ekle') {
                if (empty($sifre) || strlen($sifre) < 6) {
                    flash_mesaj_ekle('danger', 'Şifre en az 6 karakter olmalıdır.');
                } else {
                    $hash = password_hash($sifre, PASSWORD_BCRYPT);
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol, bagli_id)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$ad_soyad, $eposta, $hash, $rol, $bagli_id]);
                        flash_mesaj_ekle('success', "'{$ad_soyad}' kullanıcısı oluşturuldu.");
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            flash_mesaj_ekle('danger', 'Bu e-posta adresi zaten kayıtlı.');
                        } else {
                            flash_mesaj_ekle('danger', 'Bir hata oluştu.');
                        }
                    }
                }
            } elseif ($islem === 'duzenle') {
                $kull_id = (int)($_POST['kullanici_id'] ?? 0);
                $sql = "UPDATE kullanicilar SET ad_soyad=?, eposta=?, rol=?, bagli_id=? WHERE id=?";
                $params = [$ad_soyad, $eposta, $rol, $bagli_id, $kull_id];
                if (!empty($sifre) && strlen($sifre) >= 6) {
                    $sql = "UPDATE kullanicilar SET ad_soyad=?, eposta=?, sifre_hash=?, rol=?, bagli_id=? WHERE id=?";
                    $params = [$ad_soyad, $eposta, password_hash($sifre, PASSWORD_BCRYPT), $rol, $bagli_id, $kull_id];
                }
                try {
                    $pdo->prepare($sql)->execute($params);
                    flash_mesaj_ekle('success', 'Kullanıcı güncellendi.');
                } catch (PDOException $e) {
                    flash_mesaj_ekle('danger', 'E-posta zaten kullanımda.');
                }
            }
        }
    }
    header('Location: /pages/admin/kullanicilar.php');
    exit;
}

// ---- Kullanıcı Listesi ----
$kullanicilar = $pdo->query("
    SELECT k.*,
           COALESCE(b.unvan, e.unvan) AS firma_adi
    FROM kullanicilar k
    LEFT JOIN bayiler b ON k.rol='bayi' AND b.id = k.bagli_id
    LEFT JOIN entegre_firmalar e ON k.rol='entegre' AND e.id = k.bagli_id
    ORDER BY k.created_at DESC
")->fetchAll();

$bayiler   = $pdo->query("SELECT id, unvan FROM bayiler ORDER BY unvan")->fetchAll();
$entegreler = $pdo->query("SELECT id, unvan FROM entegre_firmalar ORDER BY unvan")->fetchAll();

$csrf = csrf_token_olustur();
include __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div></div>
    <button class="ss-btn ss-btn-birincil" onclick="kullanicıModalAc()">
        <i class="bi bi-person-plus-fill"></i> Yeni Kullanıcı
    </button>
</div>

<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-people-fill" style="color:var(--renk-aksan)"></i>
        Tüm Kullanıcılar
        <span class="ss-badge ss-badge-mor ms-auto"><?= count($kullanicilar) ?> kullanıcı</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>E-Posta</th>
                    <th>Rol</th>
                    <th>Bağlı Firma</th>
                    <th>Kayıt Tarihi</th>
                    <th style="text-align:right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kullanicilar as $k): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:32px; height:32px; border-radius:50%; background:rgba(99,102,241,0.15); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#a5b4fc; flex-shrink:0;">
                                <?= strtoupper(substr($k['ad_soyad'], 0, 1)) ?>
                            </div>
                            <div style="font-weight:600;"><?= html_temizle($k['ad_soyad']) ?></div>
                        </div>
                    </td>
                    <td style="font-family:var(--font-mono); font-size:12px; color:var(--renk-metin-soluk);">
                        <?= html_temizle($k['eposta']) ?>
                    </td>
                    <td>
                        <span class="ss-user-rol ss-rol-<?= $k['rol'] ?>">
                            <?= strtoupper($k['rol']) ?>
                        </span>
                    </td>
                    <td style="font-size:13px;"><?= html_temizle($k['firma_adi'] ?? '—') ?></td>
                    <td style="font-size:12px; color:var(--renk-metin-soluk);">
                        <?= date('d.m.Y', strtotime($k['created_at'])) ?>
                    </td>
                    <td style="text-align:right;">
                        <button class="ss-btn ss-btn-ikincil ss-btn-sm"
                                onclick="kullanicıModalAc(<?= htmlspecialchars(json_encode([
                                    'id'       => $k['id'],
                                    'ad_soyad' => $k['ad_soyad'],
                                    'eposta'   => $k['eposta'],
                                    'rol'      => $k['rol'],
                                    'bagli_id' => $k['bagli_id'],
                                ]), ENT_QUOTES) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($k['id'] !== oturum_kullanici_id()): ?>
                        <button class="ss-btn ss-btn-tehlike ss-btn-sm ms-1"
                                onclick="SS.silmeOnayi('/pages/admin/kullanicilar.php?sil=<?= $k['id'] ?>&csrf=<?= $csrf ?>', '<?= html_temizle($k['ad_soyad']) ?> kullanıcısını silmek istiyor musunuz?')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="kullanicıModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBaslik">Yeni Kullanıcı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/pages/admin/kullanicilar.php">
                <input type="hidden" name="csrf_token" value="<?= html_temizle($csrf) ?>">
                <input type="hidden" name="islem" id="formIslem" value="ekle">
                <input type="hidden" name="kullanici_id" id="formKullaniciId">
                <div class="modal-body" style="padding:24px;">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="ss-label">Ad Soyad</label>
                            <input type="text" name="ad_soyad" id="formAdSoyad" class="ss-input" required>
                        </div>
                        <div class="col-12">
                            <label class="ss-label">E-Posta</label>
                            <input type="email" name="eposta" id="formEposta" class="ss-input" required>
                        </div>
                        <div class="col-12">
                            <label class="ss-label">Şifre <span id="sifreZorunlu" style="color:var(--renk-tehlike);">*</span></label>
                            <input type="password" name="sifre" id="formSifre" class="ss-input" placeholder="Değiştirmek istemiyorsanız boş bırakın">
                        </div>
                        <div class="col-6">
                            <label class="ss-label">Rol</label>
                            <select name="rol" id="formRol" class="ss-select" onchange="rolDegisince(this.value)">
                                <option value="admin">Admin</option>
                                <option value="bayi">Bayi</option>
                                <option value="entegre">Entegre</option>
                            </select>
                        </div>
                        <div class="col-6" id="firmaSecContainer">
                            <label class="ss-label" id="firmaSecLabel">Bayi Seç</label>
                            <select name="bagli_id" id="formBagliId" class="ss-select">
                                <option value="">— Seçin —</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ss-btn ss-btn-ikincil" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="ss-btn ss-btn-birincil">
                        <i class="bi bi-check-lg"></i> <span id="formBtn">Oluştur</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$bayiler_json   = json_encode($bayiler);
$entegreler_json = json_encode($entegreler);
$sayfa_js = "
const bayilerData   = $bayiler_json;
const entegrelerData = $entegreler_json;

function rolDegisince(rol, secilenId = null) {
    const container = document.getElementById('firmaSecContainer');
    const label     = document.getElementById('firmaSecLabel');
    const select    = document.getElementById('formBagliId');

    if (rol === 'admin') {
        container.style.display = 'none';
        return;
    }

    container.style.display = '';
    const liste = rol === 'bayi' ? bayilerData : entegrelerData;
    label.textContent = rol === 'bayi' ? 'Bayi Seç' : 'Entegre Firma';

    select.innerHTML = '<option value=\"\">— Seçin —</option>';
    liste.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.unvan;
        if (secilenId && parseInt(secilenId) === item.id) opt.selected = true;
        select.appendChild(opt);
    });
}

function kullanicıModalAc(kullanici = null) {
    const modal = new bootstrap.Modal(document.getElementById('kullanicıModal'));

    if (kullanici) {
        document.getElementById('modalBaslik').textContent = 'Kullanıcıyı Düzenle';
        document.getElementById('formIslem').value = 'duzenle';
        document.getElementById('formKullaniciId').value = kullanici.id;
        document.getElementById('formAdSoyad').value = kullanici.ad_soyad;
        document.getElementById('formEposta').value = kullanici.eposta;
        document.getElementById('formSifre').placeholder = 'Değiştirmek istemiyorsanız boş bırakın';
        document.getElementById('sifreZorunlu').style.display = 'none';
        document.getElementById('formBtn').textContent = 'Güncelle';
        document.getElementById('formRol').value = kullanici.rol;
        rolDegisince(kullanici.rol, kullanici.bagli_id);
    } else {
        document.getElementById('modalBaslik').textContent = 'Yeni Kullanıcı';
        document.getElementById('formIslem').value = 'ekle';
        document.getElementById('formKullaniciId').value = '';
        document.getElementById('formAdSoyad').value = '';
        document.getElementById('formEposta').value = '';
        document.getElementById('formSifre').placeholder = '••••••••';
        document.getElementById('sifreZorunlu').style.display = '';
        document.getElementById('formBtn').textContent = 'Oluştur';
        document.getElementById('formRol').value = 'bayi';
        rolDegisince('bayi');
    }

    modal.show();
}

// Sayfa açılışında bayi seçeneği göster
rolDegisince('bayi');
";
?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
