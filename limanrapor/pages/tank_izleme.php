<?php
// filepath: c:\wamp64\www\limanrapor\pages\tank_izleme.php
// Tank İzleme Sayfası - Operasyon Gösterimi Eklendi

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

$months = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
$current_year = date('Y');
?>

<!-- Tank Dashboard -->
<?php if (!empty($tank_latest_data)): ?>
<div class="tank-dashboard">
    <div class="tank-dashboard-header"><h3>🛢️ Tank Durumu - Son Veriler</h3></div>
    <div class="tanks-container">
        <?php foreach ($available_tanks as $tank_num): ?>
            <?php if (isset($tank_latest_data[$tank_num])): 
                $tank_data = $tank_latest_data[$tank_num];
                $sicaklik = ($tank_data['pt100'] ?? 0) / 100;
            ?>
            <div class="tank-display" data-tank="<?= $tank_num ?>" style="cursor: pointer;">
                <div class="tank-title">Tank <?= $tank_num ?></div>
                <img src="img/tank.png" alt="Tank <?= $tank_num ?>" class="tank-image">
                <div class="tank-values">
                    <div class="tank-value"><div class="tank-value-label">Radar (cm)</div><div class="tank-value-data"><?= number_format(($tank_data['rdr'] ?? 0) / 10, 1, ',', '.') ?> cm</div></div>
                    <div class="tank-value"><div class="tank-value-label">Basınç (bar)</div><div class="tank-value-data"><?= number_format(($tank_data['bsnc'] ?? 0) / 100, 2, ',', '.') ?> bar</div></div>
                    <div class="tank-value"><div class="tank-value-label">Sıcaklık</div><div class="tank-value-data"><?= number_format($sicaklik, 2, ',', '.') ?> °C</div></div>
                    <div class="tank-value tank-timestamp-value"><div class="tank-value-label">Son Güncelleme</div><div class="tank-value-data"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($tank_data['tarihsaat'] ?? ''))) ?></div></div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tarih Filtreleri ve Operasyon Seçeneği -->
