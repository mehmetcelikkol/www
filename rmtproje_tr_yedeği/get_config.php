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
    $guncelle = $db->prepare("UPDATE cihaz_ayar SET ayar_ok = 1 WHERE cihaz_kimligi = ?");
    $guncelle->bind_param("s", $cihaz_id);
    $guncelle->execute();
    echo json_encode(["status" => "ok"]);
    exit;
}

// CİHAZ AYAR SORDUYSA
$sorgu = $db->prepare("SELECT bdrate, slave_id1, ayar_ok FROM cihaz_ayar WHERE cihaz_kimligi = ?");
$sorgu->bind_param("s", $cihaz_id);
$sorgu->execute();
$sonuc = $sorgu->get_result()->fetch_assoc();

if ($sonuc) {
    // Veritabanı sütun isimlerinin bdrate ve slave_id1 olduğundan emin ol
    echo json_encode([
        "bdrate" => (int)$sonuc['bdrate'],
        "slave_id1"    => (int)$sonuc['slave_id1'],
        "ayar_ok"     => (int)$sonuc['ayar_ok']
    ]);
} else {
    echo json_encode(["error" => "Cihaz bulunamadi"]);
}
?>