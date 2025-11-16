<?php
header('Content-Type: application/json');
require_once '../config.php'; // Veritabanı bağlantısı

$response = [
    'status' => 'error',
    'message' => 'Bilinmeyen bir hata oluştu.',
    'data' => [],
    'stats' => []
];

if ($pdo) {
    try {
        // Ana veri sorgusu
        $sql = "SELECT * FROM tirlar ORDER BY dolumbaslama DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // İstatistikleri hesapla
        $total_weight = array_sum(array_column($data, 'toplam'));
        $record_count = count($data);
        $avg_weight = $record_count > 0 ? $total_weight / $record_count : 0;
        
        $completed_operations = 0;
        $today_operations = 0;
        $today_weight = 0;
        $today_completed = 0;
        $today_date_str = date('Y-m-d');

        foreach ($data as $row) {
            $start_valid = !empty($row['dolumbaslama']) && strtotime($row['dolumbaslama']);
            $end_valid = !empty($row['dolumbitis']) && strtotime($row['dolumbitis']);

            if ($end_valid) {
                $completed_operations++;
            }
            
            if ($start_valid && strpos($row['dolumbaslama'], $today_date_str) === 0) {
                $today_operations++;
                $today_weight += $row['toplam'] ?? 0;
                if ($end_valid) {
                    $today_completed++;
                }
            }
        }
        
        $ongoing_operations = $record_count - $completed_operations;

        // Yanıtı hazırla
        $response['status'] = 'success';
        $response['message'] = 'Veriler başarıyla çekildi.';
        $response['data'] = $data;
        $response['stats'] = [
            'total_operations' => $record_count,
            'completed_operations' => $completed_operations,
            'ongoing_operations' => $ongoing_operations,
            'today_operations' => $today_operations,
            'total_weight' => $total_weight,
            'avg_weight' => $avg_weight,
            'today_weight' => $today_weight,
            'today_completed' => $today_completed,
            'last_operation_time' => !empty($data) ? date('H:i', strtotime($data[0]['dolumbaslama'] ?? '')) : 'Yok'
        ];

    } catch (PDOException $e) {
        $response['message'] = "Veritabanı hatası: " . $e->getMessage();
    }
} else {
    $response['message'] = 'Veritabanı bağlantısı kurulamadı.';
}

echo json_encode($response);
?>