<?php
$months = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
$current_year = date('Y');
?>

<!-- Filtreler Alanı (Sağ sütun eklendi) -->
<div id="time-filters" class="data-section" style="margin-top: 1.5rem;">
    <div class="data-header"><h3 id="filter-title">Akış Hızı Grafiği için Tarih Aralığı Seçin</h3></div>
    <div class="filters-main-container">
        <!-- Sol Sütun: Zaman Filtreleri -->
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
        <!-- Sağ Sütun: Veri ve Sensör Filtreleri -->
        <div class="filter-options">
            <div class="filter-card">
                <strong>Veri Serileri:</strong>
                <div id="series-filters" class="checkbox-group">
                    <div class="checkbox-wrapper"><input type="checkbox" id="series-debi" value="Debi" checked><label for="series-debi">Debi</label></div>
                    <div class="checkbox-wrapper"><input type="checkbox" id="series-sicaklik" value="Sıcaklık" checked><label for="series-sicaklik">Sıcaklık</label></div>
                    <div class="checkbox-wrapper"><input type="checkbox" id="series-yogunluk" value="Yoğunluk" checked><label for="series-yogunluk">Yoğunluk</label></div>
                </div>
            </div>
            <div class="filter-card">
                <strong>Sensörler:</strong>
                <div id="sensor-filters" class="checkbox-group">
                    <p>Lütfen önce bir tarih aralığı seçin.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Alanı -->
<div class="data-section" id="chart-section" style="margin-top: 1.5rem; display: none;">
    <div id="charts-container"></div>
    <div id="chart-status" class="empty-state" style="padding: 40px 20px;"></div>
</div>

