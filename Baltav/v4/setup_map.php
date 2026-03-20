<?php
require_once __DIR__ . '/db.php';

try {
    $db = Database::getConnection();
    
    // Sütun Ekleme (Eğer yoksa)
    $sql = "ALTER TABLE cihazlar 
            ADD COLUMN lat DECIMAL(10, 8) DEFAULT NULL,
            ADD COLUMN lon DECIMAL(11, 8) DEFAULT NULL";
            
    try {
        $db->exec($sql);
        echo "✅ Sütunlar Eklendi!<br>";
    } catch(PDOException $e) {
        echo "ℹ️ Sütunlar zaten var olabilir.<br>";
    }

    // Rastgele Koordinat Ata (Demo İçin - Balıkesir Çevresi)
    $stmt = $db->query("SELECT id FROM cihazlar");
    while($row = $stmt->fetch()) {
        $lat = 39.6484 + (rand(-50, 50) / 1000); // Balıkesir
        $lon = 27.8826 + (rand(-50, 50) / 1000);
        
        $upd = $db->prepare("UPDATE cihazlar SET lat = ?, lon = ? WHERE id = ?");
        $upd->execute([$lat, $lon, $row['id']]);
    }
    
    echo "✅ Demo Koordinatlar Atandı! <a href='index.php'>Dashboard'a Dön</a>";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
