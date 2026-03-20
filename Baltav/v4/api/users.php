<?php
// api/users.php
// Basit kullanıcı CRUD (JSON) - admin yetkisi gerektirir
require_once __DIR__ . '/../auth.php';
$auth = new Auth();
$auth->requireLogin();
header('Content-Type: application/json; charset=utf-8');

$db = Database::getConnection();
$action = $_REQUEST['action'] ?? 'list';

if ($action !== 'list' && !$auth->hasRole(['admin'])) {
    http_response_code(403);
    echo json_encode(['error'=>'forbidden']);
    exit;
}

try {
    if ($action === 'list') {
        $stmt = $db->query("SELECT id, ad_soyad, eposta, rol, bagli_id, created_at FROM kullanicilar ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        echo json_encode(['data'=>$rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'create') {
        $ad = trim($_POST['ad_soyad'] ?? '');
        $ep = trim($_POST['eposta'] ?? '');
        $sifre = $_POST['sifre'] ?? null;
        $rol = $_POST['rol'] ?? 'bayi';
        $bagli = $_POST['bagli_id'] ? (int)$_POST['bagli_id'] : null;

        if (!$ad || !$ep || !$sifre) {
            throw new Exception('Gerekli alanlar eksik');
        }

        // GEÇİCİ: düz metin parola saklama
        $hash = $sifre;
        $stmt = $db->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre_hash, rol, bagli_id) VALUES (:ad, :ep, :hash, :rol, :bagli)");
        $stmt->execute([':ad'=>$ad,':ep'=>$ep,':hash'=>$hash,':rol'=>$rol,':bagli'=>$bagli]);
        echo json_encode(['ok'=>true,'id'=>$db->lastInsertId()]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID gerekli');
        $ad = trim($_POST['ad_soyad'] ?? '');
        $ep = trim($_POST['eposta'] ?? '');
        $sifre = $_POST['sifre'] ?? null;
        $rol = $_POST['rol'] ?? 'bayi';
        $bagli = $_POST['bagli_id'] ? (int)$_POST['bagli_id'] : null;

        if ($sifre) {
            // GEÇİCİ: düz metin parola saklama
            $hash = $sifre;
            $stmt = $db->prepare("UPDATE kullanicilar SET ad_soyad=:ad, eposta=:ep, sifre_hash=:hash, rol=:rol, bagli_id=:bagli, updated_at=NOW() WHERE id=:id");
            $stmt->execute([':ad'=>$ad,':ep'=>$ep,':hash'=>$hash,':rol'=>$rol,':bagli'=>$bagli,':id'=>$id]);
        } else {
            $stmt = $db->prepare("UPDATE kullanicilar SET ad_soyad=:ad, eposta=:ep, rol=:rol, bagli_id=:bagli, updated_at=NOW() WHERE id=:id");
            $stmt->execute([':ad'=>$ad,':ep'=>$ep,':rol'=>$rol,':bagli'=>$bagli,':id'=>$id]);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID gerekli');
        $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = :id");
        $stmt->execute([':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    throw new Exception('Bilinmeyen action');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error'=>true,'message'=>$e->getMessage()]);
}

?>