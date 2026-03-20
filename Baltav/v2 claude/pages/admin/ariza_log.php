<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa   = 'ariza_log';
$sayfa_basligi = 'Arıza Logları';

// Filtreler
$filtre_tip    = $_GET['tip']    ?? '';
$filtre_tarih  = $_GET['tarih'] ?? '';
$filtre_cihaz  = $_GET['cihaz'] ?? '';

$where_kosullari = ["1=1"];
$params = [];

if (!empty($filtre_tip)) {
    $where_kosullari[] = "ag.ariza_tipi = ?";
    $params[] = $filtre_tip;
}

if (!empty($filtre_tarih)) {
    $where_kosullari[] = "DATE(ag.olusturma_zamani) = ?";
    $params[] = $filtre_tarih;
}

if (!empty($filtre_cihaz)) {
    $where_kosullari[] = "(c.cihaz_kodu LIKE ? OR c.cihaz_adi LIKE ?)";
    $params[] = "%{$filtre_cihaz}%";
    $params[] = "%{$filtre_cihaz}%";
}

$where_str = implode(' AND ', $where_kosullari);

$stmt = $pdo->prepare("
    SELECT ag.*, c.cihaz_kodu, c.cihaz_adi, b.unvan AS bayi_adi
    FROM ariza_gecmisi ag
    JOIN cihazlar c ON c.id = ag.cihaz_id
    LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
    WHERE $where_str
    ORDER BY ag.olusturma_zamani DESC
    LIMIT 200
");
$stmt->execute($params);
$arizalar = $stmt->fetchAll();

// Arıza tipleri (renk haritası)
$ariza_renkleri = [
    'CIHAZ_RESET'       => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#fcd34d'],
    'HABERLESME_HATASI' => ['bg' => 'rgba(239,68,68,0.15)',  'color' => '#fca5a5'],
    'BAGLANTI_KOPTU'    => ['bg' => 'rgba(239,68,68,0.15)',  'color' => '#fca5a5'],
    'ASIRI_YUK'         => ['bg' => 'rgba(139,92,246,0.15)', 'color' => '#c4b5fd'],
];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Filtreler -->
<div class="ss-kart mb-4">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div style="flex:1; min-width:140px;">
            <label class="ss-label">Arıza Tipi</label>
            <select name="tip" class="ss-select">
                <option value="">Tümü</option>
                <option value="CIHAZ_RESET"       <?= $filtre_tip==='CIHAZ_RESET'?'selected':'' ?>>Cihaz Reset</option>
                <option value="HABERLESME_HATASI" <?= $filtre_tip==='HABERLESME_HATASI'?'selected':'' ?>>Haberleşme Hatası</option>
                <option value="BAGLANTI_KOPTU"    <?= $filtre_tip==='BAGLANTI_KOPTU'?'selected':'' ?>>Bağlantı Koptu</option>
                <option value="ASIRI_YUK"         <?= $filtre_tip==='ASIRI_YUK'?'selected':'' ?>>Aşırı Yük</option>
            </select>
        </div>
        <div style="flex:1; min-width:140px;">
            <label class="ss-label">Tarih</label>
            <input type="date" name="tarih" value="<?= html_temizle($filtre_tarih) ?>" class="ss-input">
        </div>
        <div style="flex:2; min-width:200px;">
            <label class="ss-label">Cihaz Ara</label>
            <input type="text" name="cihaz" value="<?= html_temizle($filtre_cihaz) ?>" class="ss-input" placeholder="Kod veya isim...">
        </div>
        <button type="submit" class="ss-btn ss-btn-birincil" style="height:41px;">
            <i class="bi bi-search"></i> Filtrele
        </button>
        <a href="/pages/admin/ariza_log.php" class="ss-btn ss-btn-ikincil" style="height:41px;">
            <i class="bi bi-x-lg"></i> Temizle
        </a>
    </form>
</div>

<!-- Log Tablosu -->
<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>
        Arıza Kayıtları
        <span class="ss-badge ss-badge-tehlike ms-auto"><?= count($arizalar) ?> kayıt</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead><tr>
                <th>Zaman</th>
                <th>Cihaz</th>
                <th>Bayi</th>
                <th>Arıza Tipi</th>
                <th>Açıklama</th>
            </tr></thead>
            <tbody>
            <?php if (empty($arizalar)): ?>
                <tr><td colspan="5" style="text-align:center; padding:40px; color:var(--renk-metin-soluk);">
                    <i class="bi bi-check-circle" style="font-size:32px; color:#22c55e; display:block; margin-bottom:8px;"></i>
                    Seçilen filtrelere uygun arıza kaydı yok.
                </td></tr>
            <?php else: ?>
            <?php foreach ($arizalar as $ariza):
                $stil = $ariza_renkleri[$ariza['ariza_tipi']] ?? ['bg'=>'rgba(255,255,255,0.05)','color'=>'#fff'];
            ?>
            <tr>
                <td style="font-family:var(--font-mono); font-size:12px; white-space:nowrap;">
                    <?= date('d.m.Y H:i:s', strtotime($ariza['olusturma_zamani'])) ?>
                </td>
                <td>
                    <div style="font-weight:600;"><?= html_temizle($ariza['cihaz_adi'] ?? '—') ?></div>
                    <div style="font-family:var(--font-mono); font-size:11px; color:var(--renk-metin-soluk);">
                        <?= html_temizle($ariza['cihaz_kodu']) ?>
                    </div>
                </td>
                <td style="font-size:13px;"><?= html_temizle($ariza['bayi_adi'] ?? '—') ?></td>
                <td>
                    <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; background:<?= $stil['bg'] ?>; color:<?= $stil['color'] ?>;">
                        <?= html_temizle($ariza['ariza_tipi']) ?>
                    </span>
                </td>
                <td style="font-size:12px; color:var(--renk-metin-soluk);">
                    <?= html_temizle($ariza['aciklama'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
