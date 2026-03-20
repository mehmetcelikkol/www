<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('bayi');

$aktif_sayfa   = 'dashboard';
$sayfa_basligi = 'Dashboard';

$bayi_id = oturum_bagli_id();

// Bayi bilgisi
$stmt = $pdo->prepare("SELECT * FROM bayiler WHERE id = ?");
$stmt->execute([$bayi_id]);
$bayi = $stmt->fetch();

// Bayinin cihazları (son durumlarıyla)
$cihazlar = kullanici_cihazlari_getir($pdo);

// Her cihaz için doluluk + tahmin hesapla
$silo_kartlari = [];
foreach ($cihazlar as $cihaz) {
    $agirlik = 0;
    $son_gorulme = null;

    $stmt2 = $pdo->prepare("SELECT agirlik_degeri, son_gorulme FROM cihaz_son_durum WHERE cihaz_kimligi = ?");
    $stmt2->execute([$cihaz['cihaz_kodu']]);
    $son_durum = $stmt2->fetch();

    if ($son_durum) {
        $agirlik     = (float)$son_durum['agirlik_degeri'];
        $son_gorulme = $son_durum['son_gorulme'];
    }

    $yuzde  = doluluk_yuzde_hesapla($pdo, $cihaz['cihaz_kodu'], $agirlik);
    $renk   = doluluk_rengi_hex($yuzde);
    $tahmin = yem_bitis_tahmini($pdo, $cihaz['cihaz_kodu'], $agirlik);

    $silo_kartlari[] = [
        'cihaz'      => $cihaz,
        'agirlik'    => $agirlik,
        'yuzde'      => $yuzde,
        'renk'       => $renk,
        'tahmin'     => $tahmin,
        'son_gorulme' => $son_gorulme,
    ];
}

// Kritik silo var mı? (%20 altı)
$kritik_siloLar = array_filter($silo_kartlari, fn($s) => $s['yuzde'] < 20);

$istat = [
    'toplam' => count($cihazlar),
    'kritik' => count($kritik_siloLar),
    'dolu'   => count(array_filter($silo_kartlari, fn($s) => $s['yuzde'] >= 70)),
];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Bayi Karşılama -->
<div class="ss-kart mb-4" style="background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(139,92,246,0.05)); border-color:rgba(99,102,241,0.2);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-size:22px; font-weight:700;">
                Merhaba, <?= html_temizle(explode(' ', $_SESSION['ad_soyad'])[0]) ?> 👋
            </div>
            <div style="color:var(--renk-metin-soluk); font-size:13px; margin-top:4px;">
                <i class="bi bi-shop me-1"></i>
                <?= html_temizle($bayi['unvan'] ?? '') ?>
            </div>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div class="ss-stat-kart" style="--stat-renk:#6366f1; --stat-renk-bg:rgba(99,102,241,0.12); min-width:120px;">
                <div class="ss-stat-ikon" style="width:40px; height:40px;"><i class="bi bi-archive-fill"></i></div>
                <div>
                    <div class="ss-stat-sayi" style="font-size:26px;"><?= $istat['toplam'] ?></div>
                    <div class="ss-stat-etiket">Silo</div>
                </div>
            </div>
            <div class="ss-stat-kart" style="--stat-renk:#22c55e; --stat-renk-bg:rgba(34,197,94,0.12); min-width:120px;">
                <div class="ss-stat-ikon" style="width:40px; height:40px;"><i class="bi bi-battery-full"></i></div>
                <div>
                    <div class="ss-stat-sayi" style="font-size:26px;"><?= $istat['dolu'] ?></div>
                    <div class="ss-stat-etiket">Dolu (≥%70)</div>
                </div>
            </div>
            <?php if ($istat['kritik'] > 0): ?>
            <div class="ss-stat-kart" style="--stat-renk:#ef4444; --stat-renk-bg:rgba(239,68,68,0.12); min-width:120px;">
                <div class="ss-stat-ikon" style="width:40px; height:40px;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <div class="ss-stat-sayi" style="font-size:26px;"><?= $istat['kritik'] ?></div>
                    <div class="ss-stat-etiket">Kritik</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Kritik Uyarı Bandı -->