<div id="time-filters" class="data-section" style="display:none; margin-top: 1.5rem;">
    <div class="data-header">
        <h3 id="filter-title">Tarih Aralığı Seçin</h3>
    </div>
    
    <div class="filters-main-container">
        <!-- Sol Taraf: Filtre Grupları -->
        <div class="filter-groups">
            <div class="filter-buttons-wrapper">
                <strong>Hazır Aralıklar:</strong>
                <div class="filter-buttons">
                    <button class="filter-btn" data-range="last_7_days">Son 7 Gün</button>
                    <button class="filter-btn" data-range="this_week">Bu Hafta</button>
                    <button class="filter-btn" data-range="last_30_days">Son 30 Gün</button>
                    <button class="filter-btn" data-range="this_month">Bu Ay</button>
                    <button class="filter-btn" data-range="last_month">Geçen Ay</button>
                    <button class="filter-btn" data-range="all">Tümü</button>
                </div>
            </div>
            <div class="filter-buttons-wrapper">
                <strong>Aylar (<?= $current_year ?>):</strong>
                <div class="filter-buttons">
                    <?php foreach ($months as $i => $month): ?>
                        <button class="filter-btn" data-range="month_<?= $i + 1 ?>"><?= $month ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-buttons-wrapper">
                <strong>Özel Aralık:</strong>
                <div class="custom-range-inputs">
                    <input type="date" id="start-date" class="date-input" title="Başlangıç Tarihi">
                    <input type="date" id="end-date" class="date-input" title="Bitiş Tarihi">
                    <button id="custom-range-btn" class="filter-btn primary">Getir</button>
                </div>
            </div>
        </div>
        <!-- Sağ Taraf: Ek Seçenekler -->
        <div class="filter-options">
            <strong>Ek Seçenekler:</strong>
            <div class="checkbox-wrapper">
                <input type="checkbox" id="show-operations-checkbox">
                <label for="show-operations-checkbox">Operasyonları Göster</label>
            </div>
        </div>
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
    const showOperationsCheckbox = document.getElementById('show-operations-checkbox');
    let activeTankId = null;
    let lastUsedUrl = null; // Son kullanılan URL'yi sakla

    if (typeof echarts === 'undefined') {
        chartStatus.innerHTML = '<h3>Grafik Kütüphanesi Yüklenemedi</h3><p>Lütfen <strong>assets/js/echarts.min.js</strong> dosyasının doğru yerde olduğundan emin olun.</p>';
        chartSection.style.display = 'block';
        return;
    }

    const radarChart = echarts.init(document.getElementById('chartRadar'));
    const basincChart = echarts.init(document.getElementById('chartBasinc'));
    const sicaklikChart = echarts.init(document.getElementById('chartSicaklik'));
    echarts.connect([radarChart, basincChart, sicaklikChart]);

    function getBaseChartOptions() {
        return {
            tooltip: { trigger: 'axis' },
            grid: { left: '50px', right: '20px', bottom: '80px', containLabel: true },
            xAxis: { type: 'category', boundaryGap: false },
            yAxis: { type: 'value' },
            series: [{ type: 'line', smooth: true, areaStyle: {} }],
            dataZoom: [{ type: 'slider', start: 0, end: 100, bottom: 10 }]
        };
    }

    function renderCharts(data) {
        chartContainer.style.display = 'block';
        chartStatus.style.display = 'none';
        
        radarChart.resize(); basincChart.resize(); sicaklikChart.resize();

        // Her grafik için temel seçenekleri al ve operasyon verisini ekle
        const radarOptions = getBaseChartOptions();
        radarOptions.series[0].data = data.tank_data.radar_cm;
        radarOptions.series[0].markArea = { data: data.operations_data || [] };
        radarOptions.title = { text: `Tank ${activeTankId} - Radar (cm)` };
        radarOptions.xAxis.data = data.tank_data.time;
        radarOptions.yAxis.axisLabel = { formatter: '{value} cm' };
        
        const basincOptions = getBaseChartOptions();
        basincOptions.series[0].data = data.tank_data.basinc_bar;
        basincOptions.series[0].markArea = { data: data.operations_data || [] };
        basincOptions.title = { text: `Tank ${activeTankId} - Basınç (bar)` };
        basincOptions.xAxis.data = data.tank_data.time;
        basincOptions.yAxis.axisLabel = { formatter: '{value} bar' };

        const sicaklikOptions = getBaseChartOptions();
        sicaklikOptions.series[0].data = data.tank_data.sicaklik;
        sicaklikOptions.series[0].markArea = { data: data.operations_data || [] };
        sicaklikOptions.title = { text: `Tank ${activeTankId} - Sıcaklık (°C)` };
        sicaklikOptions.xAxis.data = data.tank_data.time;
        sicaklikOptions.yAxis.axisLabel = { formatter: '{value} °C' };

        radarChart.setOption(radarOptions, true); // true -> önceki ayarları temizle
        basincChart.setOption(basincOptions, true);
        sicaklikChart.setOption(sicaklikOptions, true);
    }

    function showStatus(message, isError = false) {
        chartContainer.style.display = 'none';
        chartStatus.style.display = 'block';
        chartStatus.innerHTML = isError ? `<h3 style="color:#c53030;">Hata</h3><p>${message}</p>` : `<h3>Bilgi</h3><p>${message}</p>`;
    }

    async function fetchAndRenderCharts(url) {
        if (!activeTankId) return;
        lastUsedUrl = url; // URL'yi sakla
        
        const showOps = showOperationsCheckbox.checked;
        const finalUrl = `${url}&show_operations=${showOps}`;

        chartSection.style.display = 'block';
        showStatus('Grafik verileri yükleniyor, lütfen bekleyin...');

        try {
            const response = await fetch(finalUrl);
            if (!response.ok) throw new Error(`Sunucu hatası: ${response.statusText}`);
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            if (data.tank_data.time.length === 0) {
                showStatus(`Seçilen tarih aralığında Tank ${activeTankId} için veri bulunamadı.`);
            } else {
                renderCharts(data);
            }
        } catch (error) {
            showStatus(`Grafik verileri alınırken bir sorun oluştu. Detay: ${error.message}`, true);
        }
    }

    // 1. Tank kartına tıklandığında
    document.querySelectorAll('.tank-display').forEach(card => {
        card.addEventListener('click', () => {
            activeTankId = card.getAttribute('data-tank');
            document.querySelectorAll('.tank-display').forEach(c => c.style.outline = 'none');
            card.style.outline = '3px solid #3b82f6';
            filterTitle.innerText = `Tank ${activeTankId} için Tarih Aralığı Seçin`;
            timeFilters.style.display = 'block';
            chartSection.style.display = 'none';
            lastUsedUrl = null; // Yeni tank seçildi, son URL'yi sıfırla
        });
    });

    // 2. Hazır filtre butonuna tıklandığında
    document.querySelectorAll('.filter-btn[data-range]').forEach(button => {
        button.addEventListener('click', () => {
            const range = button.getAttribute('data-range');
            fetchAndRenderCharts(`api/get_tank_data.php?tank_id=${activeTankId}&range=${range}`);
        });
    });

    // 3. Özel tarih aralığı "Getir" butonuna tıklandığında
    document.getElementById('custom-range-btn').addEventListener('click', () => {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        if (!startDate || !endDate) { alert('Lütfen hem başlangıç hem de bitiş tarihi seçin.'); return; }
        fetchAndRenderCharts(`api/get_tank_data.php?tank_id=${activeTankId}&range=custom&start=${startDate}&end=${endDate}`);
    });

    // 4. "Operasyonları Göster" checkbox'ı değiştiğinde
    showOperationsCheckbox.addEventListener('change', () => {
        if (lastUsedUrl) { // Eğer daha önce bir grafik çizildiyse
            fetchAndRenderCharts(lastUsedUrl); // Aynı filtrelerle tekrar çiz
        }
    });

    window.addEventListener('resize', () => {
        radarChart.resize();
        basincChart.resize();
        sicaklikChart.resize();
    });
});
</script>