<?php
// ERROR RAPORLAMA AÇ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "db.php";
date_default_timezone_set('Europe/Istanbul');

$db->query("CREATE TABLE IF NOT EXISTS site_ziyaretler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_adresi VARCHAR(100),
    user_agent TEXT,
    ziyaret_zamani DATETIME,
    sayfa VARCHAR(255),
    INDEX idx_ip (ip_adresi),
    INDEX idx_zaman (ziyaret_zamani)
)");

// Ziyareti kaydet
$visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$current_page = $_SERVER['REQUEST_URI'] ?? '/';
$visit_time = date('Y-m-d H:i:s');

$stmt = $db->prepare("INSERT INTO site_ziyaretler (ip_adresi, user_agent, ziyaret_zamani, sayfa) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $visitor_ip, $user_agent, $visit_time, $current_page);
$stmt->execute();

// Site istatistiklerini hesapla
$site_stats = [
    'total_visits' => 0,
    'unique_visitors' => 0,
    'today_visits' => 0,
    'today_unique' => 0,
    'this_week_visits' => 0,
    'this_month_visits' => 0,
    'avg_daily_visits' => 0,
    'peak_hour' => 0
];

$result = $db->query("SELECT COUNT(*) as total FROM site_ziyaretler");
$site_stats['total_visits'] = $result->fetch_assoc()['total'];

$result = $db->query("SELECT COUNT(DISTINCT ip_adresi) as unique_count FROM site_ziyaretler");
$site_stats['unique_visitors'] = $result->fetch_assoc()['unique_count'];

$result = $db->query("SELECT COUNT(*) as today FROM site_ziyaretler WHERE DATE(ziyaret_zamani) = CURDATE()");
$site_stats['today_visits'] = $result->fetch_assoc()['today'];

$result = $db->query("SELECT COUNT(DISTINCT ip_adresi) as today_unique FROM site_ziyaretler WHERE DATE(ziyaret_zamani) = CURDATE()");
$site_stats['today_unique'] = $result->fetch_assoc()['today_unique'];

$result = $db->query("SELECT COUNT(*) as week FROM site_ziyaretler WHERE YEARWEEK(ziyaret_zamani) = YEARWEEK(NOW())");
$site_stats['this_week_visits'] = $result->fetch_assoc()['week'];

$result = $db->query("SELECT COUNT(*) as month FROM site_ziyaretler WHERE YEAR(ziyaret_zamani) = YEAR(NOW()) AND MONTH(ziyaret_zamani) = MONTH(NOW())");
$site_stats['this_month_visits'] = $result->fetch_assoc()['month'];

$result = $db->query("SELECT COUNT(*) / 30 as avg_daily FROM site_ziyaretler WHERE ziyaret_zamani >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$site_stats['avg_daily_visits'] = round($result->fetch_assoc()['avg_daily'], 1);

$result = $db->query("SELECT HOUR(ziyaret_zamani) as peak_hour, COUNT(*) as visit_count FROM site_ziyaretler GROUP BY HOUR(ziyaret_zamani) ORDER BY visit_count DESC LIMIT 1");
$peak_data = $result->fetch_assoc();
$site_stats['peak_hour'] = $peak_data ? $peak_data['peak_hour'] : 0;


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
    <title>SiloSense Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    // <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
    <style>

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            height: 600px; /* 450'den 600'e çıkardık */
            width: 100%;
            margin-bottom: 25px;
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
        }
        .download-btn:hover {
            background: #38a169;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            flex-wrap: wrap;
        }
        
        .header h1 {
            color: #4a5568;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header h1 span {
            color: #667eea;
        }
        
        .server-time {
            background: #4a5568;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .server-time #clock {
            font-family: 'Courier New', monospace;
            background: #2d3748;
            padding: 8px 15px;
            border-radius: 6px;
            margin-left: 10px;
        }
        
        .time-info {
            background: #e8f4fc;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #2c5282;
            border-left: 4px solid #4299e1;
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
            display: flex;
            align-items: center;
            gap: 10px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .device-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .device-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .device-btn:before {
            content: "📡";
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .chart-container h2 {
            color: #4a5568;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-container h2:before {
            content: "📈";
            font-size: 24px;
        }
        
        .limit-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .limit-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-container h2:before {
            content: "📊";
            font-size: 24px;
        }
        
        .stat-card {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
        }
        
        .stat-card.min {
            border-left-color: #e53e3e;
        }
        
        .stat-card.max {
            border-left-color: #38a169;
        }
        
        .stat-card.avg {
            border-left-color: #d69e2e;
        }
        
        .stat-card.limit-min {
            border-left-color: #fc8181;
        }
        
        .stat-card.limit-max {
            border-left-color: #9ae6b4;
        }
        
        .stat-card h3 {
            color: #718096;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            color: #4a5568;
            font-size: 28px;
            font-weight: 700;
        }
        
        .stat-card .unit {
            color: #a0aec0;
            font-size: 16px;
            margin-left: 5px;
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
            align-items: center;
            gap: 10px;
        }
        
        .table-container h2:before {
            content: "📋";
            font-size: 24px;
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
            font-size: 15px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f7fafc;
        }
        
        td {
            padding: 14px 12px;
            color: #4a5568;
        }
        
        .status-online {
            color: #38a169;
            font-weight: 600;
            background: #f0fff4;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-offline {
            color: #e53e3e;
            font-weight: 600;
            background: #fff5f5;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .version-badge {
            background: #d6bcfa;
            color: #553c9a;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
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
        
        .no-device p {
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }
        
        .no-device:before {
            content: "👆";
            font-size: 48px;
            display: block;
            margin-bottom: 20px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .device-btn {
                flex: 1;
                min-width: 150px;
                justify-content: center;
            }
        }
        
        .out-of-limit {
            background: #fff5f5 !important;
            border-left: 5px solid #e53e3e !important;
        }
        
        .out-of-limit td:first-child {
            color: #e53e3e;
            font-weight: bold;
        }

        /* KOMPAKT İSTATİSTİKLER İÇİN STİL */
.stats-container.compact {
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat-item {
    background: #f7fafc;
    padding: 15px;
    border-radius: 10px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-left: 4px solid #667eea;
    transition: transform 0.2s;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 24px;
    background: white;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.stat-content {
    flex: 1;
}

.stat-title {
    font-size: 12px;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #4a5568;
    margin-bottom: 3px;
}

.stat-sub {
    font-size: 11px;
    color: #a0aec0;
}

.limit-value {
    font-weight: 600;
    color: #4a5568;
}

.stat-alert {
    color: #e53e3e;
    margin-left: 5px;
    font-size: 14px;
}

/* MIN/MAX için özel border */
.stats-grid .stat-item:nth-child(1) {
    border-left-color: #e53e3e; /* Min için kırmızı */
}

.stats-grid .stat-item:nth-child(2) {
    border-left-color: #38a169; /* Max için yeşil */
}

/* Responsive */
@media (max-width: 1400px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* SİTE İSTATİSTİKLERİ */
.site-stats-container {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin-top: 25px;
}

.site-stats-container h2 {
    color: #4a5568;
    margin-bottom: 20px;
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.site-stats-container h2:before {
    content: "🌐";
    font-size: 24px;
}

.site-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.site-stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s;
}

.site-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.site-stat-card.green {
    background: linear-gradient(135deg, #38a169 0%, #276749 100%);
}

.site-stat-card.orange {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

.site-stat-card.blue {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.site-stat-card.red {
    background: linear-gradient(135deg, #fc8181 0%, #e53e3e 100%);
}

.site-stat-card.teal {
    background: linear-gradient(135deg, #4fd1c5 0%, #319795 100%);
}

.site-stat-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.site-stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.site-stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.site-stat-subtext {
    font-size: 12px;
    opacity: 0.8;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📊 <span>SiloSense</span> Monitoring Dashboard</h1>
                <p>Gerçek zamanlı silo ağırlık takip sistemi</p>
                <div class="time-info">
                  <!--  ⏰ ESP32 ile sunucu arasında 3 saat fark var (UTC+3 vs NTP ayarlı değil) -->
                </div>
            </div>
            <div class="server-time">
                alan tutucu (ileride lazım olacak)
                <!-- Sunucu Saati: <span id="clock"><?php //echo date('Y-m-d H:i:s'); ?></span> -->
            </div>
        </div>
        
        <div class="devices-panel">
            <h2>Bağlı Cihazlar</h2>
            <div class="device-buttons">
                <?php if (empty($cihazlar)): ?>
                    <p style="color: #718096;">Henüz bağlı cihaz yok.</p>
                <?php else: ?>
                    <?php foreach ($cihazlar as $cihaz): ?>
                        <button class="device-btn <?php echo ($selected_device == $cihaz) ? 'active' : ''; ?>" 
                                onclick="selectDevice('<?php echo $cihaz; ?>')">
                            <?php echo $cihaz; ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$selected_device): ?>
            <div class="no-device">
                <h2>Bir Cihaz Seçin</h2>
                <p>Yukarıdaki listeden izlemek istediğiniz cihaza tıklayın.<br>
                Cihaz detayları, grafik ve son ölçümler burada görüntülenecektir.</p>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h2>Ağırlık Grafiği</h2>
                    
                    <?php if ($limits['min_limit'] !== null || $limits['max_limit'] !== null): ?>
                    <div class="limit-info">
                        <?php if ($limits['min_limit'] !== null): ?>
                            <div class="limit-badge min">
                                <span>⬇</span> Alt Limit: <?php echo number_format($limits['min_limit'], 2); ?> kg
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($limits['max_limit'] !== null): ?>
                            <div class="limit-badge max">
                                <span>⬆</span> Üst Limit: <?php echo number_format($limits['max_limit'], 2); ?> kg
                            </div>
                        <?php endif; ?>
                        
                        <div class="limit-badge <?php echo $limits['alarm_active'] ? 'alarm-on' : 'alarm-off'; ?>">
                            <span><?php echo $limits['alarm_active'] ? '🔔' : '🔕'; ?></span>
                            Alarm: <?php echo $limits['alarm_active'] ? 'AKTİF' : 'PASİF'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <canvas id="weightChart"></canvas>
                </div>
                
<!-- DAHA KOMPAKT İSTATİSTİKLER -->
<div class="stats-container compact">
    <h2>📊 Özet İstatistikler</h2>
    
    <div class="stats-grid">
        <!-- SATIR 1: MIN/MAX -->
        <div class="stat-item">
            <div class="stat-icon">📉</div>
            <div class="stat-content">
                <div class="stat-title">Minimum</div>
                <div class="stat-value"><?php echo number_format($stats['min'], 2); ?> kg</div>
                <?php if ($limits['min_limit'] !== null): ?>
                <div class="stat-sub">
                    Limit: <span class="limit-value"><?php echo number_format($limits['min_limit'], 2); ?> kg</span>
                    <?php if ($stats['min'] < $limits['min_limit']): ?>
                    <span class="stat-alert">⚠</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-item">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-title">Maksimum</div>
                <div class="stat-value"><?php echo number_format($stats['max'], 2); ?> kg</div>
                <?php if ($limits['max_limit'] !== null): ?>
                <div class="stat-sub">
                    Limit: <span class="limit-value"><?php echo number_format($limits['max_limit'], 2); ?> kg</span>
                    <?php if ($stats['max'] > $limits['max_limit']): ?>
                    <span class="stat-alert">⚠</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SATIR 2: ORTALAMA ve KAYIT -->
        <div class="stat-item">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-title">Ortalama</div>
                <div class="stat-value"><?php echo number_format($stats['avg'], 2); ?> kg</div>
                <div class="stat-sub">Son 10 ölçüm</div>
            </div>
        </div>
        
        <div class="stat-item">
            <div class="stat-icon">🔢</div>
            <div class="stat-content">
                <div class="stat-title">Kayıtlar</div>
                <div class="stat-value"><?php echo count($table_data); ?></div>
                <div class="stat-sub">Son 100 ölçüm</div>
            </div>
        </div>
        
        <!-- SATIR 3: VERSİYON ve ALARM -->
        <div class="stat-item">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-title">Versiyon</div>
                <div class="stat-value">
                    <?php 
                    if (!empty($table_data) && $table_data[0]['gosterilecek_versiyon'] > 1) {
                        $ver = (string)$table_data[0]['gosterilecek_versiyon'];
                        $formatted = $ver[0] . (isset($ver[1]) ? '.' . $ver[1] : '') . (isset($ver[2]) ? '.' . $ver[2] : '');
                        echo '<span class="version-badge">' . $formatted . '</span>';
                    } else {
                        echo '<span style="color:#999">N/A</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <?php if ($limits['alarm_active']): ?>
        <div class="stat-item">
            <div class="stat-icon">🔔</div>
            <div class="stat-content">
                <div class="stat-title">Alarm</div>
                <div class="stat-value" style="color: #c53030;">AKTİF</div>
                <div class="stat-sub">Limit kontrolü</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
    
    <div class="stat-card">
        <h3>⚡ Versiyon</h3>
        <div class="value" style="font-size: 20px;">
            <?php 
            if (!empty($table_data) && $table_data[0]['gosterilecek_versiyon'] > 1) {
                $ver = (string)$table_data[0]['gosterilecek_versiyon'];
                $formatted = $ver[0] . (isset($ver[1]) ? '.' . $ver[1] : '') . (isset($ver[2]) ? '.' . $ver[2] : '');
                echo '<span class="version-badge" style="font-size: 16px;">' . $formatted . '</span>';
            } else {
                echo '<span style="color:#999">N/A</span>';
            }
            ?>
        </div>
        <div class="value-sub">Cihaz yazılımı</div>
    </div>
    
    <?php if ($limits['alarm_active']): ?>
    <div class="stat-card alarm">
        <h3>🔔 Alarm Durumu</h3>
        <div class="value" style="color: #c53030; font-size: 22px;">AKTİF</div>
        <div class="value-sub">Limit aşımlarında uyarır</div>
    </div>
    <?php endif; ?>
</div>
            </div>
            
            <div class="table-container">
                
                <h2>Son Ölçümler (<?php echo count($table_data); ?> kayıt)</h2>
                    <a href="export_csv.php?cihaz=<?php echo $selected_device; ?>" class="download-btn">
                        📥 Tüm Verileri Excel (CSV) Olarak İndir
                    </a>
                <div style="margin-bottom: 15px; text-align: right;">

                </div>
          

                <?php if (!empty($table_data)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Ağırlık (kg)</th>
                                <th>Litre</th>
                                <th>Stabil</th>
                                <th>Paket No</th>
                                <th>RS485 Hata</th>
                                <th>Çalışma (sn)</th>
                                <th>Versiyon</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_data as $row): 
                                $isOutOfLimit = false;
                                if ($limits['min_limit'] !== null && $row['agirlik_degeri'] < $limits['min_limit']) {
                                    $isOutOfLimit = true;
                                }
                                if ($limits['max_limit'] !== null && $row['agirlik_degeri'] > $limits['max_limit']) {
                                    $isOutOfLimit = true;
                                }
                            ?>
                                <tr class="<?php echo $isOutOfLimit ? 'out-of-limit' : ''; ?>">
                                    <td><?php echo $row['tarih']; ?></td>
                                    <td>
                                        <strong><?php echo number_format($row['agirlik_degeri'], 2); ?></strong>
                                        <?php if ($row['agirlik_degeri'] == $stats['min']): ?>
                                            <span style="color:#e53e3e; margin-left:5px;">⬇ MIN</span>
                                        <?php elseif ($row['agirlik_degeri'] == $stats['max']): ?>
                                            <span style="color:#38a169; margin-left:5px;">⬆ MAX</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($isOutOfLimit): ?>
                                            <span style="color:#e53e3e; margin-left:5px; font-weight:bold;">🚨 LİMİT DIŞI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td> <?php echo $row['darbeSayisi']; ?></td>
                                    <td><?php echo $row['stabil_durumu']; ?></td>
                                    <td>#<?php echo $row['paket_no']; ?></td>
                                    <td><?php echo $row['rs485_hata_sayisi']; ?></td>
                                    <td><?php echo number_format($row['calisma_suresi_saniye']); ?></td>
                                    <td>
                                        <?php 
                                        if ($row['gosterilecek_versiyon'] > 1) {
                                            $ver = (string)$row['gosterilecek_versiyon'];
                                            $formatted = '';
                                            if (strlen($ver) >= 1) $formatted .= $ver[0];
                                            if (strlen($ver) >= 2) $formatted .= '.' . $ver[1];
                                            if (strlen($ver) >= 3) $formatted .= '.' . substr($ver, 2);
                                            echo '<span class="version-badge">' . $formatted . '</span>';
                                        } else {
                                            echo '<span style="color:#999">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['durum'] == 'ONLINE'): ?>
                                            <span class="status-online">ONLINE</span>
                                        <?php else: ?>
                                            <span class="status-offline">OFFLINE</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #718096;">Bu cihaza ait ölçüm kaydı bulunamadı.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- SİTE İSTATİSTİKLERİ -->
<div class="site-stats-container">
    <h2>Site Kullanım İstatistikleri</h2>
    <div class="site-stats-grid">
        <div class="site-stat-card">
            <div class="site-stat-icon">👥</div>
            <div class="site-stat-label">Toplam Ziyaret</div>
            <div class="site-stat-value"><?php echo number_format($site_stats['total_visits']); ?></div>
            <div class="site-stat-subtext">Tüm zamanlar</div>
        </div>

        <div class="site-stat-card green">
            <div class="site-stat-icon">🎯</div>
            <div class="site-stat-label">Tekil Ziyaretçi</div>
            <div class="site-stat-value"><?php echo number_format($site_stats['unique_visitors']); ?></div>
            <div class="site-stat-subtext">Farklı IP adresi</div>
        </div>

        <div class="site-stat-card orange">
            <div class="site-stat-icon">📅</div>
            <div class="site-stat-label">Bugünkü Ziyaret</div>
            <div class="site-stat-value"><?php echo number_format($site_stats['today_visits']); ?></div>
            <div class="site-stat-subtext"><?php echo number_format($site_stats['today_unique']); ?> tekil kullanıcı</div>
        </div>

        <div class="site-stat-card blue">
            <div class="site-stat-icon">📊</div>
            <div class="site-stat-label">Bu Hafta</div>
            <div class="site-stat-value"><?php echo number_format($site_stats['this_week_visits']); ?></div>
            <div class="site-stat-subtext">Haftalık toplam</div>
        </div>

        <div class="site-stat-card red">
            <div class="site-stat-icon">📆</div>
            <div class="site-stat-label">Bu Ay</div>
            <div class="site-stat-value"><?php echo number_format($site_stats['this_month_visits']); ?></div>
            <div class="site-stat-subtext">Aylık toplam</div>
        </div>

        <div class="site-stat-card teal">
            <div class="site-stat-icon">📈</div>
            <div class="site-stat-label">Günlük Ortalama</div>
            <div class="site-stat-value"><?php echo $site_stats['avg_daily_visits']; ?></div>
            <div class="site-stat-subtext">Son 30 gün</div>
        </div>

        <div class="site-stat-card">
            <div class="site-stat-icon">⏰</div>
            <div class="site-stat-label">En Yoğun Saat</div>
            <div class="site-stat-value"><?php echo str_pad($site_stats['peak_hour'], 2, '0', STR_PAD_LEFT); ?>:00</div>
            <div class="site-stat-subtext">En çok ziyaret edilen</div>
        </div>

        <div class="site-stat-card green">
            <div class="site-stat-icon">🔄</div>
            <div class="site-stat-label">Ortalama Dönüş</div>
            <div class="site-stat-value">
                <?php 
                $return_rate = $site_stats['unique_visitors'] > 0 ? 
                    round(($site_stats['total_visits'] / $site_stats['unique_visitors']), 1) : 0;
                echo $return_rate;
                ?>x
            </div>
            <div class="site-stat-subtext">Kullanıcı başına</div>
        </div>
    </div>
</div>

<script>
    // Canlı saat güncelleme
    function updateClock() {
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            const now = new Date();
            const dateStr = now.toISOString().slice(0, 19).replace('T', ' ');
            clockElement.textContent = dateStr;
        }
    }
    setInterval(updateClock, 1000);
    
    // Cihaz seçme fonksiyonu
    function selectDevice(deviceId) {
        const url = new URL(window.location);
        url.searchParams.set('cihaz', deviceId);
        window.location.href = url.toString();
    }
        
    <?php if (!empty($chart_data)): ?>
    // Grafik oluşturma
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('weightChart').getContext('2d');
        
        const labels = <?php echo json_encode(array_column($chart_data, 'time')); ?>;
        const data = <?php echo json_encode(array_column($chart_data, 'value')); ?>;
        
        const minValue = Math.min(...data);
        const maxValue = Math.max(...data);
        const minLimit = <?php echo $limits['min_limit'] !== null ? $limits['min_limit'] : 'null'; ?>;
        const maxLimit = <?php echo $limits['max_limit'] !== null ? $limits['max_limit'] : 'null'; ?>;
        
        const pointBackgroundColors = data.map((value) => {
            if (minLimit !== null && value < minLimit) return '#e53e3e';
            if (maxLimit !== null && value > maxLimit) return '#e53e3e';
            return '#667eea';
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Ağırlık (kg)',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: pointBackgroundColors,
                        pointRadius: 4
                    }
                    <?php if ($limits['min_limit'] !== null): ?>,
                    {
                        label: 'Alt Limit',
                        data: Array(labels.length).fill(<?php echo $limits['min_limit']; ?>),
                        borderColor: '#fc8181',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                    <?php endif; ?>
                    <?php if ($limits['max_limit'] !== null): ?>,
                    {
                        label: 'Üst Limit',
                        data: Array(labels.length).fill(<?php echo $limits['max_limit']; ?>),
                        borderColor: '#9ae6b4',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                    <?php endif; ?>
                ]
            },
            options: {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: {
            bottom: 100
        }
    },
    plugins: {
        legend: { display: true, position: 'top' },
        tooltip: { mode: 'index', intersect: false }
    },
    scales: {
        y: {
            beginAtZero: false,
            grid: { color: '#f0f0f0' }
        },
        x: {
            grid: { display: false },
            ticks: { 
                maxRotation: 45, 
                minRotation: 45,
                autoSkip: true, // Çok fazla veri varsa bazılarını atlar, birbirine girmez
                maxTicksLimit: 15 // Ekranda en fazla 15 tarih görünür (sıkışmayı önler)
            }
        }
    }
}
        });
    });
    <?php endif; ?>
</script>

</body>
</html>