<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/logger.php';

// Oturum başlatma kontrolü (auth.php zaten yapıyor ama emin olalım)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
$auth->requireLogin(); // Giriş yapılmadıysa login sayfasına yönlendir

// Anasayfaya erişimi logla
log_action('INFO', 'Kullanıcı anasayfayı görüntüledi.', ['sayfa' => 'index.php', 'rol' => $_SESSION['rol'] ?? 'Bilinmiyor']);

require_once __DIR__ . '/includes/header.php';

// Rol bazlı içerik yükleme
switch ($_SESSION['rol'] ?? 'kumes') {
    case 'admin':
    case 'baltav':
        require_once __DIR__ . '/includes/baltav_dashboard.php';
        break;
    case 'entegre':
        require_once __DIR__ . '/includes/entegre_dashboard.php';
        break;
    case 'kumes':
        require_once __DIR__ . '/includes/kumes_dashboard.php';
        break;
    default:
        echo '<div class="alert alert-danger">Yetkiniz olmayan bir alana erişmeye çalışıyorsunuz.</div>';
        break;
}

require_once __DIR__ . '/includes/footer.php';
?>