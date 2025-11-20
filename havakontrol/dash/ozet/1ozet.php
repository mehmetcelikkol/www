<?php
include '../conn.php';

$sql = "SELECT 
    v.serino,
    v.temp,
    v.hum,
    v.wifi,
    v.versiyon,
    v.oturum,
    v.ip,
    v.kayit_tarihi,
    c.firmaid,
    c.konum,
    cr.unvan as firma_adi,
    TIMESTAMPDIFF(MINUTE, v.kayit_tarihi, NOW()) as dakika_once
FROM (
    SELECT serino, MAX(kayit_tarihi) as son_kayit
    FROM veriler 
    GROUP BY serino
) as son
JOIN veriler v ON v.serino = son.serino AND v.kayit_tarihi = son.son_kayit
LEFT JOIN cihazlar c ON v.serino = c.serino
LEFT JOIN cari cr ON c.firmaid = cr.id
ORDER BY v.kayit_tarihi DESC";

// SQL sorgusunu çalıştır
$result = $conn->query($sql);

// Sorgu hatası kontrolü
if (!$result) {
    die("SQL Hatası: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veri Tablosu</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

    <h1>Veri Tablosu</h1>
    
    <table>
        <thead>
            <tr>
                <th>Serino</th>
                <th>Temp</th>
                <th>Hum</th>
                <th>WiFi</th>
                <th>Versiyon</th>
                <th>Oturum</th>
                <th>IP</th>
                <th>Kayıt Tarihi</th>
                <th>Firma ID</th>
                <th>Konum</th>
                <th>Firma Adı</th>
                <th>Dakika Önce</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Veri çekme işlemi ve tabloya ekleme
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['serino']) . "</td>";
                echo "<td>" . htmlspecialchars($row['temp']) . "</td>";
                echo "<td>" . htmlspecialchars($row['hum']) . "</td>";
                echo "<td>" . htmlspecialchars($row['wifi']) . "</td>";
                echo "<td>" . htmlspecialchars($row['versiyon']) . "</td>";
                echo "<td>" . htmlspecialchars($row['oturum']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ip']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kayit_tarihi']) . "</td>";
                echo "<td>" . htmlspecialchars($row['firmaid']) . "</td>";
                echo "<td>" . htmlspecialchars($row['konum']) . "</td>";
                echo "<td>" . htmlspecialchars($row['firma_adi']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dakika_once']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
