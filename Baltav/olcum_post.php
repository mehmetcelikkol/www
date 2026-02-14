<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Europe/Istanbul');
require "db.php";

$raw = file_get_contents("php://input");

// DEBUG
file_put_contents(
    "debug.log",
    date("Y-m-d H:i:s") . " RAW=" . $raw . PHP_EOL,
    FILE_APPEND
);

$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["durum" => "HATA", "mesaj" => "JSON decode hatası"]);
    exit;
}

// JSON'dan verileri al
$cihaz_kimligi         = $data['cihaz_kimligi'] ?? null;
$paket_no              = $data['paket_no'] ?? null;
$agirlik_degeri        = $data['agirlik_degeri'] ?? 0.0;
$stabil_mi             = $data['stabil_mi'] ?? 1;  // ASLINDA VERSİYON (170)
$calisma_suresi_saniye = $data['calisma_suresi_saniye'] ?? 0;
$rs485_hata_sayisi     = $data['rs485_hata_sayisi'] ?? 0;
$yazilim_surumu        = $data['yazilim_surumu'] ?? '1.0.0';

$ip_adresi = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// DEBUG: Gelen stabil_mi değerini logla (versiyon olarak)
file_put_contents(
    "debug.log",
    date("Y-m-d H:i:s") . 
    " stabil_mi(deger)=" . $stabil_mi . 
    " tipi=" . gettype($stabil_mi) . PHP_EOL,
    FILE_APPEND
);

if (!$cihaz_kimligi || $paket_no === null) {
    echo json_encode(["durum" => "HATA", "mesaj" => "Eksik alan"]);
    exit;
}

// GEÇİCİ ÇÖZÜM: stabil_mi'yi cihaz_versiyonu'na yaz
$cihaz_versiyonu = $stabil_mi;  // stabil_mi aslında versiyon (170)
$gercek_stabil_mi = 1;  // stabil_mi'yi her zaman 1 (true) yap

// ANA KAYIT (geçici çözüm)
$stmt = $db->prepare("
    INSERT INTO cihaz_paketleri
    (cihaz_kimligi, paket_no, agirlik_degeri, stabil_mi,
     cihaz_versiyonu, calisma_suresi_saniye, rs485_hata_sayisi,
     yazilim_surumu, ip_adresi)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// "sidiiisss" - stabil_mi ve cihaz_versiyonu için iki int
$stmt->bind_param(
    "sidiiisss",
    $cihaz_kimligi,
    $paket_no,
    $agirlik_degeri,
    $gercek_stabil_mi,      // Her zaman 1
    $cihaz_versiyonu,       // Versiyon (170) buraya
    $calisma_suresi_saniye,
    $rs485_hata_sayisi,
    $yazilim_surumu,
    $ip_adresi
);

$stmt->execute();

// SON DURUM (geçici çözüm)
$stmt2 = $db->prepare("
    INSERT INTO cihaz_son_durum
    (cihaz_kimligi, paket_no, agirlik_degeri, stabil_mi,
     cihaz_versiyonu, calisma_suresi_saniye, rs485_hata_sayisi,
     yazilim_surumu, son_gorulme)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        paket_no = VALUES(paket_no),
        agirlik_degeri = VALUES(agirlik_degeri),
        stabil_mi = VALUES(stabil_mi),
        cihaz_versiyonu = VALUES(cihaz_versiyonu),
        calisma_suresi_saniye = VALUES(calisma_suresi_saniye),
        rs485_hata_sayisi = VALUES(rs485_hata_sayisi),
        yazilim_surumu = VALUES(yazilim_surumu),
        son_gorulme = NOW()
");

$stmt2->bind_param(
    "sidi iiss",
    $cihaz_kimligi,
    $paket_no,
    $agirlik_degeri,
    $gercek_stabil_mi,      // Her zaman 1
    $cihaz_versiyonu,       // Versiyon (170) buraya
    $calisma_suresi_saniye,
    $rs485_hata_sayisi,
    $yazilim_surumu
);

$stmt2->execute();

// Başarılı cevap (debug info ekle)
echo json_encode([
    "durum" => "OK",
    "mesaj" => "Kayıt başarılı (geçici çözüm)",
    "paket_no" => $paket_no,
    "stabil_mi_gelen" => $stabil_mi,
    "cihaz_versiyonu_yazilan" => $cihaz_versiyonu,
    "not" => "stabil_mi=1, versiyon=cihaz_versiyonu'na yazıldı"
]);