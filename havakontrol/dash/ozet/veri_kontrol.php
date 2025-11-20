<?php
include '../conn.php';
header('Content-Type: application/json');

$sql = "SELECT 
    v.serino,
    v.temp,
    v.hum,
    v.wifi,
    v.versiyon,
    v.oturum,
    v.ip,
    v.kayit_tarihi,
    c.firmaid,
    c.konum,
    cr.unvan as firma_adi,
    TIMESTAMPDIFF(MINUTE, v.kayit_tarihi, NOW()) as dakika_once
FROM (
    SELECT serino, MAX(kayit_tarihi) as son_kayit
    FROM veriler 
    GROUP BY serino
) as son
JOIN veriler v ON v.serino = son.serino AND v.kayit_tarihi = son.son_kayit
LEFT JOIN cihazlar c ON v.serino = c.serino
LEFT JOIN cari cr ON c.firmaid = cr.id
ORDER BY v.kayit_tarihi DESC";

$result = $conn->query($sql);
$data = [];

while($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
