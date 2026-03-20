<?php
// Version.txt dosyasından mevcut versiyonu oku
$mevcutVersiyon = trim(file_get_contents('version.txt'));

// İndirme isteği geldiğinde
if (isset($_GET['download'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $tarih = date('Y-m-d H:i:s');
    $logSatiri = "$tarih - $ip - $mevcutVersiyon - firmware.ino.esp32.bin\n";
    
    // Log dosyasına kaydet
    file_put_contents('downloads.log', $logSatiri, FILE_APPEND);
    
    // Dosyayı indirme
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="firmware.ino.esp32.bin"');
    readfile('firmware.ino.esp32.bin');
    exit;
}

// Yükleme işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['ino_dosya']) && isset($_FILES['bin_dosya']) && isset($_POST['yeni_versiyon'])) {
        $inoDosya = $_FILES['ino_dosya'];
        $binDosya = $_FILES['bin_dosya'];
        $yeniVersiyon = $_POST['yeni_versiyon'];
        
        $inoYuklemeBasarili = move_uploaded_file(
            $inoDosya['tmp_name'], 
            __DIR__ . '/' . $inoDosya['name']
        );
        
        $binYuklemeBasarili = move_uploaded_file(
            $binDosya['tmp_name'], 
            __DIR__ . '/firmware.ino.esp32.bin'
        );
        
        if ($inoYuklemeBasarili && $binYuklemeBasarili) {
            file_put_contents('version.txt', $yeniVersiyon);
            $mesaj = "Dosyalar başarıyla yüklendi ve versiyon " . $yeniVersiyon . " olarak güncellendi";
        } else {
            $mesaj = "Dosya yüklemede hata oluştu";
        }
    }
}

// Log dosyasından istatistikleri al
$logDosyasi = file_exists('downloads.log') ? file('downloads.log') : [];
$toplamIndirme = count($logDosyasi);
$tekillIpler = array_unique(array_map(function($line) {
    return explode(' - ', $line)[1];
}, $logDosyasi));

// Versiyon bazlı istatistikleri hesapla
$versiyonIstatistikleri = [];
foreach ($logDosyasi as $log) {
    $parcalar = explode(' - ', trim($log));
    $tarih = $parcalar[0];
    $ip = $parcalar[1];
    $versiyon = $parcalar[2];
    
    if (!isset($versiyonIstatistikleri[$versiyon])) {
        $versiyonIstatistikleri[$versiyon] = [];
    }
    if (!isset($versiyonIstatistikleri[$versiyon][$ip])) {
        $versiyonIstatistikleri[$versiyon][$ip] = 0;
    }
    $versiyonIstatistikleri[$versiyon][$ip]++;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Firmware Yükleme ve İstatistikler</title>
    <meta charset="UTF-8">
    <style>
        .stats {
            background: #f0f0f0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .download-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        table {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>Mevcut Versiyon: <?php echo $mevcutVersiyon; ?></h2>
    
    <?php if (isset($mesaj)): ?>
        <p><?php echo $mesaj; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <p>
            <label>INO Dosyası:</label>
            <input type="file" name="ino_dosya" accept=".ino" required>
        </p>
        <p>
            <label>BIN Dosyası:</label>
            <input type="file" name="bin_dosya" accept=".bin" required>
        </p>
        <p>
            <label>Yeni Versiyon:</label>
            <input type="text" name="yeni_versiyon" pattern="\d{1,3}\.\d{2}" 
                   placeholder="örn: 14.03" required>
        </p>
        <button type="submit">Dosyaları Yükle</button>
    </form>

    <div class="stats">
        <h3>İndirme İstatistikleri:</h3>
        <p>Toplam İndirme: <?php echo $toplamIndirme; ?></p>
        <p>Tekil IP Sayısı: <?php echo count($tekillIpler); ?></p>
        
        <h4>Versiyon Bazlı İndirme İstatistikleri:</h4>
        <table>
            <tr>
                <th>Versiyon</th>
                <th>IP Adresi</th>
                <th>İndirme Sayısı</th>
            </tr>
            <?php foreach ($versiyonIstatistikleri as $versiyon => $ipler): ?>
                <?php foreach ($ipler as $ip => $indirmeSayisi): ?>
                    <tr>
                        <td><?php echo $versiyon; ?></td>
                        <td><?php echo $ip; ?></td>
                        <td><?php echo $indirmeSayisi; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>

        <h4>Son 10 İndirme:</h4>
        <ul>
        <?php
        $son10 = array_slice($logDosyasi, -10);
        foreach ($son10 as $log) {
            $parcalar = explode(' - ', $log);
            echo "<li>Tarih: {$parcalar[0]} - IP: {$parcalar[1]} - Versiyon: {$parcalar[2]}</li>";
        }
        ?>
        </ul>
    </div>
</body>
</html>
