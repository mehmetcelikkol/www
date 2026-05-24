<?php
$ip = $_SERVER['REMOTE_ADDR'];
$tarih = date('Y-m-d H:i:s');
$versiyon = trim(file_get_contents('version.txt')); // Mevcut versiyonu al
$logSatiri = "$tarih - $ip - $versiyon - firmware.ino.esp32.bin\n";

file_put_contents('downloads.log', $logSatiri, FILE_APPEND);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="firmware.ino.esp32.bin"');
readfile('firmware.ino.esp32.bin');
exit;
?>
