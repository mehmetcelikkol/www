<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

@require_once '../includes/database.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}

$completed_operations = [];
try {
    // Sadece son 1000 operasyonu çekerek performansı garanti altına alalım
    $op_sql = "SELECT gemi_adi, Gemi_no, tonaj, islem, kayit_tarihi FROM (
        SELECT * FROM gemioperasyon ORDER BY kayit_tarihi DESC LIMIT 1000
    ) sub ORDER BY kayit_tarihi ASC";
    
    $op_stmt = $pdo->query($op_sql);
    $all_ops = $op_stmt->fetchAll(PDO::FETCH_ASSOC);

    $started_ops_stack = [];
    foreach ($all_ops as $op) {
        $op_key = $op['Gemi_no'];
        if ($op['islem'] === 'basla') {
            if (!isset($started_ops_stack[$op_key])) $started_ops_stack[$op_key] = [];
            $started_ops_stack[$op_key][] = $op;
        } elseif ($op['islem'] === 'dur' && isset($started_ops_stack[$op_key]) && !empty($started_ops_stack[$op_key])) {
            $start_op = array_shift($started_ops_stack[$op_key]);
            $completed_operations[] = [
                'gemi_adi' => $start_op['gemi_adi'],
                'start_time_ymd' => date('Y-m-d', strtotime($start_op['kayit_tarihi'])),
                'end_time_ymd' => date('Y-m-d', strtotime($op['kayit_tarihi'])),
                'start_time_dmy' => date('d.m.y', strtotime($start_op['start_time']))
            ];
        }
    }
    
    echo json_encode(array_reverse($completed_operations));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Operasyonlar çekilirken bir hata oluştu: ' . $e->getMessage()]);
}
?>