<?php
header('Content-Type: application/json; charset=utf-8');
$dbDosya = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/energy.db';
try {
    $db = new PDO("sqlite:$dbDosya");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    $cihaz = $input['cihaz'] ?? '';
    $kanal = $input['kanal'] ?? '';
    $baslangic = $input['baslangic'] ?? '';
    $bitis = $input['bitis'] ?? '';
    $limit = isset($input['limit']) ? intval($input['limit']) : 100;
    if($limit < 1) $limit = 10;
    if($limit > 1000) $limit = 1000;

    // Tarih formatı düzeltme (datetime-local: '2025-08-26T09:00' -> '2025-08-26 09:00:00')
    function fixDate($d) {
        if(!$d) return '';
        $d = str_replace('T', ' ', $d);
        if(strlen($d) == 16) $d .= ':00';
        return $d;
    }
    $baslangic = fixDate($baslangic);
    $bitis = fixDate($bitis);

    $datasets = [];
    $labels = [];
    if(!$cihaz && !$kanal){ // Hem cihaz hem kanal tümü
        $cihazlar = $db->query("SELECT id, cihaz_adi FROM cihazlar ORDER BY cihaz_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
        $kanallar = $db->query("SELECT id, ad, unit FROM kanallar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cihazlar as $c){
            foreach($kanallar as $k){
                $where = ["o.cihaz_id = ?", "o.kanal_id = ?"];
                $params = [$c['id'], $k['id']];
                if($baslangic){
                    $where[] = "o.kayit_zamani >= ?";
                    $params[] = $baslangic;
                }
                if($bitis){
                    $where[] = "o.kayit_zamani <= ?";
                    $params[] = $bitis;
                }
                $whereSql = $where ? ("WHERE " . implode(' AND ', $where)) : '';
                $sql = "SELECT o.kayit_zamani, o.deger FROM olcumler o $whereSql ORDER BY o.kayit_zamani ASC LIMIT $limit";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $data = [];
                foreach($rows as $row){
                    if(!in_array($row['kayit_zamani'], $labels)) $labels[] = $row['kayit_zamani'];
                    $data[] = $row['deger'];
                }
                if(count($data) > 0){
                    $label = $c['cihaz_adi'] . ' - ' . $k['ad'];
                    if($k['unit']) $label .= ' (' . $k['unit'] . ')';
                    $datasets[] = [
                        'label' => $label,
                        'data' => $data
                    ];
                }
            }
        }
    } else if($cihaz && !$kanal){ // Tüm kanallar, tek cihaz
        $kanallar = $db->query("SELECT id, ad, unit FROM kanallar ORDER BY ad ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach($kanallar as $k){
            $where = ["o.cihaz_id = ?", "o.kanal_id = ?"];
            $params = [$cihaz, $k['id']];
            if($baslangic){
                $where[] = "o.kayit_zamani >= ?";
                $params[] = $baslangic;
            }
            if($bitis){
                $where[] = "o.kayit_zamani <= ?";
                $params[] = $bitis;
            }
            $whereSql = $where ? ("WHERE " . implode(' AND ', $where)) : '';
            $sql = "SELECT o.kayit_zamani, o.deger FROM olcumler o $whereSql ORDER BY o.kayit_zamani ASC LIMIT $limit";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach($rows as $row){
                if(!in_array($row['kayit_zamani'], $labels)) $labels[] = $row['kayit_zamani'];
                $data[] = $row['deger'];
            }
            if(count($data) > 0){
                $label = $k['ad'];
                if($k['unit']) $label .= ' (' . $k['unit'] . ')';
                $datasets[] = [
                    'label' => $label,
                    'data' => $data
                ];
            }
        }
    } else if(!$cihaz && $kanal){ // Tüm cihazlar, tek kanal
        $cihazlar = $db->query("SELECT id, cihaz_adi FROM cihazlar ORDER BY cihaz_adi ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cihazlar as $c){
            $where = ["o.cihaz_id = ?", "o.kanal_id = ?"];
            $params = [$c['id'], $kanal];
            if($baslangic){
                $where[] = "o.kayit_zamani >= ?";
                $params[] = $baslangic;
            }
            if($bitis){
                $where[] = "o.kayit_zamani <= ?";
                $params[] = $bitis;
            }
            $whereSql = $where ? ("WHERE " . implode(' AND ', $where)) : '';
            $sql = "SELECT o.kayit_zamani, o.deger FROM olcumler o $whereSql ORDER BY o.kayit_zamani ASC LIMIT $limit";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data = [];
            foreach($rows as $row){
                if(!in_array($row['kayit_zamani'], $labels)) $labels[] = $row['kayit_zamani'];
                $data[] = $row['deger'];
            }
            if(count($data) > 0){
                $label = $c['cihaz_adi'];
                $datasets[] = [
                    'label' => $label,
                    'data' => $data
                ];
            }
        }
    } else { // Tek cihaz ve tek kanal
        $where = [];
        $params = [];
        if($cihaz){
            $where[] = "o.cihaz_id = ?";
            $params[] = $cihaz;
        }
        if($kanal){
            $where[] = "o.kanal_id = ?";
            $params[] = $kanal;
        }
        if($baslangic){
            $where[] = "o.kayit_zamani >= ?";
            $params[] = $baslangic;
        }
        if($bitis){
            $where[] = "o.kayit_zamani <= ?";
            $params[] = $bitis;
        }
        $whereSql = $where ? ("WHERE " . implode(' AND ', $where)) : '';
        $sql = "SELECT o.kayit_zamani, o.deger, k.ad, k.unit FROM olcumler o JOIN kanallar k ON o.kanal_id = k.id $whereSql ORDER BY o.kayit_zamani ASC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = [];
        $label = '';
        foreach($stmt as $row){
            $labels[] = $row['kayit_zamani'];
            $data[] = $row['deger'];
            if(!$label) {
                $label = $row['ad'];
                if($row['unit']) $label .= ' (' . $row['unit'] . ')';
            }
        }
        $datasets[] = [
            'label' => $label ? $label : 'Enerji',
            'data' => $data
        ];
    }
    echo json_encode([
        'labels' => $labels,
        'datasets' => $datasets
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