<?php if (!empty($kritik_siloLar)): ?>
<div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:12px; padding:14px 20px; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
    <i class="bi bi-exclamation-triangle-fill" style="font-size:22px; color:#ef4444;"></i>
    <div>
        <div style="font-weight:600; color:#fca5a5;">⚠️ Kritik Uyarı!</div>
        <div style="font-size:13px; color:var(--renk-metin-soluk); margin-top:2px;">
            <?= count($kritik_siloLar) ?> silonuzda yem seviyesi %20'nin altına düştü.
            Acil dolum planlaması yapınız.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Silo Kartları Grid -->
<?php if (empty($silo_kartlari)): ?>
    <div class="ss-kart" style="text-align:center; padding:60px;">
        <i class="bi bi-inbox" style="font-size:48px; color:var(--renk-metin-soluk); display:block; margin-bottom:16px;"></i>
        <div style="font-size:16px; font-weight:600; margin-bottom:8px;">Henüz silo atanmamış</div>
        <div style="color:var(--renk-metin-soluk); font-size:13px;">
            Sistem yöneticinizle iletişime geçerek cihaz ataması yaptırınız.
        </div>
    </div>
<?php else: ?>
<div class="ss-grid-silo">
    <?php foreach ($silo_kartlari as $s):
        $tahmin     = $s['tahmin'];
        $gun_kaldi  = $tahmin['gun_kaldi'];
        $acil       = $gun_kaldi !== null && $gun_kaldi <= 7;
        $son_gtime  = $s['son_gorulme'] ? date('d.m.Y H:i', strtotime($s['son_gorulme'])) : 'Veri yok';
        $aktif      = $s['cihaz']['aktif_mi'] ? 'aktif' : 'pasif';
    ?>
    <div class="silo-kart" onclick="window.location='/pages/bayi/silo_detay.php?id=<?= $s['cihaz']['id'] ?>'">
        <div class="silo-kart-adi"><?= html_temizle($s['cihaz']['cihaz_adi'] ?? $s['cihaz']['cihaz_kodu']) ?></div>
        <div class="silo-kart-kod">
            <span class="silo-durum-nokta <?= $aktif ?>"></span>
            <?= html_temizle($s['cihaz']['cihaz_kodu']) ?>
        </div>

        <!-- ANİMASYONLU SİLO -->
        <div class="silo-svg-container"
             id="silo_<?= $s['cihaz']['id'] ?>"
             data-yuzde="<?= $s['yuzde'] ?>"
             data-renk="<?= $s['renk'] ?>">
        </div>

        <!-- Doluluk Yüzdesi -->
        <div style="font-size:24px; font-weight:700; font-family:var(--font-mono); color:<?= $s['renk'] ?>; margin:-6px 0 12px;">
            %<?= number_format($s['yuzde'], 1) ?>
        </div>

        <!-- Yem Bitiş Tahmini (Countdown) -->
        <?php if ($gun_kaldi !== null): ?>
        <div style="
            background: <?= $acil ? 'rgba(239,68,68,0.1)' : 'rgba(99,102,241,0.08)' ?>;
            border: 1px solid <?= $acil ? 'rgba(239,68,68,0.25)' : 'rgba(99,102,241,0.15)' ?>;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            text-align: center;
        ">
            <div style="font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:<?= $acil ? '#fca5a5' : '#a5b4fc' ?>; margin-bottom:4px;">
                <i class="bi bi-hourglass-split me-1"></i>Tahmini Bitiş
            </div>
            <div style="font-size:20px; font-weight:700; font-family:var(--font-mono); color:<?= $acil ? '#fca5a5' : '#a5b4fc' ?>;">
                <?= number_format($gun_kaldi, 0) ?> GÜN
            </div>
            <div style="font-size:11px; color:var(--renk-metin-soluk);">
                <?= $tahmin['bitis_tarihi'] ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bilgiler -->
        <div style="font-size:12px;">
            <div class="silo-bilgi-satir">
                <span class="silo-bilgi-etiket">Ağırlık</span>
                <span class="silo-bilgi-deger" style="color:<?= $s['renk'] ?>">
                    <?= number_format($s['agirlik'], 1) ?> kg
                </span>
            </div>
            <?php if ($tahmin['gunluk_tuketim']): ?>
            <div class="silo-bilgi-satir">
                <span class="silo-bilgi-etiket">Günlük Tük.</span>
                <span class="silo-bilgi-deger"><?= number_format($tahmin['gunluk_tuketim'], 1) ?> kg/gün</span>
            </div>
            <?php endif; ?>
            <div class="silo-bilgi-satir">
                <span class="silo-bilgi-etiket">Son Okuma</span>
                <span class="silo-bilgi-deger" style="font-size:11px;"><?= $son_gtime ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
