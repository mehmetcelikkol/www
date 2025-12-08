<?php
// Basit hata kontrolü ve JSON header
header('Content-Type: application/json; charset=utf-8');
// Xdebug/HTML hata çıktısını engelle
if (function_exists('ini_set')) {
    ini_set('display_errors', '0');
}

try {
    // WAMP varsayılanları: kullanıcı=root, şifre boş. Gerekirse diğer sayfalardaki bilgiyi kullanın.
    $dsn = 'mysql:host=localhost;dbname=scada1;charset=utf8mb4';
    $user = 'root';
    $pass = '';
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opts);

    // tirlar’dan verileri çek
    $rows = $pdo->query("
        SELECT id, plaka, port, dolumbaslama, dolumbitis, toplam, tarih, durdurma_sekli
        FROM tirlar
        ORDER BY COALESCE(dolumbitis, dolumbaslama, tarih) DESC, id DESC
        LIMIT 500
    ")->fetchAll();

    // tir_islemleri şemasına dönüştür
    $out = [];
    foreach ($rows as $r) {
        // datetime alanlarını doğrudan kullan
        $start = $r['dolumbaslama'] ?: null; // 'YYYY-MM-DD HH:MM:SS'
        $end   = $r['dolumbitis']  ?: null;

        // port yoksa tir_no varsayımı (opsiyonel)
        $tirNo = empty($r['port']) ? 1 : null;

        // Veritabanındaki 'toplam' zaten Ton
        $toplamTon = (float)$r['toplam'];

        $out[] = [
            'id' => (int)$r['id'],
            'plaka' => $r['plaka'],
            'tir_no' => $tirNo,
            'port' => ($r['port'] !== null ? (int)$r['port'] : null),
            'hedef_ton' => null,
            'baslama_zamani' => $start,
            'bitis_zamani' => $end,
            'toplam_ton' => round($toplamTon, 2),
            'durdurma_sekli' => $r['durdurma_sekli'] ?: null,
            'kullanici' => null,
            'aciklama' => null
        ];
    }

    // İstatistikler (Ton)
    $total = count($out);
    $completed = 0; $ongoing = 0; $todayOps = 0; $totalWeightTon = 0.0; $todayWeightTon = 0.0; $lastOpTime = null;
    $todayStr = date('Y-m-d');
    foreach ($out as $row) {
        $totalWeightTon += (float)($row['toplam_ton'] ?? 0);
        if (!empty($row['baslama_zamani']) && strncmp($row['baslama_zamani'], $todayStr, 10) === 0) $todayOps++;
        if (!empty($row['bitis_zamani'])) {
            $completed++;
            if (strncmp($row['bitis_zamani'], $todayStr, 10) === 0) $todayWeightTon += (float)($row['toplam_ton'] ?? 0);
            if (!$lastOpTime || $row['bitis_zamani'] > $lastOpTime) $lastOpTime = $row['bitis_zamani'];
        } else {
            $ongoing++;
        }
    }
    $avgWeightTon = $total ? ($totalWeightTon / $total) : 0;

    echo json_encode([
        'status' => 'success',
        'data' => $out,
        'stats' => [
            'total_operations' => $total,
            'completed_operations' => $completed,
            'ongoing_operations' => $ongoing,
            'today_operations' => $todayOps,
            'total_weight_ton' => round($totalWeightTon, 2),
            'avg_weight_ton' => round($avgWeightTon, 2),
            'today_weight_ton' => round($todayWeightTon, 2),
            'today_completed' => $completed,
            'last_operation_time' => $lastOpTime
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Sunucu hatası: '.$e->getMessage(),
        'data' => [],
        'stats' => []
    ], JSON_UNESCAPED_UNICODE);
}