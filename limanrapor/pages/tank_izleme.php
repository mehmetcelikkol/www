<?php
// filepath: c:\wamp64\www\limanrapor\pages\tank_izleme.php
// Tank İzleme Sayfası - AJAX ile Dinamik Grafik Yükleme

$tank_latest_data = [];
$available_tanks = [];
$error_message = null;

try {
    // Sadece anlık durumlar için hızlı bir sorgu
    $tank_list_stmt = $pdo->query("SELECT DISTINCT tank FROM tank_verileri ORDER BY tank ASC");
    $available_tanks = $tank_list_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($available_tanks)) {
        $placeholders = implode(',', array_fill(0, count($available_tanks), '?'));
        $latest_sql = "
            SELECT t1.* FROM tank_verileri t1
            INNER JOIN (
                SELECT tank, MAX(tarihsaat) AS max_ts FROM tank_verileri WHERE tank IN ($placeholders) GROUP BY tank
            ) t2 ON t1.tank = t2.tank AND t1.tarihsaat = t2.max_ts
        ";
        $latest_stmt = $pdo->prepare($latest_sql);
        $latest_stmt->execute($available_tanks);
        foreach ($latest_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tank_latest_data[$row['tank']] = $row;
        }
    }
} catch(PDOException $e) {
    $error_message = "Veri çekme hatası: " . $e->getMessage();
}

// Dinamik tarih butonları için ay isimleri
$months = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
?>

<!-- Tank Dashboard -->
<?php if (!empty($tank_latest_data)): ?>
<div class="tank-dashboard">
    <div class="tank-dashboard-header"><h3>🛢️ Tank Durumu - Anlık Değerler</h3></div>
    <div class="tanks-container">
        <?php foreach ($available_tanks as $tank_num): ?>
            <?php if (isset($tank_latest_data[$tank_num])): 
                $tank_data = $tank_latest_data[$tank_num];
                $radar_cm = ($tank_data['rdr'] ?? 0) / 10;
                $basinc_bar = ($tank_data['bsnc'] ?? 0) / 100;
                $sicaklik = ($tank_data['pt100'] ?? 0) / 10;
            ?>
            <div class="tank-display" data-tank="<?= $tank_num ?>" style="cursor: pointer;">
                <div class="tank-title">Tank <?= $tank_num ?></div>
                <img src="img/tank.png" alt="Tank <?= $tank_num ?>" class="tank-image">
                <div class="tank-values">
                    <div class="tank-value"><div class="tank-value-label">Radar (cm)</div><div class="tank-value-data"><?= number_format($radar_cm, 1, ',', '.') ?> cm</div></div>
                    <div class="tank-value"><div class="tank-value-label">Basınç (bar)</div><div class="tank-value-data"><?= number_format($basinc_bar, 2, ',', '.') ?> bar</div></div>
                    <div class="tank-value"><div class="tank-value-label">Sıcaklık</div><div class="tank-value-data"><?= number_format($sicaklik, 2, ',', '.') ?> °C</div></div>
                    <div class="tank-value tank-timestamp-value"><div class="tank-value-label">Son Güncelleme</div><div class="tank-value-data"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($tank_data['tarihsaat'] ?? ''))) ?></div></div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tarih Filtre Butonları (Başlangıçta Gizli) -->
<div id="time-filters" class="data-section" style="display:none; margin-top: 1.5rem;">
    <div class="data-header">
        <h3 id="filter-title">Tarih Aralığı Seçin</h3>
    </div>
    <div class="filter-buttons">
        <button class="filter-btn" data-range="last_7_days">Son 7 Gün</button>
        <button class="filter-btn" data-range="this_week">Bu Hafta</button>
        <button class="filter-btn" data-range="this_month">Bu Ay</button>
        <button class="filter-btn" data-range="last_month">Geçen Ay</button>
        <button class="filter-btn" data-range="this_year">Bu Yıl</button>
    </div>
    <div class="filter-buttons" style="margin-top: 10px;">
        <?php foreach ($months as $i => $month): ?>
            <button class="filter-btn" data-range="month_<?= $i + 1 ?>"><?= $month ?></button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tank Grafikleri Alanı -->
<div class="data-section" id="tank-chart-section" style="margin-top: 1.5rem; display: none;">
    <div id="chartContainer">
        <div id="chartRadar" style="width: 100%; height: 350px;"></div>
        <div id="chartBasinc" style="width: 100%; height: 350px; margin-top: 20px;"></div>
        <div id="chartSicaklik" style="width: 100%; height: 350px; margin-top: 20px;"></div>
    </div>
    <div id="chart-status" class="empty-state" style="padding: 40px 20px;"></div>
</div>

