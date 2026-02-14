<?php
echo json_encode([
  "version" => trim(file_get_contents("version.txt")),
  "bin" => file_exists("firmware.bin") ? "OK" : "YOK"
]);
