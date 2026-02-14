<?php
// Veritabanı bağlantısını db.php'den al
require "db.php";
date_default_timezone_set('Europe/Istanbul');

// Cihaz listesini al
$cihazlar = [];
$result = $db->query("SELECT DISTINCT cihaz_kimligi FROM cihaz_son_durum ORDER BY cihaz_kimligi");
while ($row = $result->fetch_assoc()) {
    $cihazlar[] = $row['cihaz_kimligi'];
}

$selected_device = $_GET['cihaz'] ?? null;
$table_data = [];
$chart_data = [];
$stats = [
    'min' => PHP_FLOAT_MAX,
    'max' => PHP_FLOAT_MIN,
    'avg' => 0,
    'last_10' => []
];

// Limit bilgileri
$limits = [
    'min_limit' => null,
    'max_limit' => null,
    'alarm_active' => true
];

if ($selected_device && in_array($selected_device, $cihazlar)) {
    // Cihaz limitlerini al
    $limit_stmt = $db->prepare("
        SELECT min_agirlik, max_agirlik, alarm_aktif 
        FROM cihaz_limitleri 
        WHERE cihaz_kimligi = ?
    ");
    $limit_stmt->bind_param("s", $selected_device);
    $limit_stmt->execute();
    $limit_result = $limit_stmt->get_result();
    
    if ($limit_row = $limit_result->fetch_assoc()) {
        $limits['min_limit'] = (float)$limit_row['min_agirlik'];
        $limits['max_limit'] = (float)$limit_row['max_agirlik'];
        $limits['alarm_active'] = (bool)$limit_row['alarm_aktif'];
    }
    
    // Tablo verilerini al
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(alinan_zaman, '%Y-%m-%d %H:%i:%s') as tarih,
            agirlik_degeri,
            CASE 
                WHEN stabil_mi = 1 THEN 'Evet' 
                ELSE 'Hayır' 
            END as stabil_durumu,
            paket_no,
            rs485_hata_sayisi,
            darbeSayisi,
            calisma_suresi_saniye,
            COALESCE(cihaz_versiyonu, stabil_mi) as gosterilecek_versiyon,
            CASE 
                WHEN calisma_suresi_saniye > 60 THEN 'ONLINE'
                ELSE 'OFFLINE'
            END as durum
        FROM cihaz_paketleri 
        WHERE cihaz_kimligi = ? 
        ORDER BY alinan_zaman DESC 
        LIMIT 100
    ");
    $stmt->bind_param("s", $selected_device);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $table_data[] = $row;
        
        // Grafik verisi için (son 50 kayıt)
        if (count($chart_data) < 100) {
            $chart_data[] = [
                'time' => $row['tarih'],
                'value' => (float)$row['agirlik_degeri']
            ];
        }
        
        // İstatistikler için
        $weight = (float)$row['agirlik_degeri'];
        if ($weight > 0) {
            $stats['min'] = min($stats['min'], $weight);
            $stats['max'] = max($stats['max'], $weight);
        }
    }
    
    // Grafik verisini ters çevir (eskiden yeniye)
    $chart_data = array_reverse($chart_data);
    
    // Son 10 değer
    $stats['last_10'] = array_slice(array_column($chart_data, 'value'), -10);
    $stats['avg'] = count($stats['last_10']) > 0 ? 
        array_sum($stats['last_10']) / count($stats['last_10']) : 0;
    
    // Min/max reset
    if ($stats['min'] == PHP_FLOAT_MAX) $stats['min'] = 0;
    if ($stats['max'] == PHP_FLOAT_MIN) $stats['max'] = 0;
}
?>

