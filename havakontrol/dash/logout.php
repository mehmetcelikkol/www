<?php
session_start();
session_unset(); // Tüm oturum değişkenlerini kaldır
session_destroy(); // Oturumu tamamen sonlandır

// Kullanıcıyı giriş sayfasına yönlendirin (veya başka bir sayfa)
header("Location: login.php");
exit();
?>
