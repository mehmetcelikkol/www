<?php
/*
$db = new mysqli(
    "localhost",
    "proje_silosense",
    "0120a0120A",
    "proje_silosense"
);
*/

$db = new mysqli(
    "localhost",
    "root",
    "",
    "silosense"
);

if ($db->connect_errno) {
    http_response_code(500);
    echo json_encode([
        "durum" => "HATA",
        "mesaj" => "Veritabanı bağlantı hatası"
    ]);
    exit;
}

$db->set_charset("utf8mb4");
