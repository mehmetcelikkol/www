<?php


$host = "localhost";
$dbname = "proje_crm";
$username = "proje_crm";
$password = "0120a0120A";

/*
$host = "localhost";
$dbname = "proje_crm";
$username = "root";
$password = "";
*/


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantısı sağlanamadı: " . $e->getMessage());
}
?>
