<?php
// Veritabanı yapılandırmasını yükle
$config = include 'config.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Tarih filtreleri - sabit aralık (son 30 gün)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$table_name = $_GET['table'] ?? 'tank_verileri'; // Varsayılan olarak tanklar

// Mevcut tabloları listele
$tables = [];
try {
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
} catch(PDOException $e) {
    $tables = ['sensordata', 'flowveri', 'gemilog', 'logs']; // varsayılan
}

// Önemli tabloları önce sırala
$priority_tables = ['sensordata', 'flowveri', 'gemilog', 'tnkcks_log', 'logs', 'tirlar'];
$sorted_tables = [];

// Önce öncelikli tabloları ekle
foreach ($priority_tables as $priority_table) {
    if (in_array($priority_table, $tables)) {
        $sorted_tables[] = $priority_table;
    }
}

// Sonra diğer tabloları ekle
foreach ($tables as $table) {
    if (!in_array($table, $sorted_tables)) {
        $sorted_tables[] = $table;
    }
}

$tables = $sorted_tables;

// Tablo isimlerini Türkçe karşılıkları
$table_names_tr = [
    'sensordata' => 'Sensör Verileri',
    'flowveri' => 'Akış Ölçüm Verileri',
    'gemilog' => 'Gemi İşlem Kayıtları',
    'tnkcks_log' => 'Tank Çıkış Kayıtları',
    'logs' => 'Sistem Logları',
    'motorverileri' => 'Motor Verileri',
    'commanddata' => 'Komut Verileri',
    'plc_data' => 'PLC Verileri',
    'tirlar' => 'Tır İşlemleri',
    'gemi_bosaltma' => 'Gemi Boşaltma Operasyonları (Akış Hızı)',
    'gemi_bosaltma_toplam' => 'Gemi Boşaltma Operasyonları (Toplam)',
    'tank_verileri' => 'Tank Seviyeleri',
    'users' => 'Kullanıcılar',
    'roles' => 'Roller',
    'permissions' => 'İzinler',
    'drv_config' => 'Sürücü Yapılandırması',
    'plc_config' => 'PLC Yapılandırması',
    'tank1_kalibrasyon' => 'Tank 1 Kalibrasyon',
    'tank2_kalibrasyon' => 'Tank 2 Kalibrasyon',
    'tank1_kgli' => 'Tank 1 Kg/Litre',
    'tank2_kgli' => 'Tank 2 Kg/Litre',
    'adresler' => 'Adres Listesi'
];

