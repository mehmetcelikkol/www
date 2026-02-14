<?php
// Hataları görmek için (Test aşamasında açık kalsın)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php"; // Dosya adının db.php olduğundan emin ol

header('Content-Type: application/json');

$cihaz_id = isset($_GET['id']) ? $_GET['id'] : null;
$onay_ver = isset($_GET['onay_ver']) ? $_GET['onay_ver'] : null;

if (!$cihaz_id) {
    echo json_encode(["error" => "ID eksik"]);
    exit;
}

// EĞER CİHAZ ONAY GÖNDERDİYSE (ayar_ok = 1 yap)
if ($onay_ver == 1) {
    $guncelle = $db->prepare("UPDATE cihaz_ayarlar SET ayar_ok = 1 WHERE cihaz_kimligi = ?");
    $guncelle->bind_param("s", $cihaz_id);
    $guncelle->execute();
    echo json_encode(["status" => "ok"]);
    exit;
}

// CİHAZ AYAR SORDUYSA
$sorgu = $db->prepare("SELECT modbus_baud, slave_id, ayar_ok FROM cihaz_ayarlar WHERE cihaz_kimligi = ?");
$sorgu->bind_param("s", $cihaz_id);
$sorgu->execute();
$sonuc = $sorgu->get_result()->fetch_assoc();

if ($sonuc) {
    // Veritabanı sütun isimlerinin modbus_baud ve slave_id olduğundan emin ol
    echo json_encode([
        "modbus_baud" => (int)$sonuc['modbus_baud'],
        "slave_id"    => (int)$sonuc['slave_id'],
        "ayar_ok"     => (int)$sonuc['ayar_ok']
    ]);
} else {
    echo json_encode(["error" => "Cihaz bulunamadi"]);
}
?>