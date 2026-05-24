<?php 
require_once 'header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Tablo Başlıklarında Filtreleme Özelliği -->
    <table class="table table-striped table-bordered" id="firmalarTablosu">
      <thead class="thead-dark">
        <tr>
          <th><center>Firma</center><input type="text" id="firmaFiltre" placeholder="Firma Filtre"></th>
          <th><center>Personel - 1</center><input type="text" id="personel1Filtre" placeholder="Personel 1 Filtre"></th>
          <th><center>Personel - 2</center><input type="text" id="personel2Filtre" placeholder="Personel 2 Filtre"></th>
          <th><center>Tarih</center>
            <input type="date" id="baslangicTarih" placeholder="Başlangıç Tarihi">
            <input type="date" id="bitisTarih" placeholder="Bitiş Tarihi">
          </th>
          <th><center>Telefon arama</center><input type="text" id="aramaFiltre" placeholder="Arama Filtre"></th>
          <th><center>Ziyaret</center><input type="text" id="ziyaretFiltre" placeholder="Ziyaret Filtre"></th>
          <th><center>Sonuç</center><input type="text" id="sonucFiltre" placeholder="Sonuç Filtre"></th>
          <th><center>Tekrar Gerekli mi?</center><input type="text" id="tekrarFiltre" placeholder="Tekrar Gerekli mi? Filtre"></th>
          <th><center>Ön Yüz</center></th>
          <th><center>Arka Yüz</center></th>
          <th><center>Ziyaret Sayısı</center></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT h.firmaid, h.ekipid1, h.ekipid2, h.tarih, h.arama, h.ziyaret, h.sonuc, h.aciklama, h.gerek,
        f.isim AS firma_isim,
        k.onyuz AS kart_onyuz, k.arkayuz AS kart_arkayuz
        FROM hareket h
        LEFT JOIN firma f ON h.firmaid = f.id
        LEFT JOIN kart k ON h.firmaid = k.firmaid
        ORDER BY h.tarih DESC";

        try {
          $stmt = $pdo->prepare($sql);
          $stmt->execute();
          $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

          foreach ($results as $result) {
            $firmaid = $result['firmaid'];
            $ekipid1 = $result['ekipid1'];
            $ekipid2 = $result['ekipid2'];
            $tarih = $result['tarih'];
            
            if ($tarih === "0000-00-00") {
              $formatliTarih = "boş";
            } else {
              $formatliTarih = date('d.m.Y', strtotime($tarih));
            }

            $arama = $result['arama'];
            $ziyaret = $result['ziyaret'];
            $sonuc = $result['sonuc'];
            $aciklama = $result['aciklama'];
            $gerek = $result['gerek'] == 1 ? "EVET" : "HAYIR";
            $firma_isim = $result['firma_isim'];

            $kart_onyuz = $result['kart_onyuz'];
            $kart_arkayuz = $result['kart_arkayuz'];

            // Ekip isimlerini al
            $sql_ekip1 = "SELECT isim FROM ekip WHERE id = :ekipid";
            $stmt_ekip1 = $pdo->prepare($sql_ekip1);
            $stmt_ekip1->bindParam(':ekipid', $ekipid1);
            $stmt_ekip1->execute();
            $ekip1_isim = $stmt_ekip1->fetchColumn();

            $sql_ekip2 = "SELECT isim FROM ekip WHERE id = :ekipid";
            $stmt_ekip2 = $pdo->prepare($sql_ekip2);
            $stmt_ekip2->bindParam(':ekipid', $ekipid2);
            $stmt_ekip2->execute();
            $ekip2_isim = $stmt_ekip2->fetchColumn();

            // Firma Ziyaret Sayısını Al
            $sql_ziyaret_sayisi = "SELECT COUNT(*) FROM hareket WHERE firmaid = :firmaid";
            $stmt_ziyaret_sayisi = $pdo->prepare($sql_ziyaret_sayisi);
            $stmt_ziyaret_sayisi->bindParam(':firmaid', $firmaid);
            $stmt_ziyaret_sayisi->execute();
            $ziyaret_sayisi = $stmt_ziyaret_sayisi->fetchColumn();

            echo '<tr>';
            echo '<td>' . $firma_isim . '</td>';
            echo '<td>' . $ekip1_isim . '</td>';
            echo '<td>' . $ekip2_isim . '</td>';
            echo '<td>' . $formatliTarih . '</td>';
            echo '<td>' . $arama . '</td>';
            echo '<td>' . $ziyaret . '</td>';
            echo '<td>' . $sonuc . '</td>';
            echo '<td>' . $gerek . '</td>';
            echo '<td>';
            if (!empty($kart_onyuz)) {
              echo '<img src="' . $kart_onyuz . '" alt="Ön Yüz" width="100" height="70"/>';
            }
            echo '</td>';
            echo '<td>';
            if (!empty($kart_arkayuz)) {
              echo '<img src="' . $kart_arkayuz . '" alt="Arka Yüz" width="100" height="70"/>';
            }
            echo '</td>';
            echo '<td>' . $ziyaret_sayisi . '</td>'; // Ziyaret sayısını ekle
            echo '</tr>';
          }

        } catch (PDOException $e) {
          die("Veritabanı hatası: " . $e->getMessage());
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Tüm filtrelerin çalışması için event listener ekleyelim
document.addEventListener('DOMContentLoaded', function() {
  // Tarih aralığı filtreleme işlevi
  document.getElementById('baslangicTarih').addEventListener('change', tarihFiltrele);
  document.getElementById('bitisTarih').addEventListener('change', tarihFiltrele);

  // Diğer filtrelerin işlevlerini ekle
  document.getElementById('firmaFiltre').addEventListener('keyup', filtrele);
  document.getElementById('personel1Filtre').addEventListener('keyup', filtrele);
  document.getElementById('personel2Filtre').addEventListener('keyup', filtrele);
  document.getElementById('aramaFiltre').addEventListener('keyup', filtrele);
  document.getElementById('ziyaretFiltre').addEventListener('keyup', filtrele);
  document.getElementById('sonucFiltre').addEventListener('keyup', filtrele);
  document.getElementById('tekrarFiltre').addEventListener('keyup', filtrele);

  function filtrele() {
    var firmaFiltre = document.getElementById('firmaFiltre').value.toUpperCase();
    var personel1Filtre = document.getElementById('personel1Filtre').value.toUpperCase();
    var personel2Filtre = document.getElementById('personel2Filtre').value.toUpperCase();
    var aramaFiltre = document.getElementById('aramaFiltre').value.toUpperCase();
    var ziyaretFiltre = document.getElementById('ziyaretFiltre').value.toUpperCase();
    var sonucFiltre = document.getElementById('sonucFiltre').value.toUpperCase();
    var tekrarFiltre = document.getElementById('tekrarFiltre').value.toUpperCase();
    
    var table = document.getElementById('firmalarTablosu');
    var tr = table.getElementsByTagName('tr');

    for (var i = 1; i < tr.length; i++) {
      var tdFirma = tr[i].getElementsByTagName('td')[0];
      var tdPersonel1 = tr[i].getElementsByTagName('td')[1];
      var tdPersonel2 = tr[i].getElementsByTagName('td')[2];
      var tdArama = tr[i].getElementsByTagName('td')[4];
      var tdZiyaret = tr[i].getElementsByTagName('td')[5];
      var tdSonuc = tr[i].getElementsByTagName('td')[6];
      var tdTekrar = tr[i].getElementsByTagName('td')[7];

      if (tdFirma && tdPersonel1 && tdPersonel2 && tdArama && tdZiyaret && tdSonuc && tdTekrar) {
        var firma = tdFirma.textContent || tdFirma.innerText;
        var personel1 = tdPersonel1.textContent || tdPersonel1.innerText;
        var personel2 = tdPersonel2.textContent || tdPersonel2.innerText;
        var arama = tdArama.textContent || tdArama.innerText;
        var ziyaret = tdZiyaret.textContent || tdZiyaret.innerText;
        var sonuc = tdSonuc.textContent || tdSonuc.innerText;
        var tekrar = tdTekrar.textContent || tdTekrar.innerText;

        if (
          firma.toUpperCase().indexOf(firmaFiltre) > -1 &&
          personel1.toUpperCase().indexOf(personel1Filtre) > -1 &&
          personel2.toUpperCase().indexOf(personel2Filtre) > -1 &&
          arama.toUpperCase().indexOf(aramaFiltre) > -1 &&
          ziyaret.toUpperCase().indexOf(ziyaretFiltre) > -1 &&
          sonuc.toUpperCase().indexOf(sonucFiltre) > -1 &&
          tekrar.toUpperCase().indexOf(tekrarFiltre) > -1
        ) {
          tr[i].style.display = '';
        } else {
          tr[i].style.display = 'none';
        }
      }
    }
  }

  function tarihFiltrele() {
    var baslangicTarih = new Date(document.getElementById('baslangicTarih').value);
    var bitisTarih = new Date(document.getElementById('bitisTarih').value);
    var table = document.getElementById('firmalarTablosu');
    var tr = table.getElementsByTagName('tr');

    for (var i = 1; i < tr.length; i++) {
      var td = tr[i].getElementsByTagName('td')[3]; // Tarih sütunu
      if (td) {
        var tarih = new Date(td.innerText.split('.').reverse().join('-')); // Tarih formatı: dd.mm.yyyy
        if (tarih >= baslangicTarih && tarih <= bitisTarih) {
          tr[i].style.display = '';
        } else {
          tr[i].style.display = 'none';
        }
      }
    }
  }
});
</script>


<?php 
require_once 'footer.php';
?>
