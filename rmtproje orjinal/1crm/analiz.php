<?php 
require_once 'header.php'; 
?>

<div class="container-fluid">
  <div class="row">
    <h2>Satışçı Verimlilik Analizi</h2>
    <div class="col-md-12">
      <h4>Genel Performans</h4>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Satışçı</th>
            <th>Günlük Ziyaret</th>
            <th>Haftalık Ziyaret</th>
            <th>Aylık Ziyaret</th>
            <th>Yıllık Ziyaret</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql = "
          SELECT e.isim AS satisci_isim, 
          COUNT(h.id) AS toplam_ziyaret,
          SUM(CASE WHEN h.tarih >= CURDATE() THEN 1 ELSE 0 END) AS bugun_ziyaret,
          SUM(CASE WHEN YEARWEEK(h.tarih, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS hafta_ziyaret,
          SUM(CASE WHEN MONTH(h.tarih) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS ay_ziyaret,
          SUM(CASE WHEN YEAR(h.tarih) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS yil_ziyaret
          FROM ekip e 
          LEFT JOIN hareket h ON h.ekipid1 = e.id 
          GROUP BY e.isim
          ";

          $stmt = $pdo->prepare($sql);
          $stmt->execute();
          $salespersons = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $totalRegisteredDaysWeek = 0;
          $totalRegisteredDaysMonth = 0;
          $totalRegisteredDaysYear = 0;

          foreach ($salespersons as $salesperson) {
            echo "<tr>";
            echo "<td>{$salesperson['satisci_isim']}</td>";
            echo "<td>{$salesperson['bugun_ziyaret']}</td>";
            echo "<td>{$salesperson['hafta_ziyaret']}</td>";
            echo "<td>{$salesperson['ay_ziyaret']}</td>";
            echo "<td>{$salesperson['yil_ziyaret']}</td>";
            echo "</tr>";

            if ($salesperson['hafta_ziyaret'] > 0) $totalRegisteredDaysWeek++;
            if ($salesperson['ay_ziyaret'] > 0) $totalRegisteredDaysMonth++;
            if ($salesperson['yil_ziyaret'] > 0) $totalRegisteredDaysYear++;
          }

          $totalDaysInWeek = 7;
          $totalDaysInMonth = date('t');
          $totalDaysInYear = date('L') ? 366 : 365;

          $emptyDaysWeek = $totalDaysInWeek - $totalRegisteredDaysWeek;
          $emptyDaysMonth = $totalDaysInMonth - $totalRegisteredDaysMonth;
          $emptyDaysYear = $totalDaysInYear - $totalRegisteredDaysYear;

          if ($emptyDaysWeek > 0) {
            echo "<tr><td>Boş Geçenler (Hafta)</td><td>0</td><td>{$emptyDaysWeek}</td><td>0</td><td>0</td></tr>";
          }
          if ($emptyDaysMonth > 0) {
            echo "<tr><td>Boş Geçenler (Ay)</td><td>0</td><td>0</td><td>{$emptyDaysMonth}</td><td>0</td></tr>";
          }
          if ($emptyDaysYear > 0) {
            echo "<tr><td>Boş Geçenler (Yıl)</td><td>0</td><td>0</td><td>0</td><td>{$emptyDaysYear}</td></tr>";
          }

          // Haftalık veriler
          $weeklyData = "
              SELECT 
                  DAYOFWEEK(h.tarih) as gun,
                  COUNT(h.id) as ziyaret_sayisi
              FROM hareket h
              WHERE YEARWEEK(h.tarih, 1) = YEARWEEK(CURDATE(), 1)
              GROUP BY DAYOFWEEK(h.tarih)
              ORDER BY DAYOFWEEK(h.tarih)
          ";
          $stmt = $pdo->prepare($weeklyData);
          $stmt->execute();
          $weeklyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $ziyaretler = array_fill(0, 7, 0);
          foreach ($weeklyStats as $stat) {
              $index = $stat['gun'] - 1;
              $ziyaretler[$index] = (int)$stat['ziyaret_sayisi'];
          }

          // Aylık veriler
          $monthlyData = "
              SELECT 
                  DAY(h.tarih) as gun,
                  COUNT(h.id) as ziyaret_sayisi
              FROM hareket h
              WHERE MONTH(h.tarih) = MONTH(CURDATE()) 
              AND YEAR(h.tarih) = YEAR(CURDATE())
              GROUP BY DAY(h.tarih)
              ORDER BY DAY(h.tarih)
          ";
          $stmt = $pdo->prepare($monthlyData);
          $stmt->execute();
          $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $aylikZiyaretler = array_fill(0, date('t'), 0);
          foreach ($monthlyStats as $stat) {
              $index = $stat['gun'] - 1;
              $aylikZiyaretler[$index] = (int)$stat['ziyaret_sayisi'];
          }

          // Yıllık veriler
          $yearlyData = "
              SELECT 
                  MONTH(h.tarih) as ay,
                  COUNT(h.id) as ziyaret_sayisi
              FROM hareket h
              WHERE YEAR(h.tarih) = YEAR(CURDATE())
              GROUP BY MONTH(h.tarih)
              ORDER BY MONTH(h.tarih)
          ";
          $stmt = $pdo->prepare($yearlyData);
          $stmt->execute();
          $yearlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

          $yillikZiyaretler = array_fill(0, 12, 0);
          foreach ($yearlyStats as $stat) {
              $index = $stat['ay'] - 1;
              $yillikZiyaretler[$index] = (int)$stat['ziyaret_sayisi'];
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Grafikler -->
    <div class="col-md-12">
      <div class="row">
        <div class="col-md-4">
          <h4>Haftalık Ziyaret Grafiği</h4>
          <canvas id="verimlilikGrafik"></canvas>
        </div>
        <div class="col-md-4">
          <h4>Aylık Ziyaret Grafiği</h4>
          <canvas id="aylikGrafik"></canvas>
        </div>
        <div class="col-md-4">
          <h4>Yıllık Ziyaret Grafiği</h4>
          <canvas id="yillikGrafik"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

  // Veri kontrolü için yardımcı fonksiyon
function isDataEmpty(data) {
    return data.every(item => item === 0);
}

// Plugin tanımı
const noDataPlugin = {
    id: 'noData',
    afterDraw: (chart) => {
        if (isDataEmpty(chart.data.datasets[0].data)) {
            const {ctx, width, height} = chart;
            chart.clear();
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 16px Arial';
            ctx.fillStyle = chart.data.datasets[0].borderColor;
            ctx.fillText('Veri Yok', width / 2, height / 2);
            ctx.restore();
        }
    }
};

// Haftalık grafik
var ctx = document.getElementById('verimlilikGrafik').getContext('2d');
var verimlilikGrafik = new Chart(ctx, {
    plugins: [noDataPlugin],
    type: 'bar',
    data: {
        labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'],
        datasets: [{
            label: 'Günlük Ziyaretler',
            data: <?php echo json_encode($ziyaretler); ?>,
            backgroundColor: 'rgba(25, 255, 62, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Aylık grafik
var ctxAylik = document.getElementById('aylikGrafik').getContext('2d');
var aylikGrafik = new Chart(ctxAylik, {
    plugins: [noDataPlugin],
    type: 'bar',
    data: {
        labels: Array.from({length: <?php echo date('t'); ?>}, (_, i) => i + 1),
        datasets: [{
            label: 'Aylık Ziyaretler',
            data: <?php echo json_encode($aylikZiyaretler); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Yıllık grafik
var ctxYillik = document.getElementById('yillikGrafik').getContext('2d');
var yillikGrafik = new Chart(ctxYillik, {
    plugins: [noDataPlugin],
    type: 'bar',
    data: {
        labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
        datasets: [{
            label: 'Yıllık Ziyaretler',
            data: <?php echo json_encode($yillikZiyaretler); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

</script>

<?php 
require_once 'footer.php'; 
?>
