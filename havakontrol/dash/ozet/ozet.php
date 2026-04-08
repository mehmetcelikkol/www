<?php
include '../conn.php';

$aktifSure = 30; // dakika

/*
  HER SERİ NO İÇİN EN SON KAYIT
*/
$sql = "
SELECT v.*
FROM veriler v
INNER JOIN (
    SELECT serino, MAX(kayit_tarihi) AS son_tarih
    FROM veriler
    GROUP BY serino
) son ON v.serino = son.serino AND v.kayit_tarihi = son.son_tarih
ORDER BY v.kayit_tarihi DESC
";

$result = $conn->query($sql);

$cihazlar = [];

/*
  IP DEĞİŞİM TAKİBİ
*/
$ipDegisimSayisi = [];

while ($row = $result->fetch_assoc()) {

    $serino = $row['serino'];

    $cihazlar[$serino] = $row;

    if (!isset($ipDegisimSayisi[$serino])) {
        $ipDegisimSayisi[$serino] = [
            'onceki_ip' => null,
            'degisim' => 0
        ];
    }

    $mevcutIP = $row['ip'];

    if ($ipDegisimSayisi[$serino]['onceki_ip'] !== null) {
        if ($ipDegisimSayisi[$serino]['onceki_ip'] != $mevcutIP) {
            $ipDegisimSayisi[$serino]['degisim']++;
        }
    }

    $ipDegisimSayisi[$serino]['onceki_ip'] = $mevcutIP;
}

/*
  CİHAZ TABLOSU
*/
$cihazMap = [];
$resCihaz = $conn->query("SELECT * FROM cihazlar");

while ($c = $resCihaz->fetch_assoc()) {
    $cihazMap[$c['serino']] = $c;
}

/*
  CARİ TABLOSU
*/
$cariMap = [];
$resCari = $conn->query("SELECT * FROM cari");

while ($cr = $resCari->fetch_assoc()) {
    $cariMap[$cr['id']] = $cr;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>HavaKontrol - Özet</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.offline { background-color: #ffe6e6; }
</style>

</head>

<body>

<div class="container mt-4">
    <h2>Genel Durum</h2>

    <table class="table table-bordered table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Seri No</th>
                <th>Sıcaklık</th>
                <th>Nem</th>
                <th>WiFi</th>
                <th>Versiyon</th>
                <th>Oturum</th>
                <th>Cari</th>
                <th>Konum</th>
                <th>Zaman</th>
                <th>Durum</th>
                <th>IP</th>
                <th>IP Değişim</th>
            </tr>
        </thead>

        <tbody>

        <?php 
        $i = 1;

        foreach ($cihazlar as $row) {

            $serino = $row['serino'];

            $dakika = (time() - strtotime($row['kayit_tarihi'])) / 60;

            $durum = ($dakika <= $aktifSure) ? "AKTİF" : "PASİF";

            $class = ($durum == "PASİF") ? "offline" : "";

            $firma = "STOK";
            $konum = "-";

            if (isset($cihazMap[$serino])) {
                $firmaid = $cihazMap[$serino]['firmaid'];
                $konum = $cihazMap[$serino]['konum'];

                if (isset($cariMap[$firmaid])) {
                    $firma = $cariMap[$firmaid]['unvan'];
                }
            }

            $ipDegisim = $ipDegisimSayisi[$serino]['degisim'] ?? 0;
        ?>

        <tr class="<?= $class ?>">
            <td><?= $i++ ?></td>
            <td><?= $serino ?></td>
            <td><?= $row['temp'] ?>°C</td>
            <td>%<?= $row['hum'] ?></td>
            <td><?= $row['wifi'] ?></td>
            <td><?= $row['versiyon'] ?></td>
            <td><?= $row['oturum'] ?></td>
            <td><?= $firma ?></td>
            <td><?= $konum ?></td>
            <td><?= $row['kayit_tarihi'] ?></td>
            <td><?= $durum ?></td>
            <td><?= $row['ip'] ?></td>
            <td><?= $ipDegisim ?></td>
        </tr>

        <?php } ?>

        </tbody>
    </table>
</div>

</body>
</html>