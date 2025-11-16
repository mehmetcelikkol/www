<?php
// filepath: c:\wamp64\www\limanrapor\pages\tank_izleme.php

// --- 1. Anlık Tank Verilerini Çek ---
$tank_latest_data = [];
$available_tanks = [];
$error_message = null;
try {
    $tank_list_stmt = $pdo->query("SELECT DISTINCT tank FROM tank_verileri ORDER BY tank ASC");
    $available_tanks = $tank_list_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($available_tanks)) {
        $placeholders = implode(',', array_fill(0, count($available_tanks), '?'));
        $latest_sql = "SELECT t1.* FROM tank_verileri t1 INNER JOIN (SELECT tank, MAX(tarihsaat) AS max_ts FROM tank_verileri WHERE tank IN ($placeholders) GROUP BY tank) t2 ON t1.tank = t2.tank AND t1.tarihsaat = t2.max_ts";
        $latest_stmt = $pdo->prepare($latest_sql);
        $latest_stmt->execute($available_tanks);
        foreach ($latest_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tank_latest_data[$row['tank']] = $row;
        }
    }
} catch(PDOException $e) { $error_message = "Veri çekme hatası: " . $e->getMessage(); }


// --- 2. Tamamlanmış Operasyonları Çek ---
$completed_operations = [];
try {
    $op_sql = "SELECT gemi_adi, Gemi_no, tonaj, islem, kayit_tarihi FROM gemioperasyon ORDER BY kayit_tarihi DESC";
    $op_stmt = $pdo->query($op_sql);
    $all_ops = $op_stmt->fetchAll(PDO::FETCH_ASSOC);

    $started_ops_stack = [];
    // Operasyonları tersten (yeniden eskiye) işleyerek doğru eşleştirme yapalım
    foreach (array_reverse($all_ops) as $op) {
        $op_key = $op['Gemi_no'];
        if ($op['islem'] === 'basla') {
            if (!isset($started_ops_stack[$op_key])) $started_ops_stack[$op_key] = [];
            $started_ops_stack[$op_key][] = $op;
        } elseif ($op['islem'] === 'dur' && isset($started_ops_stack[$op_key]) && !empty($started_ops_stack[$op_key])) {
            $start_op = array_shift($started_ops_stack[$op_key]);
            $completed_operations[] = [
                'gemi_adi' => $start_op['gemi_adi'],
                'tonaj' => $start_op['tonaj'],
                'start_time' => $start_op['kayit_tarihi'],
                'end_time' => $op['kayit_tarihi']
            ];
        }
    }
} catch (PDOException $e) {
    // Bu hata kritik değil, sadece operasyon listesi boş kalır.
}


$months = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
$current_year = date('Y');
?>

<!-- Tank Dashboard (Aynı) -->
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

