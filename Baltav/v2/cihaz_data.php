<?php
// SiloSense V2 - AJAX Veri Kaynağı
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$cihaz_kimligi = isset($_GET['id']) ? guvenli($_GET['id']) : '';

if (empty($cihaz_kimligi)) {
    echo json_encode(['error' => 'Cihaz Kimliği Eksik']);
    exit;
}

try {
    $db = Database::getConnection();

    // 1. Canlı Veri (En Son Gelen Paket)
    // cihaz_son_durum yerine cihaz_paketleri tablosunun son kaydını alıyoruz.
    $sql_live = "SELECT * FROM cihaz_paketleri 
                 WHERE cihaz_kimligi = :id 
                 ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($sql_live);
    $stmt->execute([':id' => $cihaz_kimligi]);
    $live_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Geçmiş Veriler (Son 20 Kayıt)
    $sql_history = "SELECT * FROM cihaz_paketleri 
                    WHERE cihaz_kimligi = :id 
                    ORDER BY id DESC LIMIT 20";
    $stmt = $db->prepare($sql_history);
    $stmt->execute([':id' => $cihaz_kimligi]);
    $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Limit Bilgileri (Kapasite Hesabı İçin)
    $sql_limit = "SELECT max_agirlik FROM cihaz_limitleri WHERE cihaz_kimligi = :id";
    $stmt = $db->prepare($sql_limit);
    $stmt->execute([':id' => $cihaz_kimligi]);
    $limit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $kapasite = ($limit && $limit['max_agirlik'] > 0) ? floatval($limit['max_agirlik']) : 20000; // Varsayılan

    // 4. Tahmin Algoritması (Basit Tüketim Analizi)
    $tahmin = null;
    $tahmin_metni = "Veri yetersiz";
    
    if ($live_data) {
        // 24 Saat önceki veriyi bul
        $sql_old = "SELECT agirlik_degeri, alinan_zaman FROM cihaz_paketleri 
                    WHERE cihaz_kimligi = :id 
                    AND alinan_zaman < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                    ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($sql_old);
        $stmt->execute([':id' => $cihaz_kimligi]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($old_data) {
            $fark_agirlik = floatval($old_data['agirlik_degeri']) - floatval($live_data['agirlik_degeri']);
            
            // Eğer tüketim varsa (Fark pozitif)
            if ($fark_agirlik > 10) { // En az 10kg fark olsun
                $saatlik_tuketim = $fark_agirlik / 24;
                $kalan_saat = floatval($live_data['agirlik_degeri']) / $saatlik_tuketim;
                
                if ($kalan_saat < 24) {
                    $tahmin_metni = round($kalan_saat) . " Saat sonra";
                } else {
                    $kalan_gun = round($kalan_saat / 24, 1);
                    $tahmin_metni = $kalan_gun . " Gün sonra";
                }
                
                // Tahmini Tarih
                $bitis_tarihi = date("d.m.Y H:i", strtotime("+$kalan_saat hours"));
                $tahmin = [
                    'metin' => $tahmin_metni,
                    'tarih' => $bitis_tarihi,
                    'hiz' => round($saatlik_tuketim) . " kg/saat"
                ];
            } elseif ($fark_agirlik < -100) {
                $tahmin_metni = "Dolum Yapılıyor ⬆️";
            } else {
                $tahmin_metni = "Tüketim Yok ➖";
            }
        }
    }

    // Yanıt Oluşturma
    $response = [
        'live' => [
            'agirlik' => floatval($live_data['agirlik_degeri'] ?? 0),
            'paket' => intval($live_data['paket_no'] ?? 0),
            'zaman' => $live_data['alinan_zaman'] ?? '-',
            'stabil' => intval($live_data['stabil_mi'] ?? 0),
            'yuzde' => ($live_data) ? min(100, round(($live_data['agirlik_degeri'] / $kapasite) * 100)) : 0
        ],
        'tahmin' => $tahmin ?? ['metin' => $tahmin_metni],
        'history' => [],
        'meta' => [
            'kapasite' => $kapasite
        ]
    ];

    // Geçmiş Veriyi Formatla
    foreach ($history_data as $row) {
        $response['history'][] = [
            'agirlik' => floatval($row['agirlik_degeri']),
            'paket' => intval($row['paket_no']),
            'tarih' => date('H:i:s', strtotime($row['alinan_zaman'])), // Sadece saat
            'stabil' => intval($row['stabil_mi']),
            'darbe' => intval($row['darbeSayisi'])
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