<!-- Yerel ECharts Kütüphanesi ve Grafik Kodları -->
<script src="assets/js/echarts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const timeFilters = document.getElementById('time-filters');
    const chartSection = document.getElementById('tank-chart-section');
    const chartContainer = document.getElementById('chartContainer');
    const chartStatus = document.getElementById('chart-status');
    const filterTitle = document.getElementById('filter-title');
    let activeTankId = null;

    if (typeof echarts === 'undefined') {
        chartStatus.innerHTML = '<h3>Grafik Kütüphanesi Yüklenemedi</h3><p>Lütfen <strong>assets/js/echarts.min.js</strong> dosyasının doğru yerde olduğundan emin olun.</p>';
        chartSection.style.display = 'block';
        return;
    }

    const radarChart = echarts.init(document.getElementById('chartRadar'));
    const basincChart = echarts.init(document.getElementById('chartBasinc'));
    const sicaklikChart = echarts.init(document.getElementById('chartSicaklik'));
    echarts.connect([radarChart, basincChart, sicaklikChart]);

    const baseChartOptions = {
        tooltip: { trigger: 'axis' },
        grid: { left: '50px', right: '20px', bottom: '80px', containLabel: true },
        xAxis: { type: 'category', boundaryGap: false },
        yAxis: { type: 'value' },
        series: [{ type: 'line', smooth: true, areaStyle: {} }],
        dataZoom: [{ type: 'slider', start: 0, end: 100, bottom: 10 }]
    };

    function renderCharts(data) {
        chartContainer.style.display = 'block';
        chartStatus.style.display = 'none';
        
        // --- ÇÖZÜM BURADA ---
        // Grafiklere yeni boyutlarını bildiriyoruz.
        radarChart.resize();
        basincChart.resize();
        sicaklikChart.resize();
        // --- ÇÖZÜM SONU ---

        radarChart.setOption({ ...baseChartOptions, title: { text: `Tank ${activeTankId} - Radar (cm)` }, xAxis: { data: data.time }, yAxis: { axisLabel: { formatter: '{value} cm' } }, series: [{ ...baseChartOptions.series[0], data: data.radar_cm }] });
        basincChart.setOption({ ...baseChartOptions, title: { text: `Tank ${activeTankId} - Basınç (bar)` }, xAxis: { data: data.time }, yAxis: { axisLabel: { formatter: '{value} bar' } }, series: [{ ...baseChartOptions.series[0], data: data.basinc_bar }] });
        sicaklikChart.setOption({ ...baseChartOptions, title: { text: `Tank ${activeTankId} - Sıcaklık (°C)` }, xAxis: { data: data.time }, yAxis: { axisLabel: { formatter: '{value} °C' } }, series: [{ ...baseChartOptions.series[0], data: data.sicaklik }] });
    }

    function showStatus(message, isError = false) {
        chartContainer.style.display = 'none';
        chartStatus.style.display = 'block';
        chartStatus.innerHTML = isError ? `<h3 style="color:#c53030;">Hata</h3><p>${message}</p>` : `<h3>Bilgi</h3><p>${message}</p>`;
    }

    // 1. Tank kartına tıklandığında
    document.querySelectorAll('.tank-display').forEach(card => {
        card.addEventListener('click', () => {
            activeTankId = card.getAttribute('data-tank');
            
            document.querySelectorAll('.tank-display').forEach(c => c.style.outline = 'none');
            card.style.outline = '3px solid #3b82f6';
            
            filterTitle.innerText = `Tank ${activeTankId} için Tarih Aralığı Seçin`;
            timeFilters.style.display = 'block';
            chartSection.style.display = 'none'; // Grafikleri gizle, yeni tarih seçimi bekleniyor
        });
    });

    // 2. Tarih filtresi butonuna tıklandığında
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const range = button.getAttribute('data-range');
            if (!activeTankId) return;

            chartSection.style.display = 'block';
            showStatus('Grafik verileri yükleniyor, lütfen bekleyin...');

            try {
                const response = await fetch(`api/get_tank_data.php?tank_id=${activeTankId}&range=${range}`);
                if (!response.ok) {
                    throw new Error(`Sunucu hatası: ${response.statusText}`);
                }
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.time.length === 0) {
                    showStatus(`Seçilen tarih aralığında Tank ${activeTankId} için veri bulunamadı.`);
                } else {
                    renderCharts(data);
                }
            } catch (error) {
                showStatus(`Grafik verileri alınırken bir sorun oluştu. Detay: ${error.message}`, true);
            }
        });
    });

    window.addEventListener('resize', () => {
        radarChart.resize();
        basincChart.resize();
        sicaklikChart.resize();
    });
});
</script>