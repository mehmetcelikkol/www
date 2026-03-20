<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/aktif_menu.php';

yetkili_giris_iste('bayi');

$aktif_sayfa   = 'silolarim';
$sayfa_basligi = 'Silolarım';

$cihazlar = kullanici_cihazlari_getir($pdo);

// Her cihaz için son 7 günlük veri (grafik için)
$grafik_verileri = [];
foreach ($cihazlar as $cihaz) {
    $stmt = $pdo->prepare("
        SELECT DATE(alinan_zaman) AS tarih,
               AVG(agirlik_degeri) AS ort_agirlik,
               MIN(agirlik_degeri) AS min_agirlik,
               MAX(agirlik_degeri) AS max_agirlik
        FROM cihaz_paketleri
        WHERE cihaz_kimligi = ?
          AND alinan_zaman >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(alinan_zaman)
        ORDER BY tarih ASC
    ");
    $stmt->execute([$cihaz['cihaz_kodu']]);
    $grafik_verileri[$cihaz['cihaz_kodu']] = $stmt->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>

<?php if (empty($cihazlar)): ?>
    <div class="ss-kart" style="text-align:center; padding:60px;">
        <i class="bi bi-inbox" style="font-size:48px; color:var(--renk-metin-soluk); display:block; margin-bottom:16px;"></i>
        <div style="font-size:16px; font-weight:600; margin-bottom:8px;">Henüz silo atanmamış</div>
        <div style="color:var(--renk-metin-soluk); font-size:13px;">Sistem yöneticinizle iletişime geçin.</div>
    </div>
<?php else: ?>

<?php foreach ($cihazlar as $cihaz):
    $stmt = $pdo->prepare("SELECT * FROM cihaz_son_durum WHERE cihaz_kimligi = ?");
    $stmt->execute([$cihaz['cihaz_kodu']]);
    $son_durum = $stmt->fetch();

    $agirlik = (float)($son_durum['agirlik_degeri'] ?? 0);
    $yuzde   = doluluk_yuzde_hesapla($pdo, $cihaz['cihaz_kodu'], $agirlik);
    $renk    = doluluk_rengi_hex($yuzde);
    $tahmin  = yem_bitis_tahmini($pdo, $cihaz['cihaz_kodu'], $agirlik);
    $gun_kaldi = $tahmin['gun_kaldi'];

    // Grafik verisi JSON
    $grafik = $grafik_verileri[$cihaz['cihaz_kodu']] ?? [];
    $grafik_tarihler = array_column($grafik, 'tarih');
    $grafik_agirlik  = array_column($grafik, 'ort_agirlik');
?>
<div class="ss-kart mb-4">
    <div style="display:flex; flex-wrap:wrap; gap:24px;">

        <!-- Sol: Silo + Bilgiler -->
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <!-- Silo SVG -->
            <div style="text-align:center;">
                <div class="silo-svg-container"
                     id="silo_<?= $cihaz['id'] ?>"
                     data-yuzde="<?= $yuzde ?>"
                     data-renk="<?= $renk ?>"
                     style="width:110px; height:160px; margin:0 auto;">
                </div>
                <div style="font-size:28px; font-weight:700; font-family:var(--font-mono); color:<?= $renk ?>; margin-top:4px;">
                    %<?= number_format($yuzde, 1) ?>
                </div>
            </div>

            <!-- Detaylar -->
            <div style="min-width:200px;">
                <div style="font-size:18px; font-weight:700; margin-bottom:4px;">
                    <?= html_temizle($cihaz['cihaz_adi'] ?? $cihaz['cihaz_kodu']) ?>
                </div>
                <div style="font-family:var(--font-mono); font-size:11px; color:var(--renk-metin-soluk); margin-bottom:16px;">
                    <span class="silo-durum-nokta <?= $cihaz['aktif_mi'] ? 'aktif' : 'pasif' ?>"></span>
                    <?= html_temizle($cihaz['cihaz_kodu']) ?>
                    <?php if ($cihaz['konum']): ?>
                        · <?= html_temizle($cihaz['konum']) ?>
                    <?php endif; ?>
                </div>

                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div style="display:flex; justify-content:space-between; font-size:13px; gap:20px;">
                        <span style="color:var(--renk-metin-soluk);">Güncel Ağırlık</span>
                        <span style="font-weight:600; font-family:var(--font-mono); color:<?= $renk ?>">
                            <?= number_format($agirlik, 1) ?> kg
                        </span>
                    </div>
                    <?php if ($son_durum): ?>
                    <div style="display:flex; justify-content:space-between; font-size:13px; gap:20px;">
                        <span style="color:var(--renk-metin-soluk);">Sinyal</span>
                        <span style="font-weight:600;">
                            <?= $son_durum['stabil_mi'] ? '<span style="color:#22c55e;">Stabil</span>' : '<span style="color:#f59e0b;">Dengesiz</span>' ?>
                        </span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:13px; gap:20px;">
                        <span style="color:var(--renk-metin-soluk);">Son Okuma</span>
                        <span style="font-weight:600; font-family:var(--font-mono); font-size:11px;">
                            <?= $son_durum['son_gorulme'] ? date('d.m.Y H:i', strtotime($son_durum['son_gorulme'])) : '—' ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($tahmin['gunluk_tuketim']): ?>
                    <div style="display:flex; justify-content:space-between; font-size:13px; gap:20px;">
                        <span style="color:var(--renk-metin-soluk);">Günlük Tüketim</span>
                        <span style="font-weight:600; font-family:var(--font-mono);">
                            <?= number_format($tahmin['gunluk_tuketim'], 1) ?> kg/gün
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Orta: Yem Bitiş Sayacı -->
        <?php if ($gun_kaldi !== null): ?>
        <div class="ss-countdown-kart <?= $gun_kaldi <= 7 ? 'ss-countdown-tehlike' : '' ?>" style="flex:0 0 auto; min-width:180px; align-self:center;">
            <div class="ss-countdown-baslik">
                <i class="bi bi-hourglass-split"></i>
                Tahmini Yem Bitiş
            </div>
            <div class="ss-countdown-sayi"><?= number_format($gun_kaldi, 0) ?></div>
            <div class="ss-countdown-aciklama">gün kaldı</div>
            <div class="ss-countdown-tarih"><?= $tahmin['bitis_tarihi'] ?></div>
        </div>
        <?php endif; ?>

        <!-- Sağ: Haftalık Grafik -->
        <div style="flex:1; min-width:260px;">
            <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; color:var(--renk-metin-soluk); margin-bottom:10px;">
                Son 7 Gün — Ağırlık Trendi
            </div>
            <?php if (!empty($grafik)): ?>
            <div id="chart_<?= $cihaz['id'] ?>" style="min-height:160px;"></div>
            <?php else: ?>
            <div style="height:160px; display:flex; align-items:center; justify-content:center; color:var(--renk-metin-soluk); font-size:12px;">
                Grafik için yeterli veri yok
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if (!empty($grafik)) {
    $renk_grafik = $renk;
    echo "<script>
    (function(){
        var options = {
            series: [{name:'Ağırlık (kg)', data: " . json_encode(array_map(fn($v) => round($v, 1), $grafik_agirlik)) . "}],
            chart: {type:'area', height:160, toolbar:{show:false}, background:'transparent', sparkline:{enabled:false}},
            theme: {mode:'dark'},
            colors: ['$renk_grafik'],
            fill: {type:'gradient', gradient:{opacityFrom:0.4, opacityTo:0.05}},
            stroke: {width:2, curve:'smooth'},
            xaxis: {categories:" . json_encode($grafik_tarihler) . ", labels:{style:{colors:'#64748b', fontSize:'10px'}}},
            yaxis: {labels:{style:{colors:'#64748b', fontSize:'10px'}}},
            grid: {borderColor:'rgba(255,255,255,0.05)'},
            dataLabels: {enabled:false},
            tooltip: {theme:'dark'},
        };
        new ApexCharts(document.getElementById('chart_{$cihaz['id']}'), options).render();
    })();
    </script>";
}
?>

<?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