<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cihaz Verileri Görüntüleme</title>
        <link rel="icon" type="image/x-icon" href="images/favicon.ico">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                padding: 20px; 
                background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
                min-height: 100vh;
                color: #333;
            }
            
            .container {
                max-width: 1600px;
                margin: 0 auto;
            }
            
            .header {
                background: white;
                padding: 25px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .header img {
                height: 50px;
                width: auto;
            }
            
            .header h1 {
                color: #4a5568;
                font-size: 28px;
                margin-bottom: 10px;
            }
            
            .devices-panel {
                background: white;
                padding: 25px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                margin-bottom: 25px;
            }
            
            .devices-panel h2 {
                color: #4a5568;
                margin-bottom: 20px;
                font-size: 22px;
            }
            
            .device-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }
            
            .device-btn {
                padding: 14px 24px;
                background: #edf2f7;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                color: #4a5568;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .device-btn:hover {
                background: #3b82f6;
                color: white;
                border-color: #3b82f6;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
            }
            
            .device-btn.active {
                background: #3b82f6;
                color: white;
                border-color: #3b82f6;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            }
            
            .dashboard-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 25px;
                margin-bottom: 25px;
            }
            
            .chart-container {
                background: white;
                padding: 25px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                position: relative;
                height: 600px;
                display: flex;
                flex-direction: column;
            }
            
            .chart-container h2 {
                color: #4a5568;
                margin-bottom: 20px;
                font-size: 22px;
                display: flex;
                align-items: center;
                gap: 10px;
                flex-shrink: 0;
            }
            
            .chart-container canvas {
                flex: 1;
                max-height: 450px !important;
            }
            
            .chart-container h2:before {
                content: "📈";
                font-size: 24px;
            }
            
            .limit-info {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .limit-badge {
                padding: 12px 15px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
            }
            
            .limit-badge.min {
                background: #fed7d7;
                color: #c53030;
                border: 2px solid #fc8181;
            }
            
            .limit-badge.max {
                background: #c6f6d5;
                color: #276749;
                border: 2px solid #9ae6b4;
            }
            
            .limit-badge.alarm-on {
                background: #feebc8;
                color: #9c4221;
                border: 2px solid #fbd38d;
            }
            
            .limit-badge.alarm-off {
                background: #e2e8f0;
                color: #4a5568;
                border: 2px solid #cbd5e0;
            }
            
            .stats-container {
                background: white;
                padding: 25px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            
            .stats-container h2 {
                color: #4a5568;
                margin-bottom: 20px;
                font-size: 22px;
            }
            
            .stat-card {
                background: #f7fafc;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 12px;
                border-left: 4px solid #667eea;
            }
            
            .stat-card h3 {
                color: #718096;
                font-size: 12px;
                text-transform: uppercase;
                margin-bottom: 6px;
            }
            
            .stat-card .value {
                color: #4a5568;
                font-size: 24px;
                font-weight: 700;
            }
            
            .table-container {
                background: white;
                padding: 25px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                overflow-x: auto;
            }
            
            .table-container h2 {
                color: #4a5568;
                margin-bottom: 20px;
                font-size: 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .download-btn {
                background: #48bb78;
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.3s;
                display: inline-block;
                font-size: 14px;
            }
            
            .download-btn:hover {
                background: #38a169;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                min-width: 1000px;
            }
            
            thead {
                background: #4a5568;
                color: white;
            }
            
            th {
                padding: 16px 12px;
                text-align: left;
                font-weight: 600;
                font-size: 14px;
            }
            
            tbody tr {
                border-bottom: 1px solid #e2e8f0;
                transition: background 0.2s;
            }
            
            tbody tr:hover {
                background: #f7fafc;
            }
            
            td {
                padding: 12px;
                color: #4a5568;
                font-size: 14px;
            }
            
            .status-online {
                color: #38a169;
                font-weight: 600;
                background: #f0fff4;
                padding: 4px 10px;
                border-radius: 4px;
                display: inline-block;
            }
            
            .status-offline {
                color: #e53e3e;
                font-weight: 600;
                background: #fff5f5;
                padding: 4px 10px;
                border-radius: 4px;
                display: inline-block;
            }
            
            .no-device {
                text-align: center;
                padding: 60px 20px;
                color: #718096;
                background: white;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            
            .no-device h2 {
                font-size: 24px;
                margin-bottom: 15px;
            }
            
            @media (max-width: 1200px) {
                .dashboard-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="images/btv-full-logo.png" alt="BTV Logo">
                <h1>📊 Cihaz Verileri Analizi</h1>
                <img src="images/logo.png" alt="Logo">
            </div>
            
            <div class="devices-panel">
                <h2>Cihazları Seç</h2>
                <div class="device-buttons">
                    <?php foreach ($cihazlar as $device): ?>
                        <a href="?cihaz=<?php echo urlencode($device); ?>" 
                        class="device-btn <?php echo ($selected_device === $device) ? 'active' : ''; ?>">
                            📡 <?php echo htmlspecialchars($device); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($selected_device && count($table_data) > 0): ?>
                <div class="dashboard-grid">
                    <div>
                        <div class="chart-container">
                            <h2>Ağırlık Değerleri Grafiği</h2>
                            <canvas id="weightChart"></canvas>
                        </div>
                    </div>
                    
                    <div>
                        <!--
                        <div class="stats-container">
                            
                            <h2>İstatistikler</h2>
                            <div class="limit-info">
                                <?php if ($limits['min_limit'] !== null): ?>
                                    <div class="limit-badge min">Min Limit: <?php echo $limits['min_limit']; ?> kg</div>
                                <?php endif; ?>
                                <?php if ($limits['max_limit'] !== null): ?>
                                    <div class="limit-badge max">Max Limit: <?php echo $limits['max_limit']; ?> kg</div>
                                <?php endif; ?>
                                <div class="limit-badge <?php echo $limits['alarm_active'] ? 'alarm-on' : 'alarm-off'; ?>">
                                    Alarm: <?php echo $limits['alarm_active'] ? 'AÇIK' : 'KAPALI'; ?>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <h3>Minimum</h3>
                                <div class="value"><?php echo number_format($stats['min'], 2); ?> kg</div>
                            </div>
                            <div class="stat-card">
                                <h3>Maksimum</h3>
                                <div class="value"><?php echo number_format($stats['max'], 2); ?> kg</div>
                            </div>
                            <div class="stat-card">
                                <h3>Ortalama</h3>
                                <div class="value"><?php echo number_format($stats['avg'], 2); ?> kg</div>
                            </div>
                        </div>
                                
                    </div>
                    -->
                </div>
                
                <div class="table-container">
                    <h2>
                        <span>Son 100 Kayıt</span>
                        <a href="export_csv.php?cihaz=<?php echo urlencode($selected_device); ?>" class="download-btn">
                            📥 Excel'e Aktar
                        </a>
                    </h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Tarih/Saat</th>
                                <th>Ağırlık (kg)</th>
                                <th>Durumu</th>
                                <th>Paket No</th>
                                <th>Hata Sayısı</th>
                                <th>Darbe Sayısı</th>
                                <th>Çalışma Süresi (s)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['tarih']); ?></td>
                                    <td><?php echo number_format($row['agirlik_degeri'], 2); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($row['durum'] === 'ONLINE' ? 'online' : 'offline'); ?>">
                                            <?php echo htmlspecialchars($row['durum']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['paket_no']); ?></td>
                                    <td><?php echo $row['rs485_hata_sayisi']; ?></td>
                                    <td><?php echo $row['darbeSayisi']; ?></td>
                                    <td><?php echo $row['calisma_suresi_saniye']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-device">
                    <h2>Cihaz Seçiniz</h2>
                    <p>Veri görmek için yukarıdan bir cihaz seçin.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
            const chartData = <?php echo json_encode($chart_data); ?>;
            
            if (chartData.length > 0) {
                const ctx = document.getElementById('weightChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.map(d => d.time),
                        datasets: [{
                            label: 'Ağırlık (kg)',
                            data: chartData.map(d => d.value),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                labels: { color: '#4a5568' }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: { color: '#4a5568' },
                                grid: { color: '#e2e8f0' }
                            },
                            x: {
                                ticks: { 
                                    color: '#4a5568',
                                    maxRotation: 45,
                                    minRotation: 45
                                },
                                grid: { color: '#e2e8f0' }
                            }
                        }
                    }
                });
            }
        </script>
    </body>
</html>