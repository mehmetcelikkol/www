<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Europe/Istanbul');
require "db.php";

// JSON al
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Debug
file_put_contents(
    "debug.log",
    date("H:i:s") . " RAW=" . $raw . PHP_EOL,
    FILE_APPEND
);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "durum" => "HATA",
        "mesaj" => "JSON alinamadi"
    ]);
    exit;
}

// JSON içinden oku
$cihaz_kimligi         = $data['cihaz_kimligi'] ?? null;
$paket_no              = $data['paket_no'] ?? null;
$agirlik_degeri        = $data['agirlik_degeri'] ?? null;
$stabil_mi             = $data['stabil_mi'] ?? null;
$calisma_suresi_saniye = $data['calisma_suresi_saniye'] ?? null;
$rs485_hata_sayisi     = $data['rs485_hata_sayisi'] ?? null;
$yazilim_surumu        = $data['yazilim_surumu'] ?? null;

$ip_adresi = $_SERVER['REMOTE_ADDR'] ?? null;

if (!$cihaz_kimligi || $paket_no === null) {
    echo json_encode([
        "durum" => "HATA",
        "mesaj" => "Zorunlu alan eksik"
    ]);
    exit;
}

// ANA KAYIT
$stmt = $db->prepare("
    INSERT INTO cihaz_paketleri
    (cihaz_kimligi, paket_no, agirlik_degeri, stabil_mi,
     calisma_suresi_saniye, rs485_hata_sayisi,
     yazilim_surumu, ip_adresi)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sidiiiss",
    $cihaz_kimligi,
    $paket_no,
    $agirlik_degeri,
    $stabil_mi,
    $calisma_suresi_saniye,
    $rs485_hata_sayisi,
    $yazilim_surumu,
    $ip_adresi
);

$stmt->execute();

// SON DURUM
$stmt2 = $db->prepare("
    INSERT INTO cihaz_son_durum
    (cihaz_kimligi, paket_no, agirlik_degeri, stabil_mi,
     calisma_suresi_saniye, rs485_hata_sayisi,
     yazilim_surumu, son_gorulme)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        paket_no = VALUES(paket_no),
        agirlik_degeri = VALUES(agirlik_degeri),
        stabil_mi = VALUES(stabil_mi),
        calisma_suresi_saniye = VALUES(calisma_suresi_saniye),
        rs485_hata_sayisi = VALUES(rs485_hata_sayisi),
        yazilim_surumu = VALUES(yazilim_surumu),
        son_gorulme = NOW()
");

$stmt2->bind_param(
    "sidiiss",
    $cihaz_kimligi,
    $paket_no,
    $agirlik_degeri,
    $stabil_mi,
    $calisma_suresi_saniye,
    $rs485_hata_sayisi,
    $yazilim_surumu
);

$stmt2->execute();

echo json_encode([
    "durum" => "OK"
]);
