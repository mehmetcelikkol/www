

<?php
$dbDosya = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/energy.db';

date_default_timezone_set('Europe/Istanbul');
echo date('d.m.Y H:i:s');

try {
    $db = new PDO("sqlite:$dbDosya");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fonksiyon: Array veriyi HTML tabloya çevir
    function tabloGoster($sorgu, $db) {
        $stmt = $db->query($sorgu);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(count($rows) == 0){
            echo "<p>Veri yok</p>";
            return;
        }

        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        // Başlık satırı
        echo "<tr>";
        foreach(array_keys($rows[0]) as $baslik){
            echo "<th>$baslik</th>";
        }
        echo "</tr>";

        // Veri satırları
        foreach($rows as $row){
            echo "<tr>";
            foreach($row as $cell){
                echo "<td>$cell</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
    }

    echo "<h2>Kullanıcılar Tablosu</h2>";
    tabloGoster("SELECT * FROM kullanicilar", $db);

    echo "<h2>Cihazlar Tablosu</h2>";
    tabloGoster("SELECT * FROM cihazlar", $db);

        echo "<h2>Cihaz Adresleri</h2>";
    tabloGoster("SELECT * FROM cihaz_adresleri", $db);

        echo "<h2>Kanallar Tablosu</h2>";
    tabloGoster("SELECT * FROM kanallar", $db);

    echo "<h2>Ölçümler Tablosu (Son 20)</h2>";
    tabloGoster("SELECT * FROM olcumler ORDER BY kayit_zamani DESC LIMIT 20", $db);

    echo "<h2>İşlem Log Tablosu (Son 20)</h2>";
    tabloGoster("SELECT * FROM islemler_log ORDER BY tarih DESC LIMIT 20", $db);



} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

 
?>
