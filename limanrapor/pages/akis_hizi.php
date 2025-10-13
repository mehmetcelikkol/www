<?php
// filepath: c:\wamp64\www\limanrapor\pages\akis_hizi.php
// Akış Hızı Sayfası - Gemi Boşaltma Grafiği

// Gemi boşaltma verilerini çek
$data = [];
$error_message = null;

if ($pdo) {
    try {
        $sql = "SELECT * FROM flowveri 
                WHERE sensor_adi IN ('gflow1', 'gflow2') 
                AND okuma_zamani >= :start_date AND okuma_zamani <= :end_date 
                ORDER BY okuma_zamani DESC LIMIT 1000";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error_message = "Veri çekme hatası: " . $e->getMessage();
    }
}
?>

<?php if ($error_message): ?>
    <div class="error">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<!-- Grafik Bölümü -->
<?php if (!empty($data)): ?>
<div class="chart-section">
    <div class="chart-header">
        <h3>📈 Debi Grafiği (Ton/h)</h3>
    </div>
    <div class="chart-container">
        <canvas id="flowChart"></canvas>
        <div id="chartStatus" style="text-align: center; margin-top: 1rem; color: #666;"></div>
    </div>
</div>
<?php endif; ?>

<!-- Veri Tablosu -->
<div class="data-section">
    <div class="data-header">
        <h3>🚢 Gemi Boşaltma - Akış Hızı Verileri</h3>
        <div class="header-actions">
            <span class="data-count"><?= count($data) ?> kayıt</span>
            <button class="export-btn" onclick="window.print()">📄 Yazdır</button>
        </div>
    </div>
    
    <?php if (!empty($data)): ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rıhtım</th>
                    <th>Zaman</th>
                    <th>Sıcaklık (°C)</th>
                    <th>Debi (T/h)</th>
                    <th>Yoğunluk (kg/L)</th>
                    <th>Operasyon Toplam (Ton)</th>
                    <th>Toplam (Ton)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <?php 
                    // Sensör adını rıhtım ismiyle değiştir
                    $rihtim_adi = '';
                    if ($row['sensor_adi'] === 'gflow1') {
                        $rihtim_adi = 'Rıhtım 7';
                    } elseif ($row['sensor_adi'] === 'gflow2') {
                        $rihtim_adi = 'Rıhtım 8';
                    } else {
                        $rihtim_adi = $row['sensor_adi'] ?? '';
                    }
                    ?>
                    <tr>
                        <td class="sensor-name"><?= htmlspecialchars($rihtim_adi) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['okuma_zamani'] ?? ''))) ?></td>
                        <td class="amount"><?= number_format($row['sicaklik'] ?? 0, 1, ',', '.') ?></td>
                        <td class="amount" style="font-weight: bold; color: #c53030;"><?= number_format($row['debi'] ?? 0, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['yogunluk'] ?? 0, 3, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['operasyon_toplam'] ?? 0, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['toplam'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>Veri bulunamadı</h3>
        <p>Seçilen tarih aralığında akış hızı verisi bulunamadı.</p>
        <small>Tarih aralığı: <?= $start_date ?> - <?= $end_date ?></small>
    </div>
    <?php endif; ?>
</div>

<script>
// Grafik İşlevleri
let flowChart = null;

// PHP verilerini JavaScript'e aktar
<?php if (!empty($data)): ?>
const flowData = <?= json_encode($data) ?>;

// Veriyi işle ve grafik için hazırla
function prepareChartData() {
    const gflow1Data = [];
    const gflow2Data = [];
    
    flowData.forEach(row => {
        const timestamp = row.okuma_zamani;
        const debi_ton_h = parseFloat(row.debi) || 0;
        
        const dataPoint = {
            x: timestamp,
            y: debi_ton_h
        };
        
        if (row.sensor_adi === 'gflow1') {
            gflow1Data.push(dataPoint);
        } else if (row.sensor_adi === 'gflow2') {
            gflow2Data.push(dataPoint);
        }
    });
    
    // Zamana göre sırala
    gflow1Data.sort((a, b) => new Date(a.x) - new Date(b.x));
    gflow2Data.sort((a, b) => new Date(a.x) - new Date(b.x));
    
    return { gflow1Data, gflow2Data };
}

function initChart() {
    const ctx = document.getElementById('flowChart');
    if (!ctx) return;
    
    const { gflow1Data, gflow2Data } = prepareChartData();
    
    if (flowChart) {
        flowChart.destroy();
    }
    
    flowChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Rıhtım 7 (gflow1)',
                    data: gflow1Data,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: false,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2
                },
                {
                    label: 'Rıhtım 8 (gflow2)',
                    data: gflow2Data,
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4,
                    fill: false,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'minute',
                        displayFormats: {
                            minute: 'HH:mm',
                            hour: 'HH:mm'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Zaman'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Debi (Ton/h)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(context) {
                            return new Date(context[0].raw.x).toLocaleString('tr-TR');
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + 
                                   context.parsed.y.toFixed(2) + ' Ton/h';
                        }
                    }
                }
            }
        }
    });
}

// Sayfa yüklendiğinde grafiği başlat
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('flowChart')) {
        initChart();
    }
});
<?php else: ?>
console.log('Grafik verisi yok.');
<?php endif; ?>
</script>