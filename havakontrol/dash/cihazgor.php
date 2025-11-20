<?php
// Hata raporlamayı ve bellek/zaman sınırlarını ayarla
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M'); // Bellek sınırını artırır
ini_set('max_execution_time', 300); // Maksimum yürütme süresini 300 saniyeye çıkarır

include "header.php";
include "sidebar.php";
include "navbar.php";
include "conn.php"; // Veritabanı bağlantısını dahil et

// Veritabanı bağlantısının başarılı olup olmadığını kontrol et
if ($conn->connect_error) {
  die("Bağlantı Hatası: " . $conn->connect_error);
}

// Sayfalama ayarları
$limit = 25; // Sayfa başına gösterilecek satır sayısı
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Mevcut sayfa numarasını al
$page = $page < 1 ? 1 : $page; // Sayfa numarası 1'den küçük olamaz
$start = ($page - 1) * $limit; // Başlangıç satırını hesapla

// Filtreler
$tarih_baslangic = isset($_GET['tarih_baslangic']) ? $_GET['tarih_baslangic'] : '';
$tarih_bitis = isset($_GET['tarih_bitis']) ? $_GET['tarih_bitis'] : '';

// Tarih aralığı hesapla
if (isset($_GET['filter'])) {
  $today = date('Y-m-d');
  switch ($_GET['filter']) {
    case 'today':
    $tarih_baslangic = $today;
    $tarih_bitis = $today;
    break;
    case 'week':
    $tarih_baslangic = date('Y-m-d', strtotime('monday this week'));
    $tarih_bitis = date('Y-m-d', strtotime('sunday this week'));
    break;
    case 'month':
    $tarih_baslangic = date('Y-m-01');
    $tarih_bitis = date('Y-m-t');
    break;
    case 'year':
    $tarih_baslangic = date('Y-01-01');
    $tarih_bitis = date('Y-12-31');
    break;
    default:
    $tarih_baslangic = '';
    $tarih_bitis = '';
    break;
  }
}

// SQL sorgusunu oluştur
$sql = "SELECT id, serino, temp, hum, DATE_FORMAT(kayit_tarihi, '%d.%m.%Y') AS tarih, TIME(kayit_tarihi) AS saat FROM veriler";

// Filtreleri sorguya ekle
$filters = [];
if ($tarih_baslangic && $tarih_bitis) {
  $filters[] = "DATE(kayit_tarihi) BETWEEN '$tarih_baslangic' AND '$tarih_bitis'";
}
if (count($filters) > 0) {
  $sql .= " WHERE " . implode(' AND ', $filters);
}

$sql .= " LIMIT $start, $limit";

// Toplam satır sayısını al
$sql_count = "SELECT COUNT(*) AS total FROM veriler";
if (count($filters) > 0) {
  $sql_count .= " WHERE " . implode(' AND ', $filters);
}
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_rows = $row_count['total'];
$total_pages = ceil($total_rows / $limit); // Toplam sayfa sayısını hesapla

// Verileri veritabanından çek
$result = $conn->query($sql);

// Sorgunun başarılı olup olmadığını kontrol et
if (!$result) {
  die("Sorgu Hatası: " . $conn->error);
}
?>
</div>

<div class="container-fluid">
  <h1 class="my-4">Cihaz Verileri</h1>
  <div class="card-header">
        <div class="card-title">Tüm Cihazları Gör</div>
      </div>
  <div class="row">
    <!-- Tablo Bölümü (sol) -->
    <div class="col-md-8">
      <!--
      <div class="card-header">
        <div class="card-title">Tüm Cihazları Gör</div>
      </div>
-->
      <table class="table table-bordered table-head-bg-info table-bordered-bd-info mt-4">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Tarih</th>
            <th scope="col">Saat</th>
            <th scope="col">Kimlik</th>
            <th scope="col">Sıcaklık</th>
            <th scope="col">Nem</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . $row["id"] . "</td>";
              echo "<td>" . $row["tarih"] . "</td>";
              echo "<td>" . $row["saat"] . "</td>";
              echo "<td>" . $row["serino"] . "</td>";
              echo "<td>" . $row["temp"] . "</td>";
              echo "<td>" . $row["hum"] . "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='6'>Hiç veri bulunamadı.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Sağ Bölüm -->
    <div class="col-md-4">
      <!-- Filtreleme Formu -->
      <div class="card card-secondary card-round mb-4">
        <div class="card-header">
          <div class="card-title">Filtreleme</div>
        </div>
        <div class="card-body">
          <form method="get" action="">
            <div class="form-group">
              <label for="tarih_baslangic">Başlangıç:</label>
              <input type="date" id="tarih_baslangic" name="tarih_baslangic" value="<?php echo htmlspecialchars($tarih_baslangic); ?>">
            </div>
            <div class="form-group">
              <label for="tarih_bitis">Bitiş:</label>
              <input type="date" id="tarih_bitis" name="tarih_bitis" value="<?php echo htmlspecialchars($tarih_bitis); ?>">
            </div>
            <button type="submit">Filtrele</button>
            <input type="hidden" name="page" value="<?php echo $page; ?>">
          </form>
          <div class="filters mt-3">
            <a href="?filter=today&page=<?php echo $page; ?>" class="btn btn-info btn-round">Bugün</a>
            <a href="?filter=week&page=<?php echo $page; ?>" class="btn btn-info btn-round">Bu Hafta</a>
            <a href="?filter=month&page=<?php echo $page; ?>" class="btn btn-info btn-round">Bu Ay</a>
            <a href="?filter=year&page=<?php echo $page; ?>" class="btn btn-info btn-round">Bu Yıl</a>
          </div>
        </div>
      </div>

      <!-- Grafik Bölümü -->
      <div class="col-md-12">
  <div class="card card-primary">
    <div class="card-header">
      <div class="card-head-row">
        <div class="card-title">Daily Sales</div>
        <div class="card-tools">
          <div class="dropdown">
            <button
            class="btn btn-sm btn-label-light dropdown-toggle"
            type="button"
            id="dropdownMenuButton"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            >
            Export
          </button>
          <div
          class="dropdown-menu"
          aria-labelledby="dropdownMenuButton"
          >
          <a class="dropdown-item" href="#">Action</a>
          <a class="dropdown-item" href="#">Another action</a>
          <a class="dropdown-item" href="#"
          >Something else here</a
          >
        </div>
      </div>
    </div>
  </div>
  <div class="card-category">March 25 - April 02</div>
</div>
<div class="card-body pb-0">
  <div class="mb-4 mt-2">
    <h1>$4,578.58</h1>
  </div>
  <div class="pull-in">
    <canvas id="dailySalesChart"></canvas>
  </div>
</div>
</div>
    </div>
  </div>

  <!-- Sayfalama -->
  <div class="pagination">
    <form method="get" action="">
      <button type="submit" name="page" value="<?php echo max(1, $page - 1); ?>">Önceki</button>

      <select name="page" onchange="this.form.submit()">
        <?php
        for ($i = 1; $i <= $total_pages; $i++) {
          echo '<option value="' . $i . '"' . ($i == $page ? ' selected' : '') . '>Sayfa ' . $i . '</option>';
        }
        ?>
      </select>

      <button type="submit" name="page" value="<?php echo min($total_pages, $page + 1); ?>">Sonraki</button>
    </form>
  </div>
</div>

<?php include "footer.php"; ?>
