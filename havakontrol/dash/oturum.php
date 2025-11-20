<?php
session_start(); // Oturum başlatma

// Eğer oturum yoksa ve çerez varsa oturumu çerez bilgileriyle başlat
if (!isset($_SESSION['mail']) && isset($_COOKIE['mail'])) {
    $_SESSION['mail'] = $_COOKIE['mail'];
    $_SESSION['ad'] = $_COOKIE['ad']; // Çerezden ad bilgisi de alınıyor
}

// Eğer oturum veya çerez yoksa giriş sayfasına yönlendir
if (!isset($_SESSION['mail'])) {
    header("Location: login.php");
    exit();
}
?>
