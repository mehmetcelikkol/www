<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sayfa içeriğini belirle
$page = isset($_GET['sayfa']) ? $_GET['sayfa'] : 'anasayfa';

// Üst kısım
require_once 'inc/header.php';

// Sayfa içeriği
switch($page) {
    case 'anasayfa':
        require_once 'inc/home.php';
        break;
    case 'iletisim':
        require_once 'inc/contact.php';
        break;
    case 'is-ortakligi':
        require_once 'inc/partnership.php';
        break;
    default:
        require_once 'inc/home.php';
}

// Alt kısım
require_once 'inc/footer.php';
?>