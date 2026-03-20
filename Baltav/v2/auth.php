<?php
// auth.php - Güçlendirilmiş Oturum Yönetimi

require_once __DIR__ . '/db.php';

class Auth {
    private $db;
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Session süresini uzat (1 gün)
            ini_set('session.gc_maxlifetime', 86400);
            session_set_cookie_params(86400);
            session_start();
        }
        $this->db = Database::getConnection();
        
        // Otomatik Giriş Kontrolü
        if(!$this->check() && isset($_COOKIE['silosense_user_v2'])) {
            $this->loginWithCookie($_COOKIE['silosense_user_v2']);
        }
    }

    public function login($eposta, $parola, $beniHatirla = false) {
        $stmt = $this->db->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
        $stmt->execute([$eposta]);
        $user = $stmt->fetch();

        if ($user && (password_verify($parola, $user['sifre_hash']) || $parola === $user['sifre_hash'])) { // Hash veya düz metin (geçici)
            $this->setSession($user);
            
            if ($beniHatirla) {
                // Basit ve Güvenli Cookie (User ID + Hash birleşimi)
                $token = base64_encode($user['id'] . ':' . md5($user['sifre_hash'] . 'GizliTuz'));
                setcookie('silosense_user_v2', $token, time() + (86400 * 30), "/"); // 30 Gün
            }
            return true;
        }
        return false;
    }

    private function loginWithCookie($token) {
        $parts = explode(':', base64_decode($token));
        if(count($parts) != 2) return false;
        
        $id = $parts[0];
        $hash = $parts[1];
        
        $stmt = $this->db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user && md5($user['sifre_hash'] . 'GizliTuz') === $hash) {
            $this->setSession($user);
            return true;
        }
        return false;
    }

    private function setSession($user) {
        $_SESSION['kullanici_id'] = $user['id'];
        $_SESSION['ad_soyad'] = $user['ad_soyad'];
        $_SESSION['eposta'] = $user['eposta'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['bagli_id'] = $user['bagli_kurum_id'];
    }

    public function logout() {
        session_destroy();
        setcookie('silosense_user_v2', '', time() - 3600, "/");
        header("Location: login.php");
        exit;
    }

    public function check() {
        return isset($_SESSION['kullanici_id']);
    }

    public function requireLogin() {
        if (!$this->check()) {
            header("Location: login.php");
            exit;
        }
    }
}
?>
