<?php
require "db.php";

header('Content-Type: application/json');

$chip = $_GET['chip'] ?? '';

/*
  Normalde bu veriler DB’den gelir.
  Şimdilik sabit örnek:
*/
$response = [
  "chip_id" => $chip,
  "discovery_mode" => 0,   // 1 = servis modu, 0 = normal
  "baudrate" => 9600,
  "modbus_id" => 1
];

echo json_encode($response);
