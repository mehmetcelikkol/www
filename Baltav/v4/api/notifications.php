<?php
// SiloSense V2 - Bildirim API
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Sadece giriş yapmış kullanıcılar
if(!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['count' => 0, 'items' => []]);
    exit;
}

try {
    $db = Database::getConnection();
    
    // Kritik Seviyedeki Silolar (%20 altı)
    // Not: Gerçek projede 'max_agirlik' dinamik olmalı.
    $sql = "SELECT c.cihaz_adi, p.agirlik_degeri, l.max_agirlik 
            FROM cihazlar c
            JOIN (
                SELECT * FROM cihaz_paketleri WHERE id IN (
                    SELECT MAX(id) FROM cihaz_paketleri GROUP BY cihaz_kimligi
                )
            ) p ON c.cihaz_kodu = p.cihaz_kimligi
            LEFT JOIN cihaz_limitleri l ON c.cihaz_kodu = l.cihaz_kimligi
            WHERE c.aktif_mi = 1";
            
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $alerts = [];
    foreach($rows as $r) {
        $max = ($r['max_agirlik'] > 0) ? $r['max_agirlik'] : 20000;
        $yuzde = ($r['agirlik_degeri'] / $max) * 100;
        
        if($yuzde < 20) {
            $alerts[] = [
                'title' => 'Kritik Seviye!',
                'msg' => "{$r['cihaz_adi']} seviyesi %" . round($yuzde) . "!",
                'time' => 'Şimdi'
            ];
        }
    }
    
    echo json_encode([
        'count' => count($alerts),
        'items' => $alerts
    ]);

} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
?>
