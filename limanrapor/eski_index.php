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
    'gemi_bosaltma_toplam' => 'Gemi Boşaltma',
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
            // Her tank için en son veriyi al - DÜZELTME
            $latest_sql = "SELECT * FROM tank_verileri 
                          WHERE tank IN (1, 2) 
                          ORDER BY tarihsaat DESC 
                          LIMIT 10"; // Son 10 kaydı al, sonra filtreleyeceğiz
            
            $latest_stmt = $pdo->prepare($latest_sql);
            $latest_stmt->execute();
            $all_latest = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Bu satırı kaldırın:
            // console.log('Tank verisi sorgu sonucu:', count($all_latest), 'kayıt bulundu');
            
            // Tank 1 ve Tank 2 için ayrı ayrı en son veriyi bul
            foreach ([1, 2] as $tank_num) {
                foreach ($all_latest as $row) {
                    if ($row['tank'] == $tank_num) {
                        $tank_latest_data[$tank_num] = $row;
                        break; // İlk (en son) veriyi alınca döngüden çık
                    }
                }
            }
        
        // Debug: Tank verilerini kontrol et
        if ($debug_mode) {
            $debug_info['tank_data_found'] = [
                'total_records' => count($all_latest),
                'tank1_found' => isset($tank_latest_data[1]),
                'tank2_found' => isset($tank_latest_data[2]),
                'sample_data' => array_slice($all_latest, 0, 3)
            ];
        }
        
    } catch(PDOException $e) {
        // Hata durumunda boş bırak
        $tank_latest_data = [];
        if ($debug_mode) {
            $debug_info['tank_data_error'] = $e->getMessage();
        }
    }
}

