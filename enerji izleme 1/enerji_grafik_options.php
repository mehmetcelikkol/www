<?php
header('Content-Type: application/json; charset=utf-8');
$dbDosya = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/energy.db';
try {
    $db = new PDO("sqlite:$dbDosya");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ölçümler tablosundan aktif cihaz ve kanal id'leri
    $cihazIdler = [];
    $kanalIdler = [];
    $stmt = $db->query("SELECT cihaz_id, kanal_id, SUM(deger) as toplam FROM olcumler GROUP BY cihaz_id, kanal_id HAVING toplam > 0");
    foreach($stmt as $row){
        $cihazIdler[$row['cihaz_id']] = true;
        $kanalIdler[$row['kanal_id']] = true;
    }
    // Cihazlar
    $cihazlar = [];
    if(count($cihazIdler) > 0){
        $placeholders = implode(',', array_fill(0, count($cihazIdler), '?'));
        $query = "SELECT id, cihaz_adi, konum FROM cihazlar WHERE id IN ($placeholders) ORDER BY cihaz_adi ASC";
        $stmt = $db->prepare($query);
        $stmt->execute(array_keys($cihazIdler));
        foreach($stmt as $c){
            $ad = $c['cihaz_adi'] ? $c['cihaz_adi'] : ('Cihaz #' . $c['id']);
            $c['cihaz_adi'] = $c['konum'] ? ($ad . ' - ' . $c['konum']) : $ad;
            $cihazlar[] = $c;
        }
    }
    // Kanallar
    $kanallar = [];
    if(count($kanalIdler) > 0){
        $placeholders = implode(',', array_fill(0, count($kanalIdler), '?'));
        $query = "SELECT id, ad FROM kanallar WHERE id IN ($placeholders) ORDER BY ad ASC";
        $stmt = $db->prepare($query);
        $stmt->execute(array_keys($kanalIdler));
        foreach($stmt as $k){
            $kanallar[] = $k;
        }
    }

    echo json_encode([
        'cihazlar' => $cihazlar,
        'kanallar' => $kanallar
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'cihazlar' => [],
        'kanallar' => []
    ]);
}
?>
