<?php
require "db.php";

/* -----------------------------
   CİHAZ LİSTESİ
------------------------------*/
$cihazlar = [];
$res = $db->query("SELECT DISTINCT cihaz_kimligi FROM cihaz_paketleri ORDER BY cihaz_kimligi");
while ($r = $res->fetch_assoc()) {
    $cihazlar[] = $r['cihaz_kimligi'];
}

/* -----------------------------
   SEÇİLİ CİHAZ
------------------------------*/
$seciliCihaz = $_GET['cihaz'] ?? ($cihazlar[0] ?? null);

/* -----------------------------
   LİMİTLER
------------------------------*/
$limit = null;
if ($seciliCihaz) {
    $stmt = $db->prepare("SELECT * FROM cihaz_limitleri WHERE cihaz_kimligi=?");
    $stmt->bind_param("s", $seciliCihaz);
    $stmt->execute();
    $limit = $stmt->get_result()->fetch_assoc();
}

$minLimit = $limit['min_agirlik'] ?? null;
$maxLimit = $limit['max_agirlik'] ?? null;

/* -----------------------------
   KAYITLAR
------------------------------*/
$veriler = [];
if ($seciliCihaz) {
    $stmt = $db->prepare("
        SELECT * FROM cihaz_paketleri
        WHERE cihaz_kimligi=?
        ORDER BY id DESC
        LIMIT 200
    ");
    $stmt->bind_param("s", $seciliCihaz);
    $stmt->execute();
    $veriler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* -----------------------------
   GRAFİK VERİLERİ
------------------------------*/
$grafikLabel = [];
$grafikAgirlik = [];
$grafikRenk = [];

foreach (array_reverse($veriler) as $v) {
    $grafikLabel[] = date("H:i:s", strtotime($v['alinan_zaman']));
    $grafikAgirlik[] = $v['agirlik_degeri'];

    if ($minLimit !== null && (
        $v['agirlik_degeri'] < $minLimit ||
        $v['agirlik_degeri'] > $maxLimit
    )) {
        $grafikRenk[] = "rgba(231,76,60,1)";
    } else {
        $grafikRenk[] = "rgba(46,204,113,1)";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>SiloSense Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial; background:#f4f6f8; padding:20px; }
h1 { margin-bottom:10px; }
button {
    padding:8px 14px; border:none; margin:4px;
    border-radius:4px; cursor:pointer;
}
.active { background:#2c3e50; color:white; }
.passive { background:#ddd; }

table {
    width:100%; border-collapse:collapse; background:#fff;
    margin-top:20px;
}
th, td {
    padding:8px; border-bottom:1px solid #ddd; text-align:center;
}
th { background:#34495e; color:#fff; }

.ok { color:green; font-weight:bold; }
.warn { color:orange; font-weight:bold; }
.bad { color:red; font-weight:bold; }

.chart-box {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border-radius: 6px;

    max-width: 900px;
    height: 350px;
}

</style>
</head>
<body>

<h1>📡 SiloSense – Dashboard</h1>

<!-- CİHAZ BUTONLARI -->
<div>
<?php foreach ($cihazlar as $c): ?>
    <a href="?cihaz=<?= urlencode($c) ?>">
        <button class="<?= $c === $seciliCihaz ? 'active' : 'passive' ?>">
            <?= htmlspecialchars($c) ?>
        </button>
    </a>
<?php endforeach; ?>
</div>

<!-- GRAFİK -->
<div class="chart-box">
<canvas id="grafik"></canvas>
</div>

<script>
new Chart(document.getElementById('grafik'), {
    type: 'line',
    data: {
        labels: <?= json_encode($grafikLabel) ?>,
        datasets: [
        {
            label: 'Ağırlık (kg)',
            data: <?= json_encode($grafikAgirlik) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52,152,219,0.1)',
            pointBackgroundColor: <?= json_encode($grafikRenk) ?>,
            tension:0.3,
            fill:true
        },
        <?php if ($minLimit !== null): ?>
        {
            label: 'Min Limit',
            data: Array(<?= count($grafikAgirlik) ?>).fill(<?= $minLimit ?>),
            borderColor:'orange',
            borderDash:[5,5],
            pointRadius:0
        },
        <?php endif; ?>
        <?php if ($maxLimit !== null): ?>
        {
            label: 'Max Limit',
            data: Array(<?= count($grafikAgirlik) ?>).fill(<?= $maxLimit ?>),
            borderColor:'red',
            borderDash:[5,5],
            pointRadius:0
        }
        <?php endif; ?>
        ]
    }
});
</script>

<!-- TABLO -->
<table>
<tr>
<th>Zaman</th>
<th>Ağırlık</th>
<th>Stabil</th>
<th>Paket</th>
<th>RS485</th>
<th>Uptime</th>
<th>Alarm</th>
</tr>

<?php foreach ($veriler as $r):

    $alarm = "-";
    $class = "ok";

    if ($minLimit !== null) {
        if ($r['agirlik_degeri'] < $minLimit) {
            $alarm = "⬇ ALT LİMİT";
            $class = "bad";
        }
        if ($r['agirlik_degeri'] > $maxLimit) {
            $alarm = "⬆ ÜST LİMİT";
            $class = "bad";
        }
    }

    if ($r['stabil_mi'] == 0) {
        $alarm = "DOLUM";
        $class = "warn";
    }

    if (strtotime($r['alinan_zaman']) < time()-300) {
        $alarm = "OFFLINE";
        $class = "bad";
    }
?>
<tr>
<td><?= $r['alinan_zaman'] ?></td>
<td><?= number_format($r['agirlik_degeri'],2) ?></td>
<td><?= $r['stabil_mi'] ? "Evet":"Hayır" ?></td>
<td><?= $r['paket_no'] ?></td>
<td><?= $r['rs485_hata_sayisi'] ?></td>
<td><?= $r['calisma_suresi_saniye'] ?></td>
<td class="<?= $class ?>"><?= $alarm ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