// Ana try-catch'i de kapatın
} catch(PDOException $e) {
    $data = [];
    $error_message = "Veri çekme hatası: " . $e->getMessage();
}
/*
// Tank kg hesaplama fonksiyonu - KG/Litre Tablolarını Kullanarak
function calculateTankKg($tank_no, $cm_value, $pdo) {
    try {
        // cm'yi mm'ye çevir (çünkü kgli tablosunda mm cinsinden)
        $mm_value = $cm_value * 10;
        
        // Kgli tablosu adını oluştur
        $kgli_table = "tank{$tank_no}_kgli";
        
        // Önce tablo var mı kontrol et
        $table_check_sql = "SHOW TABLES LIKE '{$kgli_table}'";
        $table_check_stmt = $pdo->prepare($table_check_sql);
        $table_check_stmt->execute();
        
        if ($table_check_stmt->rowCount() == 0) {
            // Kgli tablosu yoksa basit hesaplama
            return $cm_value * 1000; // 1 cm = 1000 kg varsayımı
        }
        
        // mm değerinden küçük veya eşit olan en büyük kaydı al
        $sql = "SELECT kg FROM `{$kgli_table}` 
                WHERE mm <= :mm_value 
                ORDER BY mm DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mm_value', $mm_value, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['kg'])) {
            return floatval($result['kg']);
        } else {
            // Veri bulunamazsa interpolasyon dene
            return interpolateKgFromKgli($tank_no, $mm_value, $pdo);
        }
        
    } catch(PDOException $e) {
        // Hata durumunda basit hesaplama
        error_log("Tank kg hesaplama hatası: " . $e->getMessage());
        return $cm_value * 1000;
    }
}

// KGLi tablosundan interpolasyon ile kg hesaplama
function interpolateKgFromKgli($tank_no, $mm_value, $pdo) {
    try {
        $kgli_table = "tank{$tank_no}_kgli";
        
        // mm değerinden küçük en büyük değer
        $lower_sql = "SELECT mm, kg FROM `{$kgli_table}` 
                      WHERE mm <= :mm_value 
                      ORDER BY mm DESC 
                      LIMIT 1";
        
        // mm değerinden büyük en küçük değer
        $upper_sql = "SELECT mm, kg FROM `{$kgli_table}` 
                      WHERE mm > :mm_value 
                      ORDER BY mm ASC 
                      LIMIT 1";
        
        $lower_stmt = $pdo->prepare($lower_sql);
        $lower_stmt->bindValue(':mm_value', $mm_value);
        $lower_stmt->execute();
        $lower = $lower_stmt->fetch(PDO::FETCH_ASSOC);
        
        $upper_stmt = $pdo->prepare($upper_sql);
        $upper_stmt->bindValue(':mm_value', $mm_value);
        $upper_stmt->execute();
        $upper = $upper_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lower && $upper) {
            // İki nokta arasında linear interpolasyon
            $mm1 = floatval($lower['mm']);
            $kg1 = floatval($lower['kg']);
            $mm2 = floatval($upper['mm']);
            $kg2 = floatval($upper['kg']);
            
            // Linear interpolasyon formülü
            $interpolated_kg = $kg1 + ($mm_value - $mm1) * ($kg2 - $kg1) / ($mm2 - $mm1);
            
            return round($interpolated_kg, 0);
        } elseif ($lower) {
            // Sadece alt değer varsa onu kullan
            return floatval($lower['kg']);
        } elseif ($upper) {
            // Sadece üst değer varsa onu kullan
            return floatval($upper['kg']);
        } else {
            // Hiç veri yoksa basit hesaplama
            return ($mm_value / 10) * 1000; // mm'yi cm'ye çevir, sonra basit çarp
        }
        
    } catch(PDOException $e) {
        error_log("KGLi interpolasyon hatası: " . $e->getMessage());
        return ($mm_value / 10) * 1000;
    }
}

// Ton hesaplama fonksiyonu (isteğe bağlı)
function calculateTankTon($tank_no, $cm_value, $pdo) {
    try {
        $mm_value = $cm_value * 10;
        $kgli_table = "tank{$tank_no}_kgli";
        
        $sql = "SELECT ton FROM `{$kgli_table}` 
                WHERE mm <= :mm_value 
                ORDER BY mm DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mm_value', $mm_value, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['ton'])) {
            return floatval($result['ton']);
        } else {
            // Kg'dan ton'a çevir
            $kg = calculateTankKg($tank_no, $cm_value, $pdo);
            return $kg / 1000;
        }
        
    } catch(PDOException $e) {
        // Kg'dan ton'a çevir
        $kg = calculateTankKg($tank_no, $cm_value, $pdo);
        return $kg / 1000;
    }
}

// Litre hesaplama fonksiyonu (isteğe bağlı)
function calculateTankLitre($tank_no, $cm_value, $pdo) {
    try {
        $mm_value = $cm_value * 10;
        $kgli_table = "tank{$tank_no}_kgli";
        
        $sql = "SELECT litre FROM `{$kgli_table}` 
                WHERE mm <= :mm_value 
                ORDER BY mm DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mm_value', $mm_value, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['litre'])) {
            return floatval($result['litre']);
        } else {
            return $cm_value * 1000; // Basit hesaplama
        }
        
    } catch(PDOException $e) {
        return $cm_value * 1000;
    }
}

// Debug: KGLi tablosu kontrolü
function debugKgliTable($tank_no, $pdo) {
    try {
        $kgli_table = "tank{$tank_no}_kgli";
        
        $sql = "SELECT COUNT(*) as count, 
                       MIN(mm) as min_mm, MAX(mm) as max_mm,
                       MIN(kg) as min_kg, MAX(kg) as max_kg,
                       MIN(litre) as min_litre, MAX(litre) as max_litre,
                       MIN(ton) as min_ton, MAX(ton) as max_ton
                FROM `{$kgli_table}`";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'table_name' => $kgli_table,
            'record_count' => $info['count'],
            'mm_range' => $info['min_mm'] . ' - ' . $info['max_mm'] . ' mm',
            'kg_range' => number_format($info['min_kg'], 2) . ' - ' . number_format($info['max_kg'], 2) . ' kg',
            'litre_range' => number_format($info['min_litre'], 2) . ' - ' . number_format($info['max_litre'], 2) . ' L',
            'ton_range' => number_format($info['min_ton'], 3) . ' - ' . number_format($info['max_ton'], 3) . ' ton'
        ];
        
    } catch(PDOException $e) {
        return [
            'table_name' => $kgli_table,
            'error' => $e->getMessage()
        ];
    }
}
*/
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Rapor Sistemi</title>
    <!-- LOCAL Chart.js dosyaları - İnternet gerektirmez -->
    <script src="js/chart.umd.js"></script>
    <script src="js/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 200px);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .data-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .data-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
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
            background: #f8fafc;
        }

        .data-table tr:nth-child(odd) {
            background: white;
        }

        .data-table tr:hover {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
            transform: scale(1.005);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

        /* Chart bölümü stilleri */
        .chart-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .chart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }

        .chart-header h3 {
            margin: 0;
            font-weight: 600;
        }

        .chart-container {
            padding: 2rem;
            height: 400px;
            position: relative;
        }

        #flowChart {
            max-height: 350px;
            width: 100% !important;
            height: 100% !important;
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
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            text-align: center;
            padding: 2.5rem 0;
            margin-top: 3rem;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
            border-top: none;
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
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .tank-dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.25rem 1.5rem;
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

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }

        .status-active {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
        }

        .status-inactive {
            background: linear-gradient(135deg, #e17055, #d63031);
            color: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
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
            <div style="margin-top: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; align-items: center;">
                    <a href="?table=tank_verileri" class="btn" style="<?= $table_name === 'tank_verileri' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🛢️ Tank İzleme</a>
                    <a href="?table=gemi_bosaltma" class="btn" style="<?= $table_name === 'gemi_bosaltma' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🚢 Akış Hızı</a>
                    <a href="?table=gemi_bosaltma_toplam" class="btn" style="<?= $table_name === 'gemi_bosaltma_toplam' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🚢 Gemi Boşaltma</a>
                    <a href="?table=tirlar" class="btn" style="<?= $table_name === 'tirlar' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🚛 Tır İşlemleri</a>
                </div>
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
                <h3>📈 Debi Grafiği (Ton/h) - Veri Sayısı: <?= count($data) ?></h3>
            </div>
            <div class="chart-container">
                <canvas id="flowChart" width="800" height="400"></canvas>
                <div id="chartStatus" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px; border-radius: 3px; font-size: 12px;">
                    Grafik yükleniyor...
                </div>
            </div>
        </div>
        <?php elseif ($table_name === 'gemi_bosaltma' && empty($data)): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <strong>Grafik Bilgileri:</strong>
            <ul>
                <li>Tablo: <?= $table_name ?></li>
                <li>Veri sayısı: <?= count($data) ?></li>
                <li>Grafik koşulu: <?= ($table_name === 'gemi_bosaltma' && !empty($data)) ? 'SAĞLANIYOR ✅' : 'SAĞLANMIYOR ❌' ?></li>
            </ul>
            <p><strong>Not:</strong> Akış hızı verisi olmadığı için grafik gösterilemiyor. Verilerin yüklendiğinden emin olun.</p>
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
                        
                        // DÜZELTME: Doğrudan veritabanından kg değerlerini kullan
                        $radar_kg = $tank_data['rdrmetre'] ?? 0;    // Hesaplanmış kg (zaten hesaplanmış)
                        $basinc_kg = $tank_data['bsncmetre'] ?? 0;  // Basınç kg değeri (zaten hesaplanmış)

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
                                    $radar_cm = ($row['rdr'] ?? 0) / 10; // mm'den cm'ye - DÜZELTME: rdr kullan
                                    
                                    // Sıcaklık PT100 değeri (10'a bölünmesi gerekiyor, 2 ondalık)
                                    $sicaklik = ($row['pt100'] ?? 0) / 10;
                                    
                                    // Basınç değerini bar cinsinden hesapla (2 ondalık)
                                    $basinc_raw = $row['bsnc'] ?? 0;
                                    $basinc_bar = $basinc_raw / 100; // Basınç birimi muhtemelen mbar veya benzeri
                                    
                                    // Basınç metre değerini santimetre cinsinden hesapla
                                    $basinc_cm = 0; // Basınç cm değeri yok - DÜZELTME
                                    
                                        // DÜZELTME: Doğrudan veritabanından kg değerlerini kullan
                                    $radar_kg = $row['rdrmetre'] ?? 0;    // Hesaplanmış kg (zaten hesaplanmış)
                                    $basinc_kg = $row['bsncmetre'] ?? 0;  // Basınç kg değeri (zaten hesaplanmış)

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
                                        <td class="amount">-</td> <!-- Basınç cm yok -->
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
        </div> <!-- data-section kapanışı -->

        <!-- Gemi Log Kayıtları Tablosu -->
        <?php if ($table_name === 'gemi_bosaltma_toplam'): ?>
        <div class="data-section" style="margin-top: 2rem;">
            <div class="data-header">
                <h3>🚢 Gemi Log Kayıtları (Son 100 Kayıt)</h3>
                <div class="header-actions">
                    <span class="data-count" id="gemilogCount">0 kayıt</span>
                </div>
            </div>
            
            <?php
            // Gemilog tablosundan son 100 kayıt
            try {
                $gemilog_sql = "SELECT * FROM gemilog ORDER BY tarihsaat DESC LIMIT 100";
                $gemilog_stmt = $pdo->prepare($gemilog_sql);
                $gemilog_stmt->execute();
                $gemilog_results = $gemilog_stmt->fetchAll();
            } catch(PDOException $e) {
                $gemilog_results = [];
                echo "<div class='alert alert-warning'>Gemilog verileri alınamadı: " . $e->getMessage() . "</div>";
            }
            ?>
            
            <?php if (!empty($gemilog_results)): ?>
                <div class="table-container">
                    <table class="data-table" style="margin-bottom: 2rem;">
                        <thead>
                            <tr>
                                <th>Gemi No</th>
                                <th>Aktif Yön</th>
                                <th>Radar 1 (cm)</th>
                                <th>Radar 2 (cm)</th>
                                <th>Basınç 1 (bar)</th>
                                <th>Basınç 2 (bar)</th>
                                <th>Sıcaklık 1 (°C)</th>
                                <th>Sıcaklık 2 (°C)</th>
                                <th>Flow 1 (m³/h)</th>
                                <th>Flow 2 (m³/h)</th>
                                <th>Kullanıcı</th>
                                <th>Tarih/Saat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gemilog_results as $gemilog_row): ?>
                                <tr>
                                    <td style="font-weight: bold; color: #2c3e50;"><?php echo htmlspecialchars($gemilog_row['gemino']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $gemilog_row['aktif_yon'] == 1 ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $gemilog_row['aktif_yon'] == 1 ? '✅ Aktif' : '❌ Pasif'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($gemilog_row['rdr1'] / 10, 1); ?> cm</td>
                                    <td><?php echo number_format($gemilog_row['rdr2'] / 10, 1); ?> cm</td>
                                    <td><?php echo number_format($gemilog_row['bsn1'] / 100, 2); ?> bar</td>
                                    <td><?php echo number_format($gemilog_row['bsn2'] / 100, 2); ?> bar</td>
                                    <td><?php echo number_format($gemilog_row['pt101'] / 10, 1); ?>°C</td>
                                    <td><?php echo number_format($gemilog_row['pt102'] / 10, 1); ?>°C</td>
                                    <td style="color: #e74c3c; font-weight: bold;"><?php echo number_format($gemilog_row['flow1'] / 100, 2); ?> m³/h</td>
                                    <td style="color: #e74c3c; font-weight: bold;"><?php echo number_format($gemilog_row['flow2'] / 100, 2); ?> m³/h</td>
                                    <td><?php echo htmlspecialchars($gemilog_row['userName']); ?></td>
                                    <td style="font-size: 0.9rem; color: #666;"><?php echo date('d.m.Y H:i:s', strtotime($gemilog_row['tarihsaat'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h4>📊 Gemi Log İstatistikleri</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
                        <div><strong>Toplam Kayıt:</strong> <?php echo count($gemilog_results); ?></div>
                        <div><strong>Veri Aralığı:</strong> Son 100 kayıt</div>
                        <div><strong>Aktif Gemiler:</strong> <?php echo count(array_filter($gemilog_results, function($r) { return $r['aktif_yon'] == 1; })); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #718096;">
                    <h4>📋 Bilgi</h4>
                    <p>Henüz gemi log kaydı bulunmuyor.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div> <!-- container kapanışı -->
    <!-- Ana Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-section">
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

        <?php if ($table_name === 'gemi_bosaltma' && !empty($data)): ?>
        // PHP verilerini JavaScript'e aktar
        const flowData = <?= json_encode($data) ?>;
        
        // Veriyi işle ve grafik için hazırla
        function prepareChartData() {
            const gflow1Data = [];
            const gflow2Data = [];
            
            flowData.forEach(row => {
                const timestamp = row.okuma_zamani;
                const debi_ton_h = parseFloat(row.debi) || 0;
                
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
            const chartStatus = document.getElementById('chartStatus');
            
            try {
                console.log('🚀 initChart başlatıldı');
                console.log('Chart global object:', typeof Chart);
                
                if (typeof Chart === 'undefined') {
                    throw new Error('Chart.js kütüphanesi yüklenmemiş');
                }
                
                chartStatus.innerHTML = 'Canvas elementi kontrol ediliyor...';
                
                const ctx = document.getElementById('flowChart');
                if (!ctx) {
                    console.error('❌ flowChart canvas elementi bulunamadı!');
                    chartStatus.innerHTML = '❌ Canvas bulunamadı';
                    return;
                }
                
                console.log('✅ Canvas element bulundu:', ctx);
                chartStatus.innerHTML = 'Veriler hazırlanıyor...';
                
                const { gflow1Data, gflow2Data } = prepareChartData();
                
                console.log('📊 Grafik verileri hazırlandı:', { 
                    gflow1: gflow1Data.length, 
                    gflow2: gflow2Data.length,
                    sample_gflow1: gflow1Data.slice(0, 3),
                    sample_gflow2: gflow2Data.slice(0, 3)
                });
                
                if (gflow1Data.length === 0 && gflow2Data.length === 0) {
                    console.warn('⚠️ Grafik verisi boş!');
                    chartStatus.innerHTML = '⚠️ Veri bulunamadı';
                    return;
                }
                
                chartStatus.innerHTML = 'Chart.js oluşturuluyor...';
                console.log('⚙️ Chart.js oluşturma başlatılıyor...');

                if (flowChart) {
                    flowChart.destroy();
                    console.log('🔄 Eski grafik temizlendi');
                }
                
                // Chart.js oluştur
                flowChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [
                            {
                                label: 'Rıhtım 7 (gflow1)',
                                data: gflow1Data,
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                tension: 0.4,
                                fill: false,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                borderWidth: 2
                            },
                            {
                                label: 'Rıhtım 8 (gflow2)',
                                data: gflow2Data,
                                borderColor: '#2196F3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                tension: 0.4,
                                fill: false,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                borderWidth: 2
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
                
                console.log('✅ Chart.js oluşturuldu!', flowChart);
                chartStatus.innerHTML = '✅ Grafik oluşturuldu!';
                setTimeout(() => {
                    if (chartStatus) chartStatus.style.display = 'none';
                }, 3000);
                
            } catch (error) {
                console.error('❌ Grafik oluşturma hatası:', error);
                if (chartStatus) {
                    chartStatus.innerHTML = '❌ Hata: ' + error.message;
                }
            }
        }
        <?php endif; ?>

        // Chart.js kontrolü - CDN gerektirmez
        function checkChartJS() {
            if (typeof Chart !== 'undefined') {
                console.log('✅ Chart.js LOCAL dosyalardan yüklendi');
                return true;
            } else {
                console.error('❌ Chart.js LOCAL dosyaları yüklenemedi!');
                return false;
            }
        }

        // DOM ready kontrolü - GÜNCELLENMIŞ
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sayfa yüklendi!');
            
            <?php if ($table_name === 'gemi_bosaltma' && !empty($data)): ?>
            console.log('✅ Gemi boşaltma sayfası - grafik koşulları sağlandı');
            console.log('Veri sayısı:', <?= count($data) ?>);
            
            // Chart.js LOCAL kontrolü
            if (!checkChartJS()) {
                const chartStatus = document.getElementById('chartStatus');
                if (chartStatus) {
                    chartStatus.innerHTML = '❌ Chart.js dosyaları bulunamadı (js/chart.umd.js)';
                }
                return;
            }
            
            if (document.getElementById('flowChart')) {
                console.log('✅ flowChart canvas elementi bulundu');
                console.log('📈 Grafik başlatılıyor...');
                initChart();
            } else {
                console.error('❌ flowChart canvas elementi DOM\'da bulunamadı!');
            }
            <?php else: ?>
            console.log('ℹ️ Grafik koşulları sağlanmadı');
            <?php endif; ?>
            
            // Tank filtreleme ve diğer işlevler...
            if (window.location.search.indexOf('tank_verileri') !== -1) {
                const tanks = document.querySelectorAll('.tank-display');
                if (tanks.length > 0) {
                    setTimeout(() => {
                        if (activeFilters.size === 0) {
                            console.log('💡 İpucu: Tank kutularına tıklayarak tabloyu filtreleyebilirsiniz!');
                        }
                    }, 2000);
                }
            }

            // Gemilog kayıt sayısını güncelle
            const gemilogCount = document.getElementById('gemilogCount');
            if (gemilogCount) {
                const gemilogTable = gemilogCount.closest('.data-section').querySelector('table tbody');
                if (gemilogTable) {
                    const gemilogRows = gemilogTable.querySelectorAll('tr').length;
                    gemilogCount.textContent = `${gemilogRows} kayıt`;
                }
            }
        });
    </script>
</body>
</html>