// Veri sorgulama
try {
    // Debug için sorguyu yazdır
    $debug_mode = true; // Debug modu - gerektiğinde true yapın
    
    // Tirlar tablosu için özel sorgu
    if ($table_name === 'tirlar') {
        // Geçici: Tarih filtresi olmadan tüm kayıtları getir
        $sql = "SELECT * FROM `tirlar` ORDER BY `dolumbaslama` DESC LIMIT 500";
        $stmt = $pdo->prepare($sql);
        
        // Eğer tarih filtresi istiyorsanız bu satırı uncomment edin:
        // $sql = "SELECT * FROM `tirlar` WHERE `dolumbaslama` BETWEEN :start_date AND :end_date ORDER BY `dolumbaslama` DESC LIMIT 500";
        // $stmt = $pdo->prepare($sql);
        // $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        // $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        
        if ($debug_mode) {
            $debug_info = [
                'sql' => $sql,
                'start_date' => $start_date . ' 00:00:00',
                'end_date' => $end_date . ' 23:59:59',
                'table_name' => $table_name,
                'note' => 'Tarih filtresi geçici olarak kaldırıldı'
            ];
        }
    } elseif ($table_name === 'gemi_bosaltma') {
        // Gemi boşaltma için flowveri tablosundan TÜM debi kayıtları (pozitif ve negatif dahil)
        $sql = "SELECT * FROM flowveri 
                WHERE sensor_adi IN ('gflow1', 'gflow2') 
                ORDER BY debi DESC"; // En yüksek debi değerleri önce görünsün
        
        $stmt = $pdo->prepare($sql);
        
        if ($debug_mode) {
            $debug_info = [
                'sql' => $sql,
                'start_date' => $start_date . ' 00:00:00',
                'end_date' => $end_date . ' 23:59:59',
                'table_name' => $table_name,
                'note' => 'Gemi boşaltma operasyonları - TÜM debi değerleri (pozitif ve negatif), en yüksek debi değerleri önce'
            ];
        }
    } elseif ($table_name === 'gemi_bosaltma_toplam') {
        // Gemi boşaltma toplam operasyonları - debi başlangıç/bitiş analizi
        $sql = "SELECT 
                    sensor_adi,
                    MIN(okuma_zamani) as baslangic_zamani,
                    MAX(okuma_zamani) as bitis_zamani,
                    COUNT(*) as okuma_sayisi,
                    AVG(debi) as ortalama_debi,
                    MAX(debi) as maksimum_debi,
                    AVG(yogunluk) as ortalama_yogunluk,
                    MAX(toplam) as son_toplam,
                    MIN(toplam) as ilk_toplam,
                    SUM(debi) as toplam_debi_birikimi
                FROM flowveri 
                WHERE sensor_adi IN ('gflow1', 'gflow2') 
                AND debi > 0
                AND okuma_zamani >= :start_date 
                AND okuma_zamani <= :end_date
                GROUP BY sensor_adi
                ORDER BY baslangic_zamani DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        
        if ($debug_mode) {
            $debug_info = [
                'sql' => $sql,
                'start_date' => $start_date . ' 00:00:00',
                'end_date' => $end_date . ' 23:59:59',
                'table_name' => $table_name,
                'note' => 'Gemi boşaltma toplam operasyonları - tarih aralığına göre gruplandırılmış'
            ];
        }
    } elseif ($table_name === 'tank_verileri') {
        // Tank verileri için özel sorgu
        $sql = "SELECT * FROM tank_verileri 
                WHERE tarihsaat >= :start_date AND tarihsaat <= :end_date 
                ORDER BY tarihsaat DESC LIMIT 500";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        
        if ($debug_mode) {
            $debug_info = [
                'sql' => $sql,
                'start_date' => $start_date . ' 00:00:00',
                'end_date' => $end_date . ' 23:59:59',
                'table_name' => $table_name,
                'note' => 'Tank seviye verileri - tarih aralığına göre filtrelenmiş'
            ];
        }
    } else {
        // Diğer tablolar için farklı tarih kolonları
        $date_columns = [
            'sensordata' => 'RecordedAt',
            'flowveri' => 'okuma_zamani',
            'gemilog' => 'tarihsaat',
            'tnkcks_log' => 'tarihsaat',
            'logs' => 'LoggedAt',
            'motorverileri' => 'tarih',
            'commanddata' => 'RecordAt',
            'plc_data' => 'RecordedAt',
            'tank_verileri' => 'tarihsaat',
            'users' => 'CreatedAt'
        ];
        
        $date_column = $date_columns[$table_name] ?? null;
        
        if ($date_column) {
            $sql = "SELECT * FROM `{$table_name}` WHERE `{$date_column}` BETWEEN :start_date AND :end_date ORDER BY `{$date_column}` DESC LIMIT 500";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
            $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        } else {
            // Tarih kolonu olmayan tablolar için sadece son 500 kayıt
            $sql = "SELECT * FROM `{$table_name}` LIMIT 500";
            $stmt = $pdo->prepare($sql);
        }
        
        if ($debug_mode) {
            $debug_info = [
                'sql' => $sql,
                'start_date' => $start_date . ' 00:00:00',
                'end_date' => $end_date . ' 23:59:59',
                'table_name' => $table_name,
                'date_column' => $date_column ?? 'none'
            ];
        }
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tank verilerinin son değerlerini al (dashboard için)
    $tank_latest_data = [];
    if ($table_name === 'tank_verileri') {
        try {
            // Her tank için en son veriyi al
            $latest_sql = "SELECT * FROM tank_verileri 
                          WHERE tank IN (1, 2) 
                          AND tarihsaat >= :start_date AND tarihsaat <= :end_date
                          ORDER BY tarihsaat DESC";
            $latest_stmt = $pdo->prepare($latest_sql);
            $latest_stmt->bindValue(':start_date', $start_date . ' 00:00:00');
            $latest_stmt->bindValue(':end_date', $end_date . ' 23:59:59');
            $latest_stmt->execute();
            $all_latest = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tank 1 ve Tank 2 için ayrı ayrı en son veriyi bul
            foreach ([1, 2] as $tank_num) {
                foreach ($all_latest as $row) {
                    if ($row['tank'] == $tank_num) {
                        $tank_latest_data[$tank_num] = $row;
                        break; // İlk (en son) veriyi alınca döngüden çık
                    }
                }
            }
    } catch(PDOException $e) {
        // Hata durumunda boş bırak
        $tank_latest_data = [];
    }
}

// Tank kg hesaplama fonksiyonu
function calculateTankKg($tank_no, $cm_value, $pdo) {
    try {
        // cm'yi mm'ye çevir
        $mm_value = $cm_value * 10;
        
        // Uygun tank kalibrasyon tablosunu seç
        $table_name = ($tank_no == 1) ? 'tank1_kgli' : 'tank2_kgli';
        
        // En yakın mm değerini bul
        $sql = "SELECT mm, kg FROM {$table_name} 
                ORDER BY ABS(mm - :mm_value) ASC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mm_value', $mm_value);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['kg'];
        } else {
            return 0; // Kalibrasyon verisi bulunamadı
        }
    } catch (PDOException $e) {
        return 0; // Hata durumunda 0 döndür
    }
}    // Debug: Toplam kayıt sayısını kontrol et
    if ($debug_mode && $table_name === 'tirlar') {
        $count_sql = "SELECT COUNT(*) as total FROM `tirlar`";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tablodaki en eski ve en yeni tarihleri kontrol et
        $date_range_sql = "SELECT MIN(dolumbaslama) as min_date, MAX(dolumbaslama) as max_date FROM `tirlar`";
        $date_range_stmt = $pdo->prepare($date_range_sql);
        $date_range_stmt->execute();
        $date_range = $date_range_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Son 5 kaydı göster
        $sample_sql = "SELECT id, plaka, dolumbaslama, dolumbitis FROM `tirlar` ORDER BY id DESC LIMIT 5";
        $sample_stmt = $pdo->prepare($sample_sql);
        $sample_stmt->execute();
        $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_info['total_records_in_table'] = $total_records;
        $debug_info['fetched_records'] = count($data);
        $debug_info['min_date_in_table'] = $date_range['min_date'];
        $debug_info['max_date_in_table'] = $date_range['max_date'];
        $debug_info['sample_records'] = $sample_data;
    } elseif ($debug_mode && $table_name === 'gemi_bosaltma') {
        $count_sql = "SELECT COUNT(*) as total FROM `flowveri` WHERE sensor_adi IN ('gflow1', 'gflow2')";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tablodaki en eski ve en yeni tarihleri kontrol et
        $date_range_sql = "SELECT MIN(okuma_zamani) as min_date, MAX(okuma_zamani) as max_date FROM `flowveri` WHERE sensor_adi IN ('gflow1', 'gflow2')";
        $date_range_stmt = $pdo->prepare($date_range_sql);
        $date_range_stmt->execute();
        $date_range = $date_range_stmt->fetch(PDO::FETCH_ASSOC);
        
        // En yüksek debi değerlerini göster
        $sample_sql = "SELECT id, sensor_adi, okuma_zamani, debi, toplam, operasyon_toplam FROM `flowveri` WHERE sensor_adi IN ('gflow1', 'gflow2') ORDER BY debi DESC LIMIT 10";
        $sample_stmt = $pdo->prepare($sample_sql);
        $sample_stmt->execute();
        $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_info['total_records_in_table'] = $total_records;
        $debug_info['fetched_records'] = count($data);
        $debug_info['min_date_in_table'] = $date_range['min_date'];
        $debug_info['max_date_in_table'] = $date_range['max_date'];
        $debug_info['sample_records'] = $sample_data;
    } elseif ($debug_mode && $table_name === 'gemi_bosaltma_toplam') {
        $count_sql = "SELECT COUNT(DISTINCT sensor_adi) as total FROM `flowveri` WHERE sensor_adi IN ('gflow1', 'gflow2') AND debi > 0";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $debug_info['total_sensors_with_data'] = $total_records;
        $debug_info['fetched_records'] = count($data);
    } elseif ($debug_mode && $table_name === 'tank_verileri') {
        $count_sql = "SELECT COUNT(*) as total FROM `tank_verileri`";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tablodaki en eski ve en yeni tarihleri kontrol et
        $date_range_sql = "SELECT MIN(tarihsaat) as min_date, MAX(tarihsaat) as max_date FROM `tank_verileri`";
        $date_range_stmt = $pdo->prepare($date_range_sql);
        $date_range_stmt->execute();
        $date_range = $date_range_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Son 5 kaydı göster
        $sample_sql = "SELECT id, tank, rdr, pt100, bsnc, tarihsaat FROM `tank_verileri` ORDER BY tarihsaat DESC LIMIT 5";
        $sample_stmt = $pdo->prepare($sample_sql);
        $sample_stmt->execute();
        $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_info['total_records_in_table'] = $total_records;
        $debug_info['fetched_records'] = count($data);
        $debug_info['min_date_in_table'] = $date_range['min_date'];
        $debug_info['max_date_in_table'] = $date_range['max_date'];
        $debug_info['sample_records'] = $sample_data;
    }
    
} catch(PDOException $e) {
    $data = [];
    $error_message = "Veri çekme hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Rapor Sistemi</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }

        .header-logo {
            margin-bottom: 1rem;
        }

        .company-logo {
            height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .company-info {
            margin-top: 0.5rem;
            opacity: 0.8;
        }

        .company-info small {
            font-size: 0.9rem;
            font-style: italic;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: #5a67d8;
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .data-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .data-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .data-count {
            color: white;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
        }

        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            font-weight: 600;
            color: white;
            position: sticky;
            top: 0;
        }

        .data-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tr:nth-child(odd) {
            background: white;
        }

        .data-table tr:hover {
            background: #e3f2fd !important;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .error {
            background: #fee;
            color: #c53030;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #fed7d7;
        }

        .debug-panel {
            background: #e6fffa;
            color: #234e52;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #81e6d9;
        }

        .debug-panel h4 {
            margin-bottom: 0.5rem;
            color: #234e52;
        }

        .debug-panel ul {
            margin-left: 1.5rem;
        }

        .debug-panel li {
            margin-bottom: 0.25rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 1.5rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .company-logo {
                height: 40px;
            }

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .footer-section {
                margin-bottom: 1.5rem;
            }

            .footer-bottom {
                padding: 1rem;
            }

            .tanks-container {
                grid-template-columns: 1fr;
                padding: 1rem;
                gap: 1rem;
            }
            
            .tank-image {
                width: 80px;
            }
            
            .tank-values {
                grid-template-columns: repeat(3, 1fr);
                font-size: 0.8rem;
                gap: 0.3rem;
            }
            
            .tank-value-label {
                font-size: 0.7rem !important;
            }
            
            .tank-value-data {
                font-size: 0.85rem !important;
            }
        }

        .export-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .export-btn:hover {
            background: white;
            color: #4CAF50;
        }

        /* Tır tablosu özel stilleri */
        .plate-number {
            font-weight: 700;
            color: #1a202c;
            background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
            text-align: center;
            border-radius: 4px;
            padding: 0.5rem;
            border: 2px solid #cbd5e0;
        }

        /* Gemi boşaltma özel stilleri */
        .sensor-name {
            font-weight: 700;
            color: #1a202c;
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
            text-align: center;
            border-radius: 4px;
            padding: 0.5rem;
            border: 2px solid #81e6d9;
        }

        /* Tank özel stilleri */
        .tank-1 {
            font-weight: 700;
            color: #1a202c;
            background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
            text-align: center;
            border-radius: 4px;
            padding: 0.5rem;
            border: 2px solid #f59e0b;
        }

        .tank-2 {
            font-weight: 700;
            color: #1a202c;
            background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%);
            text-align: center;
            border-radius: 4px;
            padding: 0.5rem;
            border: 2px solid #3b82f6;
        }

        .amount {
            font-weight: 600;
            color: #2d3748;
            text-align: right;
        }

        .data-table td:nth-child(4),
        .data-table td:nth-child(5) {
            font-size: 0.9rem;
            color: #4a5568;
        }

        /* Footer ve Debug Stilleri */
        .main-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            margin-top: 3rem;
            padding: 2rem 0 1rem 0;
            border-radius: 8px 8px 0 0;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 0 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h4 {
            color: #ecf0f1;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
        }

        .footer-section p {
            margin-bottom: 0.8rem;
            line-height: 1.6;
            color: #bdc3c7;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
            padding-left: 1rem;
            position: relative;
            color: #bdc3c7;
        }

        .footer-section ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #3498db;
            font-weight: bold;
        }

        .contact-info p {
            margin-bottom: 0.5rem;
        }

        .contact-info a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-info a:hover {
            color: #5dade2;
            text-decoration: underline;
        }

        .footer-bottom {
            background: #1a252f;
            margin-top: 2rem;
            padding: 1rem 2rem;
            text-align: center;
            border-top: 1px solid #34495e;
        }

        .footer-bottom p {
            margin: 0.5rem 0;
            color: #95a5a6;
            font-size: 0.9rem;
        }

        .debug-footer {
            margin-top: 2rem;
            padding: 1rem 0;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        .footer {
            margin-top: 2rem;
            padding: 1rem 0;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }

        .debug-toggle {
            margin-bottom: 1rem;
        }

        .debug-btn {
            background: #718096;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .debug-btn:hover {
            background: #4a5568;
        }

        .debug-panel {
            background: #f7fafc;
            color: #2d3748;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            border: 1px solid #e2e8f0;
            text-align: left;
            max-height: 400px;
            overflow-y: auto;
        }

        .debug-panel h4 {
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .debug-panel ul {
            margin-left: 1.5rem;
        }

        .debug-panel li {
            margin-bottom: 0.25rem;
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            
            .header-actions,
            .debug-toggle,
            .debug-panel,
            .debug-footer,
            .main-footer {
                display: none !important;
            }
            
            .container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .header {
                background: none !important;
                color: black !important;
                border: 2px solid black;
                margin-bottom: 1rem !important;
            }
            
            .data-section {
                box-shadow: none !important;
                border: 1px solid black;
            }
            
            .data-header {
                background: #f0f0f0 !important;
                color: black !important;
                border-bottom: 2px solid black !important;
            }
            
            .data-table th {
                background: #f0f0f0 !important;
                color: black !important;
                border: 1px solid black !important;
            }
            
            .data-table td {
                border: 1px solid black !important;
            }
            
            .data-table tr:nth-child(even) {
                background: #f8f8f8 !important;
            }
            
            .data-table tr:nth-child(odd) {
                background: white !important;
            }
            
            .plate-number {
                background: #f0f0f0 !important;
                border: 1px solid black !important;
            }
            
            .tank-1 {
                background: #f0f0f0 !important;
                border: 1px solid black !important;
            }
            
            .tank-2 {
                background: #e0e0e0 !important;
                border: 1px solid black !important;
            }
            
            .amount {
                color: black !important;
            }
            
            .data-table {
                font-size: 10px !important;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.3rem !important;
            }

            .chart-section {
                display: none !important;
            }
            
            .tank-dashboard {
                display: none !important;
            }
        }

        /* Tank Dashboard Stilleri */
        .tank-dashboard {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tank-dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            text-align: center;
        }

        .tanks-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .tank-display {
            position: relative;
            text-align: center;
            padding: 1rem;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tank-display:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        /* Tank tıklama efekti */
        .tank-display:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
        }

        .tank-1-display {
            background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
            border: 3px solid #f59e0b;
        }

        .tank-2-display {
            background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%);
            border: 3px solid #3b82f6;
        }

        .tank-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #1a202c;
        }

        .tank-image {
            width: 120px;
            height: auto;
            margin: 1rem 0;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        }

        .tank-values {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, auto);
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        /* Grid düzeni: 
           Radar      Radar(cm)     Radar(kg)
           Basınç     Basınç(cm)    Basınç(kg)
           Sıcaklık   Son Güncelleme (2 sütun)
        */

        /* Son güncelleme kutusunu 2 sütun genişliğinde yap */
        .tank-timestamp-value {
            grid-column: span 2;
        }

        .tank-value {
            background: rgba(255,255,255,0.8);
            padding: 0.5rem;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .tank-value-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.8rem;
            margin-bottom: 0.2rem;
        }

        .tank-value-data {
            font-weight: bold;
            color: #1a202c;
            font-size: 1rem;
        }

        /* Tank kg değerleri için özel stiller - KALDIRILDI */
        
        /* Son satır için özel stiller */
        .tank-value:nth-child(7) {
            /* Sıcaklık - Gradient mavi tonları */
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
            border: 2px solid #2196f3 !important;
        }

        /* Son güncelleme kutusu için özel stiller */
        .tank-timestamp-value {
            /* Gradient gri tonları, daha büyük alan */
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%) !important;
            border: 2px solid #9e9e9e !important;
            grid-column: span 2;
        }

        .tank-timestamp-value .tank-value-data {
            font-size: 0.85rem !important;
            color: #424242 !important;
            font-weight: 600 !important;
        }

        /* Tablo filtreleme stilleri */
        .table-filter {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-tag {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tag.tank-1-filter {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .filter-tag.tank-2-filter {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .filter-tag .close-btn {
            background: rgba(255,255,255,0.3);
            border: none;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-tag .close-btn:hover {
            background: rgba(255,255,255,0.5);
        }

        .clear-all-btn {
            background: #718096;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .clear-all-btn:hover {
            background: #4a5568;
        }

        /* Gizli satırlar için */
        .data-table tr.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .tanks-container {
                grid-template-columns: 1fr;
                padding: 1rem;
                gap: 1rem;
            }
            
            .tank-image {
                width: 80px;
            }
            
            .tank-values {
                grid-template-columns: repeat(3, 1fr);
                font-size: 0.8rem;
                gap: 0.3rem;
            }
            
            .tank-value-label {
                font-size: 0.7rem !important;
            }
            
            .tank-value-data {
                font-size: 0.85rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <img src="img/logo.png" alt="RMT Proje Logo" class="company-logo">
            </div>
            <h1>RMT Liman SCADA İzleme Sistemi</h1>
            <p>Gerçek zamanlı tank seviyeleri, gemi boşaltma operasyonları ve tır yükleme işlemlerini takip edin</p>
            <div class="company-info">
                <small>RMT Proje ve Endüstriyel Otomasyon Ltd. Şti. | Profesyonel SCADA, Otomasyon ve Web Çözümleri</small>
            </div>
            <div style="margin-top: 1rem;">
                <a href="?table=tank_verileri" class="btn" style="margin-right: 0.5rem; text-decoration: none; <?= $table_name === 'tank_verileri' ? 'background: #4CAF50;' : '' ?>">�️ Tank İzleme</a>
                <a href="?table=gemi_bosaltma" class="btn" style="margin-right: 0.5rem; text-decoration: none; <?= $table_name === 'gemi_bosaltma' ? 'background: #4CAF50;' : '' ?>">� Akış Hızı</a>
                <a href="?table=gemi_bosaltma_toplam" class="btn" style="margin-right: 0.5rem; text-decoration: none; <?= $table_name === 'gemi_bosaltma_toplam' ? 'background: #4CAF50;' : '' ?>">� Toplam Operasyonlar</a>
                <a href="?table=tirlar" class="btn" style="text-decoration: none; <?= $table_name === 'tirlar' ? 'background: #4CAF50;' : '' ?>">� Tır İşlemleri</a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($table_name === 'gemi_bosaltma' && !empty($data)): ?>
        <div class="chart-section">
            <div class="chart-header">
                <h3>📈 Debi Grafiği (Ton/h)</h3>
            </div>
            <div class="chart-container">
                <canvas id="flowChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($table_name === 'tank_verileri' && !empty($tank_latest_data)): ?>
        <div class="tank-dashboard">
            <div class="tank-dashboard-header">
                <h3>🛢️ Tank Durumu - Anlık Değerler</h3>
            </div>
            <div class="tanks-container">
                <?php foreach ([1, 2] as $tank_num): ?>
                    <?php if (isset($tank_latest_data[$tank_num])): ?>
                        <?php 
                        $tank_data = $tank_latest_data[$tank_num];
                        
                        // Değerleri hesapla
                        $radar_raw = $tank_data['rdr'] ?? 0;
                        $radar_cm = ($tank_data['rdrmetre'] ?? 0) / 10;
                        $sicaklik = ($tank_data['pt100'] ?? 0) / 10;
                        $basinc_bar = ($tank_data['bsnc'] ?? 0) / 100;
                        $basinc_cm = ($tank_data['bsncmetre'] ?? 0) / 10;
                        
                        // Tank kg hesaplama (radar cm'den)
                        $radar_kg = calculateTankKg($tank_num, $radar_cm, $pdo);
                        
                        // Basınç kg hesaplama (basınç cm'den)
                        $basinc_kg = calculateTankKg($tank_num, $basinc_cm, $pdo);
                        
                        // Açıklama kodu
                        $aciklama_kod = $tank_data['aciklama'] ?? 0;
                        $aciklama_text = '';
                        switch($aciklama_kod) {
                            case 0: $aciklama_text = 'Normal'; break;
                            case 1: $aciklama_text = 'Alarm'; break;
                            case 2: $aciklama_text = 'Bakım'; break;
                            default: $aciklama_text = 'Kod: ' . $aciklama_kod;
                        }
                        ?>
                        <div class="tank-display tank-<?= $tank_num ?>-display" onclick="filterTableByTank(<?= $tank_num ?>)" style="cursor: pointer;" title="Tabloyu Tank <?= $tank_num ?> için filtrele">
                            <div class="tank-title">Tank <?= $tank_num ?></div>
                            <img src="img/tank.png" alt="Tank <?= $tank_num ?>" class="tank-image">
                            
                            <div class="tank-values">
                                <!-- Radar Grup -->
                                <div class="tank-value">
                                    <div class="tank-value-label">Radar</div>
                                    <div class="tank-value-data"><?= number_format($radar_raw, 0, ',', '.') ?></div>
                                </div>
                                <div class="tank-value">
                                    <div class="tank-value-label">Radar (cm)</div>
                                    <div class="tank-value-data"><?= number_format($radar_cm, 1, ',', '.') ?> cm</div>
                                </div>
                                <div class="tank-value">
                                    <div class="tank-value-label">Radar (kg)</div>
                                    <div class="tank-value-data"><?= number_format($radar_kg, 0, ',', '.') ?> kg</div>
                                </div>
                                
                                <!-- Basınç Grup -->
                                <div class="tank-value">
                                    <div class="tank-value-label">Basınç (bar)</div>
                                    <div class="tank-value-data"><?= number_format($basinc_bar, 2, ',', '.') ?> bar</div>
                                </div>
                                <div class="tank-value">
                                    <div class="tank-value-label">Basınç (cm)</div>
                                    <div class="tank-value-data"><?= number_format($basinc_cm, 1, ',', '.') ?> cm</div>
                                </div>
                                <div class="tank-value">
                                    <div class="tank-value-label">Basınç (kg)</div>
                                    <div class="tank-value-data"><?= number_format($basinc_kg, 0, ',', '.') ?> kg</div>
                                </div>
                                
                                <!-- Sıcaklık ve Son Güncelleme -->
                                <div class="tank-value">
                                    <div class="tank-value-label">Sıcaklık</div>
                                    <div class="tank-value-data"><?= number_format($sicaklik, 2, ',', '.') ?> °C</div>
                                </div>
                                <div class="tank-value tank-timestamp-value">
                                    <div class="tank-value-label">Son Güncelleme</div>
                                    <div class="tank-value-data"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($tank_data['tarihsaat'] ?? ''))) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="tank-display tank-<?= $tank_num ?>-display" onclick="filterTableByTank(<?= $tank_num ?>)" style="cursor: pointer;" title="Tabloyu Tank <?= $tank_num ?> için filtrele">
                            <div class="tank-title">Tank <?= $tank_num ?></div>
                            <img src="img/tank.png" alt="Tank <?= $tank_num ?>" class="tank-image">
                            <div style="padding: 2rem; color: #718096;">
                                <strong>Veri Bulunamadı</strong><br>
                                <small>Seçilen tarih aralığında Tank <?= $tank_num ?> için veri bulunmuyor.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="data-section">
            <div class="data-header">
                <h3><?= $table_names_tr[$table_name] ?? ucfirst($table_name) ?></h3>
                <div class="header-actions">
                    <span class="data-count"><?= count($data) ?> kayıt</span>
                    <button type="button" class="btn export-btn" onclick="exportToCSV()">📥 CSV İndir</button>
                    <button type="button" class="btn export-btn" onclick="exportToPDF()">📄 PDF İndir</button>
                </div>
            </div>

            <?php if ($table_name === 'tank_verileri'): ?>
            <div class="table-filter" id="tableFilter" style="display: none;">
                <div class="filter-controls">
                    <span style="color: #4a5568; font-weight: 600;">Aktif Filtreler:</span>
                    <div id="activeFilters"></div>
                    <button type="button" class="clear-all-btn" onclick="clearAllFilters()">Tümünü Temizle</button>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($data)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                    </svg>
                    <h3>Veri bulunamadı</h3>
                    <p>Seçilen tarih aralığında herhangi bir tır yükleme operasyonu bulunamadı.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table" id="dataTable">
                        <thead>
                            <tr>
                                <?php if ($table_name === 'tirlar'): ?>
                                    <th>Plaka</th>
                                    <th>Port</th>
                                    <th>Dolum Başlama</th>
                                    <th>Dolum Bitiş</th>
                                    <th>Toplam (Kg)</th>
                                    <th>Durdurma Şekli</th>
                                    <th>İşlem Süresi</th>
                                <?php elseif ($table_name === 'gemi_bosaltma'): ?>
                                    <th>Rıhtım</th>
                                    <th>Zaman</th>
                                    <th>Sıcaklık (°C)</th>
                                    <th>Debi (T/h)</th>
                                    <th>Yoğunluk (kg/L)</th>
                                    <th>Operasyon Toplam (Ton)</th>
                                    <th>Toplam (Ton)</th>
                                <?php elseif ($table_name === 'gemi_bosaltma_toplam'): ?>
                                    <th>Rıhtım</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Süre</th>
                                    <th>Ort. Debi (Ton/h)</th>
                                    <th>Maks. Debi (Ton/h)</th>
                                    <th>Toplam (Ton)</th>
                                    <th>Okuma Sayısı</th>
                                <?php elseif ($table_name === 'tank_verileri'): ?>
                                    <th>Tank No</th>
                                    <th>Radar</th>
                                    <th>Radar (cm)</th>
                                    <th>Radar (kg)</th>
                                    <th>Sıcaklık (°C)</th>
                                    <th>Basınç (bar)</th>
                                    <th>Basınç (cm)</th>
                                    <th>Basınç (kg)</th>
                                    <th>Tarih/Saat</th>
                                    <th>Açıklama</th>
                                <?php else: ?>
                                    <?php if (!empty($data)): ?>
                                        <?php foreach (array_keys($data[0]) as $column): ?>
                                            <th><?= htmlspecialchars(ucfirst($column)) ?></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <?php if ($table_name === 'tirlar'): ?>
                                    <?php 
                                    // İşlem süresini hesapla
                                    $duration_text = '';
                                    if (!empty($row['dolumbaslama']) && !empty($row['dolumbitis'])) {
                                        $start = new DateTime($row['dolumbaslama']);
                                        $end = new DateTime($row['dolumbitis']);
                                        $duration = $start->diff($end);
                                        $duration_text = $duration->format('%H:%I:%S');
                                    }
                                    ?>
                                    <tr>
                                        <td class="plate-number"><?= htmlspecialchars($row['plaka'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['port'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['dolumbaslama'] ?? ''))) ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['dolumbitis'] ?? ''))) ?></td>
                                        <td class="amount"><?= number_format($row['toplam'] ?? 0, 0, ',', '.') ?> Kg</td>
                                        <td><?= htmlspecialchars($row['durdurma_sekli'] ?? '') ?></td>
                                        <td><?= $duration_text ?></td>
                                    </tr>
                                <?php elseif ($table_name === 'gemi_bosaltma'): ?>
                                    <?php 
                                    // Sensör adını rıhtım ismiyle değiştir
                                    $rihtim_adi = '';
                                    if ($row['sensor_adi'] === 'gflow1') {
                                        $rihtim_adi = 'Rıhtım 7';
                                    } elseif ($row['sensor_adi'] === 'gflow2') {
                                        $rihtim_adi = 'Rıhtım 8';
                                    } else {
                                        $rihtim_adi = $row['sensor_adi'] ?? '';
                                    }
                                    
                                    // Birimleri dönüştürme - VERİTABANI ZATEN DOĞRU BİRİMLERDE
                                    // Debi: T/h (olduğu gibi kullan)
                                    // Operasyon toplam ve Toplam: Ton (olduğu gibi kullan)
                                    // Yoğunluk: kg/L (olduğu gibi kullan)
                                    ?>
                                    <tr>
                                        <td class="sensor-name"><?= htmlspecialchars($rihtim_adi) ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['okuma_zamani'] ?? ''))) ?></td>
                                        <td class="amount"><?= number_format($row['sicaklik'] ?? 0, 1, ',', '.') ?></td>
                                        <td class="amount" style="font-weight: bold; color: #c53030;"><?= number_format($row['debi'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($row['yogunluk'] ?? 0, 3, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($row['operasyon_toplam'] ?? 0, 2, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($row['toplam'] ?? 0, 2, ',', '.') ?></td>
                                    </tr>
                                <?php elseif ($table_name === 'gemi_bosaltma_toplam'): ?>
                                    <?php 
                                    // Sensör adını rıhtım ismiyle değiştir
                                    $rihtim_adi = '';
                                    if ($row['sensor_adi'] === 'gflow1') {
                                        $rihtim_adi = 'Rıhtım 7';
                                    } elseif ($row['sensor_adi'] === 'gflow2') {
                                        $rihtim_adi = 'Rıhtım 8';
                                    } else {
                                        $rihtim_adi = $row['sensor_adi'] ?? '';
                                    }
                                    
                                    // Süre hesaplama
                                    $baslangic = new DateTime($row['baslangic_zamani']);
                                    $bitis = new DateTime($row['bitis_zamani']);
                                    $sure = $baslangic->diff($bitis);
                                    $sure_text = $sure->format('%d gün %H:%I:%S');
                                    if ($sure->days == 0) {
                                        $sure_text = $sure->format('%H:%I:%S');
                                    }
                                    
                                    // Ton hesaplamaları - VERİTABANI ZATEN TON OLARAK KAYITLI
                                    // Ortalama ve maksimum debi: T/h (olduğu gibi kullan)
                                    // Toplam: Ton (olduğu gibi kullan)
                                    $ortalama_debi_ton_h = $row['ortalama_debi'] ?? 0;
                                    $maksimum_debi_ton_h = $row['maksimum_debi'] ?? 0;
                                    
                                    // Toplam hacim hesaplama - zaten Ton cinsinden
                                    $toplam_ton = ($row['son_toplam'] ?? 0) - ($row['ilk_toplam'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="sensor-name"><?= htmlspecialchars($rihtim_adi) ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['baslangic_zamani'] ?? ''))) ?></td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['bitis_zamani'] ?? ''))) ?></td>
                                        <td><?= $sure_text ?></td>
                                        <td class="amount"><?= number_format($ortalama_debi_ton_h, 2, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($maksimum_debi_ton_h, 2, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($toplam_ton, 2, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($row['okuma_sayisi'] ?? 0, 0, ',', '.') ?></td>
                                    </tr>
                                <?php elseif ($table_name === 'tank_verileri'): ?>
                                    <?php 
                                    // Tank numarasını daha görünür yap
                                    $tank_no = $row['tank'] ?? '';
                                    $tank_display = "Tank " . $tank_no;
                                    
                                    // Tank numarasına göre CSS sınıfı belirle
                                    $tank_class = '';
                                    if ($tank_no == 1) {
                                        $tank_class = 'tank-1';
                                    } elseif ($tank_no == 2) {
                                        $tank_class = 'tank-2';
                                    } else {
                                        $tank_class = 'sensor-name'; // varsayılan
                                    }
                                    
                                    // Radar değeri (birim yok, ham değer)
                                    $radar_raw = $row['rdr'] ?? 0;
                                    
                                    // Radar metre değerini santimetre cinsinden hesapla (rdrmetre)
                                    $radar_cm = ($row['rdrmetre'] ?? 0) / 10; // mm'den cm'ye
                                    
                                    // Sıcaklık PT100 değeri (10'a bölünmesi gerekiyor, 2 ondalık)
                                    $sicaklik = ($row['pt100'] ?? 0) / 10;
                                    
                                    // Basınç değerini bar cinsinden hesapla (2 ondalık)
                                    $basinc_raw = $row['bsnc'] ?? 0;
                                    $basinc_bar = $basinc_raw / 100; // Basınç birimi muhtemelen mbar veya benzeri
                                    
                                    // Basınç metre değerini santimetre cinsinden hesapla
                                    $basinc_cm = ($row['bsncmetre'] ?? 0) / 10; // mm'den cm'ye
                                    
                                    // Tank kg hesaplamaları
                                    $radar_kg = calculateTankKg($tank_no, $radar_cm, $pdo);
                                    $basinc_kg = calculateTankKg($tank_no, $basinc_cm, $pdo);
                                    
                                    // Açıklama kodu (genellikle durum kodu)
                                    $aciklama_kod = $row['aciklama'] ?? 0;
                                    $aciklama_text = '';
                                    switch($aciklama_kod) {
                                        case 0: $aciklama_text = 'Normal'; break;
                                        case 1: $aciklama_text = 'Alarm'; break;
                                        case 2: $aciklama_text = 'Bakım'; break;
                                        default: $aciklama_text = 'Kod: ' . $aciklama_kod;
                                    }
                                    ?>
                                    <tr>
                                        <td class="<?= $tank_class ?>"><?= htmlspecialchars($tank_display) ?></td>
                                        <td class="amount"><?= number_format($radar_raw, 0, ',', '.') ?></td>
                                        <td class="amount"><?= number_format($radar_cm, 1, ',', '.') ?> cm</td>
                                        <td class="amount"><?= number_format($radar_kg, 0, ',', '.') ?> kg</td>
                                        <td class="amount"><?= number_format($sicaklik, 2, ',', '.') ?> °C</td>
                                        <td class="amount"><?= number_format($basinc_bar, 2, ',', '.') ?> bar</td>
                                        <td class="amount"><?= number_format($basinc_cm, 1, ',', '.') ?> cm</td>
                                        <td class="amount"><?= number_format($basinc_kg, 0, ',', '.') ?> kg</td>
                                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['tarihsaat'] ?? ''))) ?></td>
                                        <td><?= htmlspecialchars($aciklama_text) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value ?? '') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ana Footer -->
        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>🏢 RMT Proje ve Endüstriyel Otomasyon Ltd. Şti.</h4>
                    <p>Profesyonel SCADA, Otomasyon, Web Tasarım ve Donanım Çözümleri</p>
                    <div class="contact-info">
                        <p>📞 <strong>Telefon:</strong> 0 266 606 01 32</p>
                        <p>🌐 <strong>Web:</strong> <a href="https://www.rmtproje.com" target="_blank">www.rmtproje.com</a></p>
                        <p>📧 <strong>E-posta:</strong> <a href="mailto:info@rmtproje.com">info@rmtproje.com</a></p>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>⚠️ Önemli Bilgilendirme</h4>
                    <p><strong>Bu sistem bilgi amaçlı olarak geliştirilmiştir.</strong></p>
                    <ul>
                        <li>Gösterilen verilerin doğruluğu ve kusursuzluğu garanti edilmez</li>
                        <li>Kritik kararlar için mutlaka birincil sistemleri kontrol ediniz</li>
                        <li>Veriler gerçek zamanlı olarak güncellenmektedir</li>
                        <li>Sistem 7/24 izlenmekte ve sürekli geliştirilmektedir</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>🛠️ Hizmetlerimiz</h4>
                    <ul>
                        <li>SCADA Sistemleri</li>
                        <li>Endüstriyel Otomasyon</li>
                        <li>Web Tasarım ve Geliştirme</li>
                        <li>Donanım Entegrasyon</li>
                        <li>Veri Analizi ve Raporlama</li>
                        <li>Sistem Bakım ve Destek</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 RMT Proje ve Endüstriyel Otomasyon Ltd. Şti. | Tüm hakları saklıdır.</p>
                <p>Bu sistemin tüm bileşenleri şirketimiz tarafından tasarlanmış ve geliştirilmiştir.</p>
            </div>
        </footer>

        <!-- Debug Footer -->
        <footer class="debug-footer">
            <div class="debug-toggle">
                <button id="debugToggle" class="debug-btn" onclick="toggleDebug()">🔧 Debug Bilgileri</button>
            </div>
            
            <?php if (isset($debug_info)): ?>
                <div class="debug-panel" id="debugPanel" style="display: none;">
                    <h4>Debug Bilgileri:</h4>
                    <ul>
                        <li><strong>SQL:</strong> <?= htmlspecialchars($debug_info['sql']) ?></li>
                        <li><strong>Başlangıç Tarihi:</strong> <?= htmlspecialchars($debug_info['start_date']) ?></li>
                        <li><strong>Bitiş Tarihi:</strong> <?= htmlspecialchars($debug_info['end_date']) ?></li>
                        <li><strong>Tablo:</strong> <?= htmlspecialchars($debug_info['table_name']) ?></li>
                        <?php if (isset($debug_info['date_column'])): ?>
                            <li><strong>Tarih Kolonu:</strong> <?= htmlspecialchars($debug_info['date_column']) ?></li>
                        <?php endif; ?>
                        <?php if (isset($debug_info['total_records_in_table'])): ?>
                            <li><strong>Tablodaki Toplam Kayıt:</strong> <?= $debug_info['total_records_in_table'] ?></li>
                            <li><strong>Çekilen Kayıt:</strong> <?= $debug_info['fetched_records'] ?></li>
                        <?php endif; ?>
                        <?php if (isset($debug_info['min_date_in_table'])): ?>
                            <li><strong>Tablodaki En Eski Tarih:</strong> <?= $debug_info['min_date_in_table'] ?></li>
                            <li><strong>Tablodaki En Yeni Tarih:</strong> <?= $debug_info['max_date_in_table'] ?></li>
                        <?php endif; ?>
                        <?php if (isset($debug_info['note'])): ?>
                            <li><strong>Not:</strong> <?= htmlspecialchars($debug_info['note']) ?></li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if (isset($debug_info['sample_records']) && !empty($debug_info['sample_records'])): ?>
                        <h5 style="margin-top: 1rem; margin-bottom: 0.5rem;">Örnek Kayıtlar (Son 5):</h5>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <tr style="background: #f0f8ff;">
                                <?php if ($table_name === 'tirlar'): ?>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">ID</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Plaka</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Dolum Başlama</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Dolum Bitiş</th>
                                <?php elseif ($table_name === 'gemi_bosaltma'): ?>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">ID</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Sensör</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Okuma Zamanı</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Debi</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Toplam</th>
                                <?php elseif ($table_name === 'gemi_bosaltma_toplam'): ?>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Sensör</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Başlangıç</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Bitiş</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Ort. Debi</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Toplam</th>
                                <?php elseif ($table_name === 'tank_verileri'): ?>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">ID</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Tank</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Radar</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Sıcaklık (°C)</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Basınç (bar)</th>
                                    <th style="border: 1px solid #ccc; padding: 0.5rem;">Tarih/Saat</th>
                                <?php endif; ?>
                            </tr>
                            <?php foreach ($debug_info['sample_records'] as $sample): ?>
                                <tr>
                                    <?php if ($table_name === 'tirlar'): ?>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['id'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= htmlspecialchars($sample['plaka']) ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['dolumbaslama'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['dolumbitis'] ?></td>
                                    <?php elseif ($table_name === 'gemi_bosaltma'): ?>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['id'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= htmlspecialchars($sample['sensor_adi']) ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['okuma_zamani'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['debi'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['toplam'] ?></td>
                                    <?php elseif ($table_name === 'gemi_bosaltma_toplam'): ?>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= htmlspecialchars($sample['sensor_adi'] ?? '') ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['baslangic_zamani'] ?? '' ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['bitis_zamani'] ?? '' ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['ortalama_debi'] ?? '' ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= ($sample['son_toplam'] ?? 0) - ($sample['ilk_toplam'] ?? 0) ?></td>
                                    <?php elseif ($table_name === 'tank_verileri'): ?>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['id'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;">Tank <?= $sample['tank'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['rdr'] ?></td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= number_format(($sample['pt100'] / 10), 2, ',', '.') ?>°C</td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= number_format(($sample['bsnc'] / 100), 2, ',', '.') ?> bar</td>
                                        <td style="border: 1px solid #ccc; padding: 0.5rem;"><?= $sample['tarihsaat'] ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </footer>

        </footer>
    </div>

    <script>
        function exportToCSV() {
            const table = document.getElementById('dataTable');
            if (!table) return;

            let csv = '';
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cols = rows[i].querySelectorAll('td, th');
                const csvRow = [];
                
                for (let j = 0; j < cols.length; j++) {
                    csvRow.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                
                csv += csvRow.join(',') + '\n';
            }
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'scada_rapor_' + new Date().toISOString().slice(0, 10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function exportToPDF() {
            window.print();
        }

        function toggleDebug() {
            const panel = document.getElementById('debugPanel');
            const button = document.getElementById('debugToggle');
            
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                button.innerHTML = '🔧 Debug Bilgilerini Gizle';
            } else {
                panel.style.display = 'none';
                button.innerHTML = '🔧 Debug Bilgileri';
            }
        }

        // Otomatik yenileme (isteğe bağlı)
        // setInterval(() => {
        //     window.location.reload();
        // }, 300000); // 5 dakikada bir yenile

        // Tank Filtreleme İşlevleri
        let activeFilters = new Set();

        function filterTableByTank(tankNo) {
            // Sadece tank_verileri tablosunda filtrele
            if (window.location.search.indexOf('tank_verileri') === -1) {
                return;
            }

            if (activeFilters.has(tankNo)) {
                // Filtreyi kaldır
                activeFilters.delete(tankNo);
            } else {
                // Filtreyi ekle
                activeFilters.add(tankNo);
            }

            updateTableFilter();
            updateFilterDisplay();
        }

        function updateTableFilter() {
            const table = document.getElementById('dataTable');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (activeFilters.size === 0) {
                    // Hiç filtre yoksa tüm satırları göster
                    row.classList.remove('hidden');
                } else {
                    // Tank sütununu kontrol et (1. sütun, index 0)
                    const tankCell = row.querySelector('td:first-child');
                    if (tankCell) {
                        const tankText = tankCell.textContent.trim();
                        const tankNumber = tankText.replace('Tank ', '');
                        
                        if (activeFilters.has(parseInt(tankNumber))) {
                            row.classList.remove('hidden');
                        } else {
                            row.classList.add('hidden');
                        }
                    }
                }
            });

            updateDataCount();
        }

        function updateFilterDisplay() {
            const filterDiv = document.getElementById('tableFilter');
            const activeFiltersDiv = document.getElementById('activeFilters');
            
            if (!filterDiv || !activeFiltersDiv) return;

            if (activeFilters.size === 0) {
                filterDiv.style.display = 'none';
            } else {
                filterDiv.style.display = 'block';
                
                // Aktif filtreleri göster
                activeFiltersDiv.innerHTML = '';
                activeFilters.forEach(tankNo => {
                    const filterTag = document.createElement('div');
                    filterTag.className = `filter-tag tank-${tankNo}-filter`;
                    filterTag.innerHTML = `
                        Tank ${tankNo}
                        <button class="close-btn" onclick="removeFilter(${tankNo})" title="Filtreyi kaldır">×</button>
                    `;
                    activeFiltersDiv.appendChild(filterTag);
                });
            }
        }

        function removeFilter(tankNo) {
            activeFilters.delete(tankNo);
            updateTableFilter();
            updateFilterDisplay();
        }

        function clearAllFilters() {
            activeFilters.clear();
            updateTableFilter();
            updateFilterDisplay();
        }

        function updateDataCount() {
            const table = document.getElementById('dataTable');
            const dataCountSpan = document.querySelector('.data-count');
            
            if (!table || !dataCountSpan) return;

            const visibleRows = table.querySelectorAll('tbody tr:not(.hidden)');
            const totalRows = table.querySelectorAll('tbody tr').length;
            
            if (activeFilters.size === 0) {
                dataCountSpan.textContent = `${totalRows} kayıt`;
            } else {
                dataCountSpan.textContent = `${visibleRows.length} / ${totalRows} kayıt`;
            }
        }

        // Grafik İşlevleri
        let flowChart = null;

        // PHP verilerini JavaScript'e aktar
        <?php if ($table_name === 'gemi_bosaltma' && !empty($data)): ?>
        const flowData = <?= json_encode($data) ?>;
        
        // Veriyi işle ve grafik için hazırla
        function prepareChartData() {
            const gflow1Data = [];
            const gflow2Data = [];
            
            flowData.forEach(row => {
                const timestamp = row.okuma_zamani;
                const debi_ton_h = row.debi || 0; // Zaten T/h cinsinden
                
                const dataPoint = {
                    x: timestamp,
                    y: debi_ton_h
                };
                
                if (row.sensor_adi === 'gflow1') {
                    gflow1Data.push(dataPoint);
                } else if (row.sensor_adi === 'gflow2') {
                    gflow2Data.push(dataPoint);
                }
            });
            
            // Zamana göre sırala
            gflow1Data.sort((a, b) => new Date(a.x) - new Date(b.x));
            gflow2Data.sort((a, b) => new Date(a.x) - new Date(b.x));
            
            return { gflow1Data, gflow2Data };
        }

        function initChart() {
            const ctx = document.getElementById('flowChart');
            if (!ctx) return;
            
            const { gflow1Data, gflow2Data } = prepareChartData();
            
            flowChart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [
                        {
                            label: 'Rıhtım 7',
                            data: gflow1Data,
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.4,
                            fill: false,
                            pointRadius: 2
                        },
                        {
                            label: 'Rıhtım 8',
                            data: gflow2Data,
                            borderColor: '#2196F3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            fill: false,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'minute',
                                displayFormats: {
                                    minute: 'HH:mm',
                                    hour: 'HH:mm'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Zaman'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Debi (Ton/h)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function(context) {
                                    return new Date(context[0].raw.x).toLocaleString('tr-TR');
                                },
                                label: function(context) {
                                    return context.dataset.label + ': ' + 
                                           context.parsed.y.toFixed(2) + ' Ton/h';
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        // Sayfa yüklendiğinde grafiği başlat
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('flowChart')) {
                console.log('Grafik başlatılıyor...');
                initChart();
            }
            
            // Tank verileri sayfasında ise bilgi göster
            if (window.location.search.indexOf('tank_verileri') !== -1) {
                const tanks = document.querySelectorAll('.tank-display');
                if (tanks.length > 0) {
                    // Kısa bir süre sonra ipucu göster
                    setTimeout(() => {
                        if (activeFilters.size === 0) {
                            console.log('💡 İpucu: Tank kutularına tıklayarak tabloyu filtreleyebilirsiniz!');
                        }
                    }, 2000);
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
