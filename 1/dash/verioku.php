<?php

include "header.php";
include "conn.php";
include "oturum.php";
include "sidebar.php";
include "navbar.php";

// GET parametresi ile gelen seri numarasını al
$serino = isset($_GET['serino']) ? $_GET['serino'] : '';

// GET parametresi ile gelen zaman aralığını al
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Filtreleme tarihlerini belirleme
$now = date('Y-m-d H:i:s');
$startDate = '';

switch ($filter) {
    case 'bugun':
    $startDate = date('Y-m-d 00:00:00');
    break;
    case 'hafta':
    $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
    break;
    case 'ay':
    $startDate = date('Y-m-01 00:00:00');
    break;
    case 'yil':
    $startDate = date('Y-01-01 00:00:00');
    break;
    case 'son24':
    $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
    break;
    case 'son7':
    $startDate = date('Y-m-d H:i:s', strtotime('-7 days'));
    break;
    case 'son30':
    $startDate = date('Y-m-d H:i:s', strtotime('-30 days'));
    break;
    case 'son365':
    $startDate = date('Y-m-d H:i:s', strtotime('-365 days'));
    break;
    case 'son10':
    case 'son50':
        $startDate = ''; // En son 10 veya 50 kayıt için özel sorgu yapacağız
        break;
        default:
        $startDate = ''; // Filtre uygulanmaz
        break;
    }

// Veritabanı sorguları
    $sqlTable = "SELECT * FROM veriler";
    $sqlGraph = "SELECT * FROM veriler";

// Eğer bir seri numarası varsa, filtre ekleyin
    if (!empty($serino)) {
        $sqlTable .= " WHERE serino = '" . $conn->real_escape_string($serino) . "'";
        $sqlGraph .= " WHERE serino = '" . $conn->real_escape_string($serino) . "'";
    }

// Tarih filtresi uygula
    if (!empty($startDate)) {
        $sqlTable .= !empty($serino) ? " AND" : " WHERE";
        $sqlTable .= " kayit_tarihi >= '$startDate'";
        
        $sqlGraph .= !empty($serino) ? " AND" : " WHERE";
        $sqlGraph .= " kayit_tarihi >= '$startDate'";
    }

// Son 10 veya 50 kayıt için LIMIT ekle
    if ($filter === 'son10') {
        $sqlTable .= " ORDER BY kayit_tarihi DESC LIMIT 10";
        $sqlGraph .= " ORDER BY id ASC LIMIT 10";
    } elseif ($filter === 'son50') {
        $sqlTable .= " ORDER BY kayit_tarihi DESC LIMIT 50";
        $sqlGraph .= " ORDER BY id ASC LIMIT 50";
    } else {
        $sqlTable .= " ORDER BY kayit_tarihi DESC";
        $sqlGraph .= " ORDER BY id ASC";
    }

    $resultTable = $conn->query($sqlTable);
    $resultGraph = $conn->query($sqlGraph);

// Grafik verilerini hazırlama
    $data = [];
    while ($row = $resultGraph->fetch_assoc()) {
        $data[] = $row;
    }

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Veri Görüntüleme</title>
        <meta http-equiv="refresh" content="60"> 
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table, th, td {
                border: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .chart-container {
                width: 100%;
                height: 400px;
            }
            body, html {
                height: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }
            .scroll-container {
                height: calc(100vh - 60px); /* Footer yüksekliğini çıkarın */
                overflow-y: auto;
                padding: 20px;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <div class="scroll-container">
            <div class="card-body" align="center">
                <p class="demo">
                    <button class="btn btn-primary btn-round" onclick="filterData('bugun')">Bugün</button>
                    <button class="btn btn-primary btn-round" onclick="filterData('hafta')">Bu Hafta</button>
                    <button class="btn btn-primary btn-round" onclick="filterData('ay')">Bu Ay</button>
                    <button class="btn btn-primary btn-round" onclick="filterData('yil')">Bu Yıl</button>
                    <button class="btn btn-primary btn-border btn-round" onclick="filterData('son24')">Son 24 Saat</button>
                    <button class="btn btn-primary btn-border btn-round" onclick="filterData('son7')">Son 7 Gün</button>
                    <button class="btn btn-primary btn-border btn-round" onclick="filterData('son30')">Son 30 Gün</button>
                    <button class="btn btn-primary btn-border btn-round" onclick="filterData('son365')">Son 365 Gün</button>
                    <button class="btn btn-success btn-round" onclick="filterData('son10')">Son 10 Kayıt</button>
                    <button class="btn btn-success btn-round" onclick="filterData('son50')">Son 50 Kayıt</button>
                </p>
            </div>

            <div class="card-body" align="center">
                <div class="card-header"></div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
            <br>

            <div class="card-body">
                <!-- Tablo -->
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Serino</th>
                        <th>ISI</th>
                        <th>Nem</th>
                        <th>WiFi</th>
                        <th>Ver</th>
                        <th>Oturum</th>
                        <th>Kod1</th>
                        <th>Kayıt Tarihi</th>
                    </tr>

                    <?php
                    if ($resultTable->num_rows > 0) {
                        while($row = $resultTable->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td>" . $row["serino"] . "</td>";
                            echo "<td>" . $row["temp"] . "</td>";
                            echo "<td>" . $row["hum"] . "</td>";
                            echo "<td>" . $row["wifi"] . "</td>";
                            echo "<td>" . $row["versiyon"] . "</td>";
                            echo "<td>" . $row["oturum"] . "</td>";
                            echo "<td>" . $row["kod1dk"] . "</td>";
                            echo "<td>" . $row["kayit_tarihi"] . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>Herhangi bir veri yok!</td></tr>";
                    }
                    ?>
                </table>
            </div>

            <script>
                const data = <?php echo json_encode($data); ?>;
                
    // Tarih formatlama fonksiyonu (GG-AA SS:DD)
                function formatDate(dateString) {
                    const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');  // Gün
        const month = String(date.getMonth() + 1).padStart(2, '0');  // Ay (0-11 olduğu için +1)
        const hours = String(date.getHours()).padStart(2, '0');  // Saat
        const minutes = String(date.getMinutes()).padStart(2, '0');  // Dakika

        return `${day}-${month} ${hours}:${minutes}`;  // GG-AA SS:DD formatı
    }

    // Tarih etiketlerini formatlayarak al
    const labels = data.map(row => formatDate(row.kayit_tarihi));
    const tempData = data.map(row => parseFloat(row.temp));
    const humData = data.map(row => parseFloat(row.hum));

    const ctx = document.getElementById('lineChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
            {
                label: 'Sıcaklık (°C)',
                data: tempData,
                borderColor: '#ff0000',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 1,
                fill: true
            },
            {
                label: 'Nem (%)',
                data: humData,
                borderColor: '#0000ff',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderWidth: 1,
                fill: true
            }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'category',  // X ekseni kategori tipi olarak tanımlanıyor
                    ticks: {
                        maxRotation: 90,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    function filterData(filter) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('filter', filter);
        window.location.search = urlParams.toString();
    }
</script>


</div>
</body>
</html>


<?php include "footer.php"; ?>