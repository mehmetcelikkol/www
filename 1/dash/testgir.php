<?php
// Gelen POST verilerini logla
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = [
        'tarih' => date('Y-m-d H:i:s'),
        'veriler' => $_POST,
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    file_put_contents('post_log.txt', json_encode($data) . "\n", FILE_APPEND);
}

// Son 5 kaydı göster
$logs = array_slice(array_filter(explode("\n", @file_get_contents('post_log.txt'))), -5);
echo "<h3>Son 5 POST İsteği:</h3>";
foreach ($logs as $log) {
    $entry = json_decode($log, true);
    echo "<div style='margin:10px; padding:10px; border:1px solid #ccc'>";
    echo "<strong>Tarih:</strong> " . $entry['tarih'] . "<br>";
    echo "<strong>IP:</strong> " . $entry['ip'] . "<br>";
    echo "<strong>Veriler:</strong> <pre>" . print_r($entry['veriler'], true) . "</pre>";
    echo "</div>";
}
?>