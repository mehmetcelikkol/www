<?php
session_start();
require_once 'sistem/baglanti.php';
sistem_log_yaz($db, 'Sistem Çıkışı', 'Kullanıcı sistemden güvenli şekilde çıkış yaptı.');
session_destroy();
header('Location: giris.php');
exit;
?>
