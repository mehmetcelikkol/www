
<?php 
include "header.php";
include "sidebar.php";
include "navbar.php";
include "conn.php"; // Veritabanı bağlantısını dahil et


// Veritabanı bağlantısının başarılı olup olmadığını kontrol et
if ($conn->connect_error) {
  die("Bağlantı Hatası: " . $conn->connect_error);
}

//    echo "Veritabanı bağlantısı başarılı.<br>";

// Verileri veritabanından çek
$sql = "SELECT id, serino, temp, hum, DATE(kayit_tarihi) AS tarih, TIME(kayit_tarihi) AS saat FROM veriler";
$result = $conn->query($sql);

// Sorgunun başarılı olup olmadığını kontrol et
if (!$result) {
  die("Sorgu Hatası: " . $conn->error);
}

//    echo "Veriler başarıyla çekildi.<br>";
?>


</div>


<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <div class="card">
        <div class="card-header">
          <div class="card-title">Responsive Table</div>
        </div>
        <div class="card-body">
                    <!--
                    <div class="card-sub">
                      Create responsive tables by wrapping any table with
                      <code class="highlighter-rouge">.table-responsive</code>
                      <code class="highlighter-rouge">DIV</code> to make them
                      scroll horizontally on small devices
                    </div>
                  -->
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">Tarih</th>
                          <th scope="col">Saat</th>
                          <th scope="col">Cihaz Kimliği</th>
                          <th scope="col">Sıcaklık</th>
                          <th scope="col">Nem</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                          $counter = 0;
              // Veritabanındaki her satır için tabloya veri ekleme
                          while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td>" . $row["tarih"] . "</td>";
                            echo "<td>" . $row["saat"] . "</td>";
                            echo "<td>" . $row["serino"] . "</td>";
                            echo "<td>" . $row["temp"] . "</td>";
                            echo "<td>" . $row["hum"] . "</td>";
                            echo "</tr>";

                            $counter++;
               //   if ($counter >= 15) break; // Sadece 15 satır göster
                          }
                        } else {
                          echo "<tr><td colspan='6'>Hiç veri bulunamadı.</td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>


        </div>

        <?php include "footer.php" ?>