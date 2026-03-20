<?php
require_once __DIR__ . '/includes/functions.php';

// Oturumu tamamen temizle
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: /index.php?cikis=1');
exit;