<!-- Filtreler Alanı (Görsel İyileştirme) -->
<div id="time-filters" class="data-section" style="display:none; margin-top: 1.5rem;">
    <div class="data-header"><h3 id="filter-title">Tarih Aralığı Seçin</h3></div>
    <div class="filters-main-container">
        <!-- Sol Sütun: Genel Filtreler -->
        <div class="filter-groups">
            <div class="filter-card">
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
            <div class="filter-card">
                <strong>Aylar (<?= $current_year ?>):</strong>
                <div class="filter-buttons">
                    <?php foreach ($months as $i => $month): ?>
                        <button class="filter-btn" data-range="month_<?= $i + 1 ?>"><?= $month ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-card">
                <strong>Özel Aralık:</strong>
                <div class="custom-range-inputs">
                    <input type="date" id="start-date" class="date-input" title="Başlangıç Tarihi">
                    <input type="date" id="end-date" class="date-input" title="Bitiş Tarihi">
                    <button id="custom-range-btn" class="filter-btn primary">Getir</button>
                </div>
            </div>
        </div>
        <!-- Sağ Sütun: Operasyonlar ve Seçenekler -->
        <div class="filter-options">
            <?php if (!empty($completed_operations)): ?>
            <div class="filter-card">
                <strong>Tamamlanmış Operasyonlar:</strong>
                <div class="operations-list-box">
                    <?php foreach ($completed_operations as $op): ?>
                        <button class="filter-btn operation-btn" 
                                data-start="<?= date('Y-m-d', strtotime($op['start_time'])) ?>" 
                                data-end="<?= date('Y-m-d', strtotime($op['end_time'])) ?>">
                            <?= htmlspecialchars($op['gemi_adi']) ?> (<?= date('d.m.y', strtotime($op['start_time'])) ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="filter-card">
                <strong>Ek Seçenekler:</strong>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="show-operations-checkbox">
                    <label for="show-operations-checkbox">Operasyon Çizgilerini Göster</label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Alanı (Aynı) -->
<div class="data-section" id="tank-chart-section" style="margin-top: 1.5rem; display: none;">
    <div id="chartContainer">
        <div id="chartRadar" style="width: 100%; height: 350px;"></div>
        <div id="chartBasinc" style="width: 100%; height: 350px; margin-top: 20px;"></div>
        <div id="chartSicaklik" style="width: 100%; height: 350px; margin-top: 20px;"></div>
    </div>
    <div id="chart-status" class="empty-state" style="padding: 40px 20px;"></div>
</div>

<!-- CSS Stilleri -->
<style>
.filters-main-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.filter-groups {
    flex: 3;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.filter-options {
    flex: 2;
    min-width: 250px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.filter-card {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.filter-card > strong {
    display: block;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
}
.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.filter-btn.active {
    background-color: #3b82f6;
    color: white;
    border-color: #2563eb;
    font-weight: 600;
}
.operations-list-box {
    max-height: 170px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 6px;
    background-color: #fff;
}
.operations-list-box .operation-btn {
    display: block;
    width: 100%;
    text-align: left;
    margin-bottom: 5px;
}
</style>

<!-- JavaScript (Yeni Event Listener Eklendi) -->
<script src="assets/js/echarts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Değişkenler ve fonksiyonlar (renderCharts, showStatus vb.) aynı kalacak
    const timeFilters = document.getElementById('time-filters');
    const chartSection = document.getElementById('tank-chart-section');
    const chartContainer = document.getElementById('chartContainer');
    const chartStatus = document.getElementById('chart-status');
    const filterTitle = document.getElementById('filter-title');
    const showOperationsCheckbox = document.getElementById('show-operations-checkbox');
    let activeTankId = null;
    let lastUsedUrl = null;

    // YENİ: Aktif butonu yöneten fonksiyon
    function handleActiveButton(clickedButton) {
        // Tüm filtre butonlarından 'active' sınıfını kaldır
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        // Sadece tıklanan butona 'active' sınıfını ekle
        if (clickedButton) {
            clickedButton.classList.add('active');
        }
    }

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

        const markLineData = {
            symbol: 'none',
            data: data.operations_data || []
        };

        // --- GÖRSEL İYİLEŞTİRMELER BURADA ---

        // 1. Radar Grafiği (Mavi Tonları)
        const radarData = data.tank_data.radar_cm;
        const radarOptions = getBaseChartOptions();
        radarOptions.title = { text: `Tank ${activeTankId} - Radar (cm)` };
        radarOptions.xAxis.data = data.tank_data.time;
        radarOptions.yAxis.axisLabel = { formatter: '{value} cm' };
        radarOptions.series[0].data = radarData;
        radarOptions.series[0].markLine = markLineData;
        // Alan rengini gradient yap
        radarOptions.series[0].areaStyle = {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(59, 130, 246, 0.8)' }, // Üst renk (daha koyu)
                { offset: 1, color: 'rgba(59, 130, 246, 0.1)' }  // Alt renk (daha açık)
            ])
        };
        // Çizgi rengini ayarla
        radarOptions.series[0].lineStyle = { color: '#3B82F6' };
        radarOptions.series[0].itemStyle = { color: '#3B82F6' };


        // 2. Basınç Grafiği (Yeşil Tonları)
        const basincData = data.tank_data.basinc_bar;
        const basincOptions = getBaseChartOptions();
        basincOptions.title = { text: `Tank ${activeTankId} - Basınç (bar)` };
        basincOptions.xAxis.data = data.tank_data.time;
        basincOptions.yAxis.axisLabel = { formatter: '{value} bar' };
        basincOptions.series[0].data = basincData;
        basincOptions.series[0].markLine = markLineData;
        basincOptions.series[0].areaStyle = {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(16, 185, 129, 0.8)' },
                { offset: 1, color: 'rgba(16, 185, 129, 0.1)' }
            ])
        };
        basincOptions.series[0].lineStyle = { color: '#10B981' };
        basincOptions.series[0].itemStyle = { color: '#10B981' };


        // 3. Sıcaklık Grafiği (Turuncu/Kırmızı Tonları)
        const sicaklikData = data.tank_data.sicaklik;
        // Veri yoksa veya tek bir nokta varsa visualMap'in çökmesini engelle
        const minTemp = sicaklikData.length > 0 ? Math.min(...sicaklikData) : 0;
        const maxTemp = sicaklikData.length > 0 ? Math.max(...sicaklikData) : 1;

        const sicaklikOptions = getBaseChartOptions();
        sicaklikOptions.title = { text: `Tank ${activeTankId} - Sıcaklık (°C)` };
        sicaklikOptions.xAxis.data = data.tank_data.time;
        sicaklikOptions.yAxis.axisLabel = { formatter: '{value} °C' };
        sicaklikOptions.series[0].data = sicaklikData;
        sicaklikOptions.series[0].markLine = markLineData;
        
        // --- DEĞİŞİKLİK BURADA ---
        // Hem çizgi hem de alan için değere göre renk değişimi
        sicaklikOptions.visualMap = {
            show: false,
            type: 'continuous',
            dimension: 1, 
            min: minTemp,
            max: maxTemp,
            inRange: {
                // Renk geçişi: Soğuk (mavi) -> Ilık (turuncu) -> Sıcak (kırmızı)
                color: ['#60a5fa', '#f59e0b', '#ef4444']
            }
        };
        // Alan dolgusunu, çizgi renginin şeffaf bir versiyonu olarak ayarla
        sicaklikOptions.series[0].areaStyle = {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: 'rgba(239, 68, 68, 0.5)' }, // Kırmızıya yakın bir başlangıç
                { offset: 1, color: 'rgba(96, 165, 250, 0.1)' }  // Maviye yakın şeffaf bir bitiş
            ])
        };
        // visualMap'in alan rengini de kontrol etmesini sağlamak için bu satırı siliyoruz.
        // delete sicaklikOptions.series[0].areaStyle;


        radarChart.setOption(radarOptions, true);
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
        lastUsedUrl = url;
        
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

    // --- Event Listener'ları Güncelle ---

    // 1. Tank kartına tıklandığında (Aynı)
    document.querySelectorAll('.tank-display').forEach(card => {
        card.addEventListener('click', () => {
            activeTankId = card.getAttribute('data-tank');
            document.querySelectorAll('.tank-display').forEach(c => c.style.outline = 'none');
            card.style.outline = '3px solid #3b82f6';
            filterTitle.innerText = `Tank ${activeTankId} için Tarih Aralığı Seçin`;
            timeFilters.style.display = 'block';
            chartSection.style.display = 'none';
            lastUsedUrl = null;
            handleActiveButton(null); // Yeni tank seçildiğinde aktif butonu temizle
        });
    });

    // 2. Hazır filtre butonuna tıklandığında
    document.querySelectorAll('.filter-btn[data-range]').forEach(button => {
        button.addEventListener('click', (e) => { // event nesnesini al
            handleActiveButton(e.currentTarget); // Aktif butonu ayarla
            const range = button.getAttribute('data-range');
            fetchAndRenderCharts(`api/get_tank_data.php?tank_id=${activeTankId}&range=${range}`);
        });
    });

    // 3. Özel tarih aralığı "Getir" butonuna tıklandığında
    document.getElementById('custom-range-btn').addEventListener('click', (e) => {
        handleActiveButton(e.currentTarget); // Aktif butonu ayarla
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        if (!startDate || !endDate) { alert('Lütfen hem başlangıç hem de bitiş tarihi seçin.'); return; }
        fetchAndRenderCharts(`api/get_tank_data.php?tank_id=${activeTankId}&range=custom&start=${startDate}&end=${endDate}`);
    });

    // 4. Operasyon butonuna tıklandığında
    document.querySelectorAll('.operation-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            handleActiveButton(e.currentTarget); // Aktif butonu ayarla
            const startDate = button.getAttribute('data-start');
            const endDate = button.getAttribute('data-end');
            fetchAndRenderCharts(`api/get_tank_data.php?tank_id=${activeTankId}&range=custom&start=${startDate}&end=${endDate}`);
        });
    });

    // 5. "Operasyon Çizgilerini Göster" checkbox'ı değiştiğinde (Aynı)
    showOperationsCheckbox.addEventListener('change', () => {
        if (lastUsedUrl) {
            fetchAndRenderCharts(lastUsedUrl);
        }
    });

    window.addEventListener('resize', () => {
        radarChart.resize();
        basincChart.resize();
        sicaklikChart.resize();
    });
});
</script>