<!-- CSS Stilleri (Filtre sütunları için güncellendi) -->
<style>
.filters-main-container{display:flex;flex-wrap:wrap;gap:1.5rem}.filter-groups{flex:2;min-width:300px;display:flex;flex-direction:column;gap:1.5rem}.filter-options{flex:1;min-width:200px;display:flex;flex-direction:column;gap:1.5rem}.filter-card{background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05)}.filter-card>strong{display:block;margin-bottom:.75rem;font-size:.9rem;font-weight:600;color:#475569}.filter-buttons{display:flex;flex-wrap:wrap;gap:.5rem}.filter-btn.active{background-color:#3b82f6;color:#fff;border-color:#2563eb;font-weight:600}.chart-wrapper{margin-bottom:30px}.checkbox-group .checkbox-wrapper{display:flex;align-items:center;margin-bottom:5px}.checkbox-group .checkbox-wrapper input{margin-right:8px}
</style>

<!-- Script Bölümü (Filtreleme mantığı eklendi) -->
<script src="assets/js/echarts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartSection = document.getElementById('chart-section');
    const chartsContainer = document.getElementById('charts-container');
    const chartStatus = document.getElementById('chart-status');
    const seriesFilters = document.getElementById('series-filters');
    const sensorFilters = document.getElementById('sensor-filters');
    
    let activeChartInstances = [];
    let fullData = null; // API'den gelen tüm veriyi saklamak için

    function showStatus(message, isError = false) {
        chartsContainer.innerHTML = '';
        chartStatus.style.display = 'block';
        chartStatus.innerHTML = `<p style="${isError ? 'color:red;' : ''}">${message}</p>`;
    }

    function getChartTitle(sensorName) {
        const lowerSensorName = sensorName.toLowerCase();
        if (lowerSensorName === 'gflow1') return 'Rıhtım 7 Flow Sensör';
        if (lowerSensorName === 'gflow2') return 'Rıhtım 8 Flow Sensör';
        if (lowerSensorName.startsWith('tir')) {
            const platformNumber = lowerSensorName.replace('tir', '');
            return `Tır Platform ${platformNumber}`;
        }
        return `Sensör: ${sensorName.toUpperCase()}`;
    }

    /**
     * Bu fonksiyon, mevcut 'fullData'yı kullanarak ve filtreleri kontrol ederek
     * grafikleri sıfırdan çizer.
     */
    function renderChartsFromData() {
        if (!fullData) return;

        // 1. Seçili filtreleri al
        const selectedSeriesNames = Array.from(seriesFilters.querySelectorAll('input:checked')).map(el => el.value);
        const selectedSensors = Array.from(sensorFilters.querySelectorAll('input:checked')).map(el => el.value);

        // 2. Mevcut grafikleri temizle
        activeChartInstances.forEach(chart => chart.dispose());
        activeChartInstances = [];
        chartsContainer.innerHTML = '';

        // 3. Filtrelenmiş sensör listesi oluştur
        const sensorsToRender = Object.keys(fullData).filter(sensorName => selectedSensors.includes(sensorName));

        if (sensorsToRender.length === 0) {
            showStatus('Lütfen en az bir sensör seçin veya seçili sensörler için veri bulunamadı.');
            return;
        }
        chartStatus.style.display = 'none';

        // 4. Filtrelenmiş verilere göre grafikleri oluştur
        sensorsToRender.forEach(sensorName => {
            const sensorData = fullData[sensorName];
            if (sensorData.time.length === 0) return;

            const chartWrapper = document.createElement('div');
            chartWrapper.className = 'chart-wrapper';
            chartWrapper.style.height = '400px';
            chartsContainer.appendChild(chartWrapper);

            const chart = echarts.init(chartWrapper);
            activeChartInstances.push(chart);

            // Filtrelenmiş serileri oluştur
            const series = [];
            if (selectedSeriesNames.includes('Debi')) series.push({ name: 'Debi', type: 'line', yAxisIndex: 0, data: sensorData.debi, smooth: true });
            if (selectedSeriesNames.includes('Sıcaklık')) series.push({ name: 'Sıcaklık', type: 'line', yAxisIndex: 1, data: sensorData.sicaklik, smooth: true });
            if (selectedSeriesNames.includes('Yoğunluk')) series.push({ name: 'Yoğunluk', type: 'line', yAxisIndex: 1, data: sensorData.yogunluk, smooth: true });

            chart.setOption({
                title: { text: getChartTitle(sensorName), left: 'center' },
                tooltip: { trigger: 'axis' },
                legend: { top: 30, data: selectedSeriesNames },
                grid: { top: 70, left: '5%', right: '5%', bottom: '80px' }, // --- DEĞİŞİKLİK: Scrollbar için altta boşluk bırak
                xAxis: { type: 'category', data: sensorData.time },
                yAxis: [
                    { type: 'value', name: 'Debi (Ton/h)' },
                    { type: 'value', name: 'Sıcaklık/Yoğunluk', splitLine: { show: false } }
                ],
                series: series,
                // --- YENİ: Yakınlaştırma ve Kaydırma Çubuğu ---
                dataZoom: [
                    {
                        type: 'slider',      // Altta görünen kaydırma çubuğu
                        start: 0,            // Başlangıçta tüm veriyi göster (%0)
                        end: 100,            // Bitişte tüm veriyi göster (%100)
                        xAxisIndex: 0,       // Yatay ekseni kontrol et
                        bottom: '10px'       // Grafiğin altından 10px yukarıda konumlandır
                    },
                    {
                        type: 'inside',      // Fare tekerleği ile grafiğin içinde yakınlaştırma
                        xAxisIndex: 0,       // Yatay ekseni kontrol et
                        start: 0,
                        end: 100
                    }
                ]
            });
        });

        if (activeChartInstances.length > 1) echarts.connect(activeChartInstances);
    }

    /**
     * Bu fonksiyon, API'den yeni veri çeker, sensör filtresini doldurur
     * ve ardından grafikleri çizmesi için renderChartsFromData'yı çağırır.
     */
    async function fetchAndBuild(url) {
        chartSection.style.display = 'block';
        showStatus('Grafik verileri yükleniyor, lütfen bekleyin...');
        
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Sunucu hatası: ${response.statusText}`);
            
            fullData = await response.json(); // Veriyi global değişkene ata
            if (fullData.error) throw new Error(data.error);

            // Sensör filtresini gelen veriye göre DİNAMİK olarak doldur
            const sensorNames = Object.keys(fullData);
            sensorFilters.innerHTML = ''; // Önce temizle
            if (sensorNames.length > 0) {
                sensorNames.forEach(sensorName => {
                    const id = `sensor-${sensorName}`;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'checkbox-wrapper';
                    wrapper.innerHTML = `<input type="checkbox" id="${id}" value="${sensorName}" checked><label for="${id}">${getChartTitle(sensorName)}</label>`;
                    sensorFilters.appendChild(wrapper);
                });
                // Yeni eklenen checkbox'lara event listener ata
                sensorFilters.querySelectorAll('input').forEach(input => input.addEventListener('change', renderChartsFromData));
            } else {
                sensorFilters.innerHTML = '<p>Veri bulunamadı.</p>';
            }

            // Veri çekildikten ve filtreler oluşturulduktan sonra grafikleri çiz
            renderChartsFromData();

        } catch (error) {
            showStatus(`Grafik verileri alınırken bir sorun oluştu. Detay: ${error.message}`, true);
        }
    }

    function handleActiveButton(clickedButton) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        if (clickedButton) clickedButton.classList.add('active');
    }

    // --- Event Listeners ---
    // Zaman filtreleri yeni veri çeker (fetchAndBuild çağırır)
    document.querySelectorAll('.filter-btn[data-range]').forEach(button => {
        button.addEventListener('click', (e) => {
            handleActiveButton(e.currentTarget);
            const url = `api/get_flow_data.php?range=${e.currentTarget.getAttribute('data-range')}`;
            fetchAndBuild(url);
        });
    });
    document.getElementById('custom-range-btn').addEventListener('click', (e) => {
        handleActiveButton(e.currentTarget);
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        if (!startDate || !endDate) { alert('Lütfen hem başlangıç hem de bitiş tarihi seçin.'); return; }
        const url = `api/get_flow_data.php?range=custom&start=${startDate}&end=${endDate}`;
        fetchAndBuild(url);
    });
    
    // Veri serisi filtresi mevcut veriyi yeniden çizer (renderChartsFromData çağırır)
    seriesFilters.querySelectorAll('input').forEach(input => input.addEventListener('change', renderChartsFromData));

    window.addEventListener('resize', () => {
        activeChartInstances.forEach(chart => chart.resize());
    });

    // Otomatik Başlatma (kullanıcı özelinde kapalı)
    /*
    const initialButton = document.querySelector('.filter-btn[data-range="last_7_days"]');
    if (initialButton) {
        initialButton.click();
    }
    */
});
</script>