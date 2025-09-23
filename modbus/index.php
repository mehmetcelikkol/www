<?php
// Veritabanı bağlantı bilgileri
$host = "127.0.0.1";
$dbname = "scada1";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}

$hostname = gethostname();
$ip = gethostbyname($hostname);
$datetime = date("Y-m-d H:i:s");

// Adres açıklamaları önceden alınıyor
$adresAciklamalari = [];
try {
    $stmt = $pdo->query("SELECT adres, aciklama FROM adresler");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $adresAciklamalari[$row['adres']] = $row['aciklama'];
    }
} catch (PDOException $e) {
    echo "Adres açıklamaları alınamadı: " . $e->getMessage();
}

function printTable($pdo, $tableName, $adresAciklamalari) {
    echo "<a id='$tableName'></a>";
    echo "<h3>📋 Tablo: <code>$tableName</code></h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse; min-width: 600px;'>";
            echo "<thead><tr>";
            foreach (array_keys($rows[0]) as $col) {
                echo "<th style='background-color:#f2f2f2;'>$col</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $colName => $val) {
                    $val = $val ?? '';
                    // Adres açıklamasını gösterme mantığı
                    if (
                        ($tableName === 'commanddata' && $colName === 'comname') ||
                        ($tableName === 'sensordata' && $colName === 'SensorName')
                    ) {
                        if (isset($adresAciklamalari[$val])) {
                            $val = htmlspecialchars($adresAciklamalari[$val], ENT_QUOTES, 'UTF-8') /*. "<span style='display:none'> (" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . ")</span>"*/;

                        }
                    }
                    echo "<td>" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table><br>";
        } else {
            echo "<p style='color:gray;'>📭 Tablo boş.</p><br>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>⚠️ $tableName okunamadı: " . htmlspecialchars($e->getMessage()) . "</p><br>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>SCADA Test Paneli</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        table {
            margin-bottom: 30px;
        }
        th, td {
            padding: 6px 12px;
        }
        .menu {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 30px;
        }
        .menu a {
            margin-right: 12px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>
    <h2>📟 SCADA - PLC Bilgileri Test Sayfası</h2>
    <p><strong>🖥️ Ana Bilgisayar Adı:</strong> <?php echo htmlspecialchars($hostname); ?></p>
    <p><strong>🌐 IP Adresi:</strong> <?php echo htmlspecialchars($ip); ?></p>
    <p><strong>⏰ Tarih / Saat:</strong> <?php echo $datetime; ?></p>

    <div class="menu">
        <strong>📄 Tablolar:</strong>
        <?php
        $tables = ['commanddata', 'gemilog', 'rolepermissions', 'tnkcks_log', 'plc_config', 'drv_config', 'plc_data', 'sensordata', 'users', 'roles', 'permissions', 'logs'];
        foreach ($tables as $table) {
            echo "<a href='#$table'>$table</a>";
        }
        ?>
    </div>

    <?php
    foreach ($tables as $table) {
        printTable($pdo, $table, $adresAciklamalari);
    }
    ?>
</body>
</html>
