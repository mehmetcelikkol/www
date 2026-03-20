<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('entegre');

$aktif_sayfa   = 'dashboard';
$sayfa_basligi = 'Dashboard';

$entegre_id = oturum_bagli_id();

// Entegre firma bilgisi
$stmt = $pdo->prepare("SELECT * FROM entegre_firmalar WHERE id = ?");
$stmt->execute([$entegre_id]);
$entegre = $stmt->fetch();

// Bağlı bayiler
$stmt = $pdo->prepare("
    SELECT b.id, b.unvan, b.yetkili, b.tel,
           COUNT(c.id) AS cihaz_sayisi
    FROM bayi_entegre_iliski bei
    JOIN bayiler b ON b.id = bei.bayi_id
    LEFT JOIN cihazlar c ON c.sahip_bayi_id = b.id AND c.aktif_entegre_id = ?
    WHERE bei.entegre_id = ?
    GROUP BY b.id
    ORDER BY b.unvan
");
$stmt->execute([$entegre_id, $entegre_id]);
$baglibayiler = $stmt->fetchAll();

// Bu entegrenin cihazları
$cihazlar = kullanici_cihazlari_getir($pdo);

// İstatistikler
$istat = [
    'toplam_cihaz' => count($cihazlar),
    'bayi_sayisi'  => count($baglibayiler),
];

// Son okumaları ve dolulukları hesapla
$cihaz_ozeti = [];
foreach ($cihazlar as $cihaz) {
    $stmt2 = $pdo->prepare("SELECT agirlik_degeri, son_gorulme FROM cihaz_son_durum WHERE cihaz_kimligi = ?");
    $stmt2->execute([$cihaz['cihaz_kodu']]);
    $sd = $stmt2->fetch();
    $agirlik = (float)($sd['agirlik_degeri'] ?? 0);
    $yuzde   = doluluk_yuzde_hesapla($pdo, $cihaz['cihaz_kodu'], $agirlik);
    $cihaz_ozeti[] = [
        'cihaz'      => $cihaz,
        'agirlik'    => $agirlik,
        'yuzde'      => $yuzde,
        'renk'       => doluluk_rengi_hex($yuzde),
        'son_gorulme' => $sd['son_gorulme'] ?? null,
    ];
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Karşılama -->
<div class="ss-kart mb-4" style="background:linear-gradient(135deg,rgba(56,189,248,0.08),rgba(99,102,241,0.05)); border-color:rgba(56,189,248,0.2);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-size:22px; font-weight:700;">
                <?= html_temizle($entegre['unvan'] ?? 'Entegre Panel') ?>
            </div>
            <div style="color:var(--renk-metin-soluk); font-size:13px; margin-top:4px;">
                <i class="bi bi-building me-1"></i> Entegre Firma Paneli
            </div>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div class="ss-stat-kart" style="--stat-renk:#38bdf8; --stat-renk-bg:rgba(56,189,248,0.12); min-width:120px;">
                <div class="ss-stat-ikon" style="width:40px; height:40px;"><i class="bi bi-cpu-fill"></i></div>
                <div>
                    <div class="ss-stat-sayi" style="font-size:26px;"><?= $istat['toplam_cihaz'] ?></div>
                    <div class="ss-stat-etiket">Bağlı Cihaz</div>
                </div>
            </div>
            <div class="ss-stat-kart" style="--stat-renk:#6366f1; --stat-renk-bg:rgba(99,102,241,0.12); min-width:120px;">
                <div class="ss-stat-ikon" style="width:40px; height:40px;"><i class="bi bi-shop"></i></div>
                <div>
                    <div class="ss-stat-sayi" style="font-size:26px;"><?= $istat['bayi_sayisi'] ?></div>
                    <div class="ss-stat-etiket">Bayi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bağlı Bayiler -->
<div class="ss-kart mb-4">
    <div class="ss-kart-baslik">
        <i class="bi bi-shop" style="color:var(--renk-aksan)"></i>
        Bağlı Bayiler
    </div>
    <?php if (empty($baglibayiler)): ?>
        <div style="text-align:center; padding:30px; color:var(--renk-metin-soluk);">
            Henüz bağlı bayi bulunmuyor.
        </div>
    <?php else: ?>
    <div class="ss-grid-3">
        <?php foreach ($baglibayiler as $bayi): ?>
        <div class="ss-kart" style="padding:16px; cursor:pointer;" onclick="window.location='/pages/entegre/bayi_detay.php?id=<?= $bayi['id'] ?>'">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <div style="width:36px; height:36px; border-radius:50%; background:rgba(99,102,241,0.15); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:#a5b4fc;">
                    <?= strtoupper(substr($bayi['unvan'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:600; font-size:13.5px;"><?= html_temizle($bayi['unvan']) ?></div>
                    <div style="font-size:11px; color:var(--renk-metin-soluk);">
                        <?= html_temizle($bayi['yetkili'] ?? '—') ?>
                    </div>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--renk-metin-soluk);">
                <span><i class="bi bi-cpu me-1"></i><?= $bayi['cihaz_sayisi'] ?> cihaz</span>
                <?php if ($bayi['tel']): ?>
                <span><i class="bi bi-telephone me-1"></i><?= html_temizle($bayi['tel']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Cihaz Tablosu -->
<div class="ss-kart">
    <div class="ss-kart-baslik">
        <i class="bi bi-cpu-fill" style="color:var(--renk-aksan)"></i>
        Bağlı Cihazlar — Son Durumlar
        <span class="ss-badge ss-badge-bilgi ms-auto"><?= count($cihaz_ozeti) ?> cihaz</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="ss-tablo">
            <thead>
                <tr>
                    <th>Cihaz</th>
                    <th>Bayi</th>
                    <th>Ağırlık (kg)</th>
                    <th>Doluluk</th>
                    <th>Son Okuma</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cihaz_ozeti as $s): ?>
                <tr onclick="window.location='/pages/entegre/cihaz_detay.php?id=<?= $s['cihaz']['id'] ?>'" style="cursor:pointer;">
                    <td>
                        <div style="font-weight:600;"><?= html_temizle($s['cihaz']['cihaz_adi'] ?? '—') ?></div>
                        <div style="font-family:var(--font-mono); font-size:11px; color:var(--renk-metin-soluk);">
                            <?= html_temizle($s['cihaz']['cihaz_kodu']) ?>
                        </div>
                    </td>
                    <td><?= html_temizle($s['cihaz']['bayi_adi'] ?? '—') ?></td>
                    <td style="font-family:var(--font-mono); font-weight:600; color:<?= $s['renk'] ?>">
                        <?= number_format($s['agirlik'], 1) ?>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden; min-width:80px;">
                                <div style="height:100%; width:<?= $s['yuzde'] ?>%; background:<?= $s['renk'] ?>; border-radius:3px; transition:width 1s;"></div>
                            </div>
                            <span style="font-family:var(--font-mono); font-size:12px; color:<?= $s['renk'] ?>; min-width:40px;">
                                %<?= number_format($s['yuzde'], 1) ?>
                            </span>
                        </div>
                    </td>
                    <td style="font-size:12px; color:var(--renk-metin-soluk);">
                        <?= $s['son_gorulme'] ? date('d.m H:i', strtotime($s['son_gorulme'])) : 'Veri yok' ?>
                    </td>
                    <td>
                        <?php if ($s['cihaz']['aktif_mi']): ?>
                            <span class="ss-badge ss-badge-basari"><span class="silo-durum-nokta aktif"></span>Aktif</span>
                        <?php else: ?>
                            <span class="ss-badge ss-badge-tehlike">Pasif</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
