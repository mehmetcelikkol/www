<?php 
require_once 'header.php';
?>

<?php 
$gerek = $_GET['gerek'];

echo "<center> <br>";

   echo '<button type="button" class="btn btn-primary btn-lg"><a href="tekrargerek.php?gerek=0">' . "Tekrar gerekmeyenler" . '</a></button>';
   echo '<button type="button" class="btn btn-primary btn-lg"><a href="tekrargerek.php?gerek=1">' . "Tekrar gerekenler" . '</a></button>';

    echo "<br></center>";

    
$sql = "SELECT h.firmaid, h.ekipid1, h.ekipid2, h.tarih, h.arama, h.ziyaret, h.sonuc, h.aciklama, h.gerek,
       f.isim AS firma_isim,
       k.onyuz AS kart_onyuz, k.arkayuz AS kart_arkayuz
       FROM hareket h
       LEFT JOIN firma f ON h.firmaid = f.id
       LEFT JOIN kart k ON h.firmaid = k.firmaid
       WHERE h.gerek = $gerek
       ORDER BY h.tarih DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        echo "<table border=1 align=center>
                <tr>
                    <th>Firma ID</th>               
                    <th>Ekip 1 İsim</th>
                    <th>Ekip 2 İsim</th>
                    <th>Tarih</th>
                    <th>Arama</th>
                    <th>Ziyaret</th>
                    <th>Sonuç</th>
                    <th>Açıklama</th>
                    <th>Gerek</th>
                    <th>Firma İsim</th>
                    <th>Kart Ön Yüz</th>
                    <th>Kart Arka Yüz</th>
                </tr>";
        foreach ($results as $row) {
            $ekipid1 = $row['ekipid1'];
            $ekipid2 = $row['ekipid2'];

            // Ekip 1 ismini al
            $sql_ekip1 = "SELECT isim FROM ekip WHERE id = :ekipid";
            $stmt_ekip1 = $pdo->prepare($sql_ekip1);
            $stmt_ekip1->bindParam(':ekipid', $ekipid1, PDO::PARAM_INT);
            $stmt_ekip1->execute();
            $ekip1_isim = $stmt_ekip1->fetchColumn();

            // Ekip 2 ismini al
            $sql_ekip2 = "SELECT isim FROM ekip WHERE id = :ekipid";
            $stmt_ekip2 = $pdo->prepare($sql_ekip2);
            $stmt_ekip2->bindParam(':ekipid', $ekipid2, PDO::PARAM_INT);
            $stmt_ekip2->execute();
            $ekip2_isim = $stmt_ekip2->fetchColumn();

            // Arama, Ziyaret ve Gerek sütunlarını kontrol et
            $arama = ($row["arama"] == 1) ? "Evet" : "Hayır";
            $ziyaret = ($row["ziyaret"] == 1) ? "Evet" : "Hayır";
            $gerek = ($row["gerek"] == 1) ? "Evet" : "Hayır";

            // Kart Ön Yüz ve Kart Arka Yüz durumunu kontrol et
            $kart_onyuz_durum = (!empty($row["kart_onyuz"])) ? "Dolu" : "Boş";
            $kart_arkayuz_durum = (!empty($row["kart_arkayuz"])) ? "Dolu" : "Boş";

            echo "<tr>
                    <td>".$row["firmaid"]."</td>
                    <td>".$ekip1_isim."</td>
                    <td>".$ekip2_isim."</td>
                    <td>".$row["tarih"]."</td>
                    <td>".$arama."</td>
                    <td>".$ziyaret."</td>
                    <td>".$row["sonuc"]."</td>
                    <td>".$row["aciklama"]."</td>
                    <td>".$gerek."</td>
                    <td>".$row["firma_isim"]."</td>
                    <td>".$kart_onyuz_durum."</td>
                    <td>".$kart_arkayuz_durum."</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "0 sonuç";
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

require_once 'footer.php';
?>
