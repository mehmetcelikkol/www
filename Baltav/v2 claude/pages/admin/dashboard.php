<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('admin');

$aktif_sayfa  = 'dashboard';
$sayfa_basligi = 'Dashboard';

// ---- İstatistikler ----
$istat = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM cihazlar");
$istat['toplam_cihaz'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM cihazlar WHERE aktif_mi = 1");
$istat['aktif_cihaz'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM bayiler");
$istat['toplam_bayi'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM entegre_firmalar");
$istat['toplam_entegre'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM kullanicilar");
$istat['toplam_kullanici'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM ariza_gecmisi WHERE DATE(olusturma_zamani) = CURDATE()");
$istat['bugun_ariza'] = $stmt->fetchColumn();

// ---- Son Aktif Cihazlar (Son durumları ile) ----
$stmt = $pdo->query("
    SELECT c.id, c.cihaz_kodu, c.cihaz_adi, c.konum, c.aktif_mi,
           b.unvan AS bayi_adi, e.unvan AS entegre_adi,
           sd.agirlik_degeri, sd.son_gorulme, sd.stabil_mi
    FROM cihazlar c
    LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
    LEFT JOIN entegre_firmalar e ON e.id = c.aktif_entegre_id
    LEFT JOIN cihaz_son_durum sd ON sd.cihaz_kimligi = c.cihaz_kodu
    ORDER BY sd.son_gorulme DESC
    LIMIT 12
");
$son_cihazlar = $stmt->fetchAll();

// ---- Son Arıza Logları ----
$stmt = $pdo->query("
    SELECT ag.ariza_tipi, ag.aciklama, ag.olusturma_zamani,
           c.cihaz_kodu, c.cihaz_adi
    FROM ariza_gecmisi ag
    JOIN cihazlar c ON c.id = ag.cihaz_id
    ORDER BY ag.olusturma_zamani DESC
    LIMIT 5
");
$son_arizalar = $stmt->fetchAll();

// ---- Cihaz Limitleri ile Doluluk Hesaplama ----
$silo_verileri = [];
foreach ($son_cihazlar as $cihaz) {
    $yuzde = doluluk_yuzde_hesapla($pdo, $cihaz['cihaz_kodu'], (float)($cihaz['agirlik_degeri'] ?? 0));
    $renk  = doluluk_rengi_hex($yuzde);
    $silo_verileri[$cihaz['cihaz_kodu']] = ['yuzde' => $yuzde, 'renk' => $renk];
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- ===== İSTATİSTİK KARTLARI ===== -->
<div class="ss-grid-4 mb-4">

    <div class="ss-stat-kart" style="--stat-renk:#6366f1; --stat-renk-bg:rgba(99,102,241,0.12);">
        <div class="ss-stat-ikon"><i class="bi bi-cpu-fill"></i></div>
        <div>
            <div class="ss-stat-sayi"><?= number_format($istat['toplam_cihaz']) ?></div>
            <div class="ss-stat-etiket">Toplam Cihaz</div>
        </div>
    </div>

    <div class="ss-stat-kart" style="--stat-renk:#22c55e; --stat-renk-bg:rgba(34,197,94,0.12);">
        <div class="ss-stat-ikon"><i class="bi bi-activity"></i></div>
        <div>
            <div class="ss-stat-sayi"><?= number_format($istat['aktif_cihaz']) ?></div>
            <div class="ss-stat-etiket">Aktif Cihaz</div>
        </div>
    </div>

    <div class="ss-stat-kart" style="--stat-renk:#38bdf8; --stat-renk-bg:rgba(56,189,248,0.12);">
        <div class="ss-stat-ikon"><i class="bi bi-shop"></i></div>
        <div>
            <div class="ss-stat-sayi"><?= number_format($istat['toplam_bayi']) ?></div>
            <div class="ss-stat-etiket">Kayıtlı Bayi</div>
        </div>
    </div>

    <div class="ss-stat-kart" style="--stat-renk:<?= $istat['bugun_ariza'] > 0 ? '#ef4444' : '#f59e0b' ?>; --stat-renk-bg:rgba(<?= $istat['bugun_ariza'] > 0 ? '239,68,68' : '245,158,11' ?>,0.12);">
        <div class="ss-stat-ikon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="ss-stat-sayi"><?= number_format($istat['bugun_ariza']) ?></div>
            <div class="ss-stat-etiket">Bugünkü Arızalar</div>
        </div>
    </div>

</div>

<!-- ===== SİLO KARTLARI ===== -->
<div class="ss-kart mb-4">
    <div class="ss-kart-baslik">
        <i class="bi bi-archive-fill" style="color:var(--renk-aksan)"></i>
        Silo Doluluk Durumları
        <span class="ss-badge ss-badge-mor ms-auto"><?= count($son_cihazlar) ?> cihaz</span>
    </div>

    <?php if (empty($son_cihazlar)): ?>
        <div style="text-align:center; padding:40px; color:var(--renk-metin-soluk);">
            <i class="bi bi-inbox" style="font-size:40px; display:block; margin-bottom:12px;"></i>
            Henüz kayıtlı cihaz bulunmuyor.
        </div>
    <?php else: ?>
    <div class="ss-grid-silo">
        <?php foreach ($son_cihazlar as $cihaz):
            $agirlik = (float)($cihaz['agirlik_degeri'] ?? 0);
            $yuzde   = $silo_verileri[$cihaz['cihaz_kodu']]['yuzde'] ?? 0;
            $renk    = $silo_verileri[$cihaz['cihaz_kodu']]['renk'] ?? '#6366f1';
            $aktif   = $cihaz['aktif_mi'] ? 'aktif' : 'pasif';
            $son_gorulme = $cihaz['son_gorulme']
                ? date('d.m H:i', strtotime($cihaz['son_gorulme']))
                : 'Veri yok';
        ?>
        <div class="silo-kart" onclick="window.location='/pages/admin/cihaz_detay.php?id=<?= $cihaz['id'] ?>'">
            <div class="silo-kart-adi"><?= html_temizle($cihaz['cihaz_adi'] ?? $cihaz['cihaz_kodu']) ?></div>
            <div class="silo-kart-kod">
                <span class="silo-durum-nokta <?= $aktif ?>"></span>
                <?= html_temizle($cihaz['cihaz_kodu']) ?>
            </div>

            <!-- ANİMASYONLU SİLO SVG -->
            <div class="silo-svg-container"
                 id="silo_<?= $cihaz['id'] ?>"
                 data-yuzde="<?= $yuzde ?>"
                 data-renk="<?= $renk ?>">
            </div>

            <!-- Yüzde Badge -->
            <div style="font-size:22px; font-weight:700; font-family:var(--font-mono); color:<?= $renk ?>; margin:-8px 0 12px;">
                %<?= number_format($yuzde, 1) ?>
            </div>

            <!-- Bilgiler -->
            <div style="font-size:12px;">
                <div class="silo-bilgi-satir">
                    <span class="silo-bilgi-etiket"><i class="bi bi-speedometer2 me-1"></i>Ağırlık</span>
                    <span class="silo-bilgi-deger" style="color:<?= $renk ?>">
                        <?= number_format($agirlik, 1) ?> kg
                    </span>
                </div>
                <div class="silo-bilgi-satir">
                    <span class="silo-bilgi-etiket"><i class="bi bi-shop me-1"></i>Bayi</span>
                    <span class="silo-bilgi-deger" style="max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= html_temizle($cihaz['bayi_adi'] ?? '—') ?>
                    </span>
                </div>
                <div class="silo-bilgi-satir">
                    <span class="silo-bilgi-etiket"><i class="bi bi-clock me-1"></i>Son Görülme</span>
                    <span class="silo-bilgi-deger"><?= $son_gorulme ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ===== ALT BÖLÜM: Hızlı Erişim + Arıza Logları ===== -->
<div class="ss-grid-2">

    <!-- Hızlı Erişim -->
    <div class="ss-kart">
        <div class="ss-kart-baslik">
            <i class="bi bi-lightning-fill" style="color:#f59e0b"></i>
            Hızlı İşlemler
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="/pages/admin/cihaz_ata.php" class="ss-btn ss-btn-birincil">
                <i class="bi bi-diagram-3-fill"></i> Cihaz Ata / Düzenle
            </a>
            <a href="/pages/admin/kullanicilar.php?yeni=1" class="ss-btn ss-btn-ikincil">
                <i class="bi bi-person-plus-fill"></i> Yeni Kullanıcı Ekle
            </a>
            <a href="/pages/admin/bayiler.php?yeni=1" class="ss-btn ss-btn-ikincil">
                <i class="bi bi-shop"></i> Yeni Bayi Ekle
            </a>
            <a href="/pages/admin/entegreler.php?yeni=1" class="ss-btn ss-btn-ikincil">
                <i class="bi bi-building"></i> Yeni Entegre Firma Ekle
            </a>
            <a href="/pages/admin/ariza_log.php" class="ss-btn ss-btn-tehlike">
                <i class="bi bi-exclamation-triangle-fill"></i> Tüm Arıza Logları
            </a>
        </div>
    </div>

    <!-- Son Arıza Logları -->
    <div class="ss-kart">
        <div class="ss-kart-baslik">
            <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>
            Son Arıza Kayıtları
        </div>
        <?php if (empty($son_arizalar)): ?>
            <div style="text-align:center; padding:30px; color:var(--renk-metin-soluk);">
                <i class="bi bi-check-circle" style="font-size:32px; color:#22c55e; display:block; margin-bottom:8px;"></i>
                Bugün arıza kaydı yok.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($son_arizalar as $ariza): ?>
                <div class="ss-ariza-bant">
                    <i class="bi bi-cpu" style="font-size:16px;"></i>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:13px;">
                            <?= html_temizle($ariza['cihaz_adi'] ?? $ariza['cihaz_kodu']) ?>
                        </div>
                        <div style="font-size:11px; color:var(--renk-metin-soluk); margin-top:1px;">
                            <?= html_temizle($ariza['ariza_tipi']) ?>
                            <?php if ($ariza['aciklama']): ?>
                                — <?= html_temizle(substr($ariza['aciklama'], 0, 50)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-size:10px; color:var(--renk-metin-soluk); white-space:nowrap; margin-left:8px;">
                        <?= date('d.m H:i', strtotime($ariza['olusturma_zamani'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
