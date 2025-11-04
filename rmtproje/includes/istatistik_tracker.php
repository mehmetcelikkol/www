<?php
function kayitIstatistik($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $sayfa = $_SERVER['REQUEST_URI'] ?? '/';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $session_id = session_id();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO site_istatistik (ip_adresi, sayfa, user_agent, referer, session_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ip, $sayfa, $user_agent, $referer, $session_id]);
    } catch (Exception $e) {
        error_log("İstatistik kayıt hatası: " . $e->getMessage());
    }
}

function kayitNavbarTiklama($pdo, $navbar_item) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $session_id = session_id();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO navbar_tiklama (navbar_item, ip_adresi, session_id) VALUES (?, ?, ?)");
        $stmt->execute([$navbar_item, $ip, $session_id]);
    } catch (Exception $e) {
        error_log("Navbar tıklama kayıt hatası: " . $e->getMessage());
    }
}
?>