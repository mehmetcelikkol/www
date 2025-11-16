<?php
// --- BU ESKİ VE GEREKSİZ BLOK SİLİNECEK ---
/*
$data = [];
$gemilog_results = [];
$error_message = null;

if ($pdo) {
    try {
        // Gemi boşaltma toplam verilerini çek
        $sql = "SELECT * FROM flowveri 
                WHERE sensor_adi IN ('gflow1', 'gflow2') 
                AND okuma_zamani >= :start_date AND okuma_zamani <= :end_date
                ORDER BY okuma_zamani DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gemi log verilerini de çek
        $gemilog_sql = "SELECT * FROM gemilog ORDER BY tarihsaat DESC LIMIT 100";
        $gemilog_stmt = $pdo->prepare($gemilog_sql);
        $gemilog_stmt->execute();
        $gemilog_results = $gemilog_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error_message = "Veri çekme hatası: " . $e->getMessage();
    }
}
*/
?>

<?php
$operations = [];
$open_operations = [];
$error_message = null; 

if ($pdo) {
    try {
        // Dropdown için TÜM 'basla' operasyonlarını listele
        $sql_all = "SELECT id, gemi_adi, Gemi_no, tonaj, kayit_tarihi 
                    FROM gemioperasyon 
                    WHERE islem = 'basla' 
                    ORDER BY kayit_tarihi DESC LIMIT 100";
        $stmt_all = $pdo->prepare($sql_all);
        $stmt_all->execute();
        $operations = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        // Açık operasyonları bul (kendinden sonra 'dur' kaydı olmayan 'basla' kayıtları)
        $sql_open = "SELECT g_start.id, g_start.gemi_adi, g_start.Gemi_no, g_start.kayit_tarihi
                     FROM gemioperasyon AS g_start
                     WHERE g_start.islem = 'basla' AND NOT EXISTS (
                        SELECT 1 FROM gemioperasyon AS g_stop 
                        WHERE g_stop.Gemi_no = g_start.Gemi_no 
                        AND g_stop.islem = 'dur' 
                        AND g_stop.kayit_tarihi > g_start.kayit_tarihi
                     )
                     ORDER BY g_start.kayit_tarihi DESC";
        $stmt_open = $pdo->prepare($sql_open);
        $stmt_open->execute();
        $open_operations = $stmt_open->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        $error_message = "Operasyon listesi çekilemedi: " . $e->getMessage();
    }
}
?>

<?php if ($error_message): ?>
    <div class="error" style="background-color: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: .25rem; margin-bottom: 1rem;">
        <strong>Hata:</strong> <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<!-- Filtreler ve İstatistikler Alanı (YENİDEN DÜZENLENDİ) -->
<div id="op-filters" class="data-section" style="margin-top: 1.5rem;">
    <div class="data-header">
        <h3>🚢 Gemi Operasyon Analizi <small id="op-title-details" style="font-weight: normal; color: #475569;"></small></h3>
    </div>
    <div class="op-grid">
        <!-- Sol Sütun -->
        <div>
            <div class="filter-card">
                <strong>İncelenecek Operasyonu Seçin:</strong>
                <select id="operation-selector" class="form-select" style="width: 100%; margin-top: 0.5rem;">
                    <option value="" selected>Lütfen bir operasyon seçin...</option>
                    <?php foreach ($operations as $op): ?>
                        <option value="<?= $op['id'] ?>">
                            <?= htmlspecialchars($op['gemi_adi']) ?> (<?= htmlspecialchars($op['Gemi_no']) ?>) - [<?= date('d.m.Y H:i', strtotime($op['kayit_tarihi'])) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Açık Operasyonlar Listesi (YENİ YER) -->
            <?php if (!empty($open_operations)): ?>
            <div id="open-ops-list" style="margin-top: 1rem; padding: 1rem; background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;">
                <strong style="font-size: .9rem; color: #0c4a6e;">Devam Eden Operasyonlar:</strong>
                <ul style="list-style-type: none; padding-left: 0; margin-top: 0.5rem; font-size: 0.9rem;">
                    <?php foreach ($open_operations as $op): ?>
                        <li style="padding: 5px 0; border-bottom: 1px solid #e0f2fe;">
                            <a href="#" class="open-op-link" data-op-id="<?= $op['id'] ?>" style="text-decoration: none; color: #0369a1; font-weight: 600;">
                                <?= htmlspecialchars($op['gemi_adi']) ?> (<?= htmlspecialchars($op['Gemi_no']) ?>)
                            </a>
                            <span style="float: right; color: #38bdf8;"><?= date('d.m H:i', strtotime($op['kayit_tarihi'])) ?>'den beri</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sağ Sütun -->
        <div id="stats-container" class="stats-card" style="display: none;">
            <div class="stat-item"><span>Başlangıç:</span><strong id="stat-start-time">-</strong></div>
            <div class="stat-item"><span>Bitiş:</span><strong id="stat-end-time">-</strong></div>
            <!-- YENİ ALAN -->
            <div class="stat-item"><span>Dur/Kalk Sayısı:</span><strong id="stat-stop-start">-</strong></div>
            <div class="stat-item"><span>Ort. Debi:</span><strong id="stat-avg-flow">- Ton/h</strong></div>
            <div class="stat-item"><span>Toplam Miktar:</span><strong id="stat-total">- Ton</strong></div>
            <div class="stat-item"><span>Akış Süresi:</span><strong id="stat-flow-time">- dk</strong></div>
            <div class="stat-item"><span>Durma Süresi:</span><strong id="stat-stop-time">- dk</strong></div>
        </div>
    </div>
</div>

<!-- Grafik Alanı -->
<div class="data-section" id="chart-section" style="margin-top: 1.5rem; display: none;">
    
    <!-- DEĞİŞİKLİK BAŞLANGICI: Buton ve Başlık için yeni bir sarmalayıcı -->
    <div class="data-header d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Operasyon Grafikleri</h5>
        <button id="btn-save-pdf" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF Olarak Kaydet
        </button>
    </div>
    <!-- DEĞİŞİKLİK BİTİŞİ -->

    <div id="charts-container"></div>
    <div id="chart-status" class="empty-state" style="padding: 40px 20px;"></div>
</div>

<!-- CSS Stilleri -->
<style>
.op-grid{display:grid;grid-template-columns:1fr 2fr;gap:1rem;align-items:start;}.filter-card{background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05)}
/* DEĞİŞİKLİK: stats-card stilleri güncellendi */
.stats-card{background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05);display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;min-height:100%;align-content:center;}
.stat-item{display:flex;flex-direction:column;gap:.25rem}.stat-item span{font-size:.8rem;color:#64748b}.stat-item strong{font-size:1.1rem;font-weight:600;color:#1e293b}.form-select{padding:8px 12px;border:1px solid #ccc;border-radius:4px;background-color:white}.chart-wrapper{margin-bottom:30px}
@media(max-width: 992px) { .op-grid, .stats-card { grid-template-columns: 1fr; } }
</style>

<!-- Script Bölümü -->
<script src="assets/js/echarts.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

<!-- DEĞİŞİKLİK BAŞLANGICI: PDF Kütüphane Yolları Düzeltildi -->
<script src="assets/js/libs/html2canvas.min.js"></script>
<script src="assets/js/libs/jspdf.umd.min.js"></script>
<!-- DEĞİŞİKLİK BİTİŞİ -->
<script src="assets/js/base64.js"></script>
<script>


document.addEventListener('DOMContentLoaded', function () {
    const opSelector = document.getElementById('operation-selector');
    const chartSection = document.getElementById('chart-section');
    const chartsContainer = document.getElementById('charts-container');
    const chartStatus = document.getElementById('chart-status');
    const statsContainer = document.getElementById('stats-container');
    // --- DEĞİŞİKLİK: Yeni başlık alanı için değişken ---
    const opTitleDetails = document.getElementById('op-title-details');
    let activeChartInstances = [];

    function showStatus(message, isError = false) {
        chartSection.style.display = 'block';
        chartsContainer.innerHTML = '';
        chartStatus.style.display = 'block';
        chartStatus.innerHTML = `<p style="${isError ? 'color:red;' : ''}">${message}</p>`;
        statsContainer.style.display = 'none';
        opTitleDetails.textContent = '';
    }

    function updateStatistics(stats) {
        document.getElementById('stat-start-time').textContent = stats.start_time;
        document.getElementById('stat-end-time').textContent = stats.end_time;
        document.getElementById('stat-avg-flow').textContent = `${stats.avg_flow_rate} Ton/h`;
        document.getElementById('stat-total').textContent = `${stats.total_transferred} Ton`;
        document.getElementById('stat-flow-time').textContent = `${stats.flow_minutes} dk`;
        document.getElementById('stat-stop-time').textContent = `${stats.stop_minutes} dk`;
        // YENİ ALANI GÜNCELLE
        document.getElementById('stat-stop-start').textContent = stats.stop_start_count;
        statsContainer.style.display = 'grid';
    }

    // --- DEĞİŞİKLİK: Yeni başlık güncelleme fonksiyonu ---
    function updateTitle(details) {
        if (details && details.gemi_adi) {
            opTitleDetails.textContent = `- ${details.gemi_adi} (${details.gemi_no}) | ${details.tonaj.toLocaleString()} Ton`;
        } else {
            opTitleDetails.textContent = '';
        }
    }

    async function fetchAndRender(op_id) {
        showStatus('Veriler yükleniyor, lütfen bekleyin...');
        activeChartInstances.forEach(chart => chart.dispose());
        activeChartInstances = [];

        try {
            const url = `api/get_gemi_op_data.php?op_id=${op_id}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Sunucu hatası: ${response.statusText}`);
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            // --- DEĞİŞİKLİK: Koşulsuz olarak güncelle ---
            updateTitle(data.operation_details);
            updateStatistics(data.statistics);

            const chartData = data.chart_data;
            const sensorNames = Object.keys(chartData);
            chartStatus.style.display = 'none';

            sensorNames.forEach(sensorName => {
                const sensorData = chartData[sensorName];
                const chartWrapper = document.createElement('div');
                chartWrapper.className = 'chart-wrapper';
                chartWrapper.style.height = '400px';
                chartsContainer.appendChild(chartWrapper);
                const chart = echarts.init(chartWrapper);
                activeChartInstances.push(chart);

                chart.setOption({
                    title: { text: (sensorName.toLowerCase() === 'gflow1' ? 'Rıhtım 7' : 'Rıhtım 8'), left: 'center' },
                    tooltip: { trigger: 'axis' },
                    legend: { top: 30, data: ['Debi', 'Sıcaklık', 'Yoğunluk'] },
                    grid: { top: 70, left: '5%', right: '5%', bottom: '80px' },
                    xAxis: { type: 'category', data: sensorData.time },
                    yAxis: [{ type: 'value', name: 'Debi (Ton/h)' }, { type: 'value', name: 'Sıcaklık/Yoğunluk', splitLine: { show: false } }],
                    series: [
                        { name: 'Debi', type: 'line', yAxisIndex: 0, data: sensorData.debi, smooth: true },
                        { name: 'Sıcaklık', type: 'line', yAxisIndex: 1, data: sensorData.sicaklik, smooth: true },
                        { name: 'Yoğunluk', type: 'line', yAxisIndex: 1, data: sensorData.yogunluk, smooth: true }
                    ],
                    dataZoom: [{ type: 'slider', bottom: '10px' }, { type: 'inside' }]
                });
            });
            if (activeChartInstances.length > 1) echarts.connect(activeChartInstances);
        } catch (error) {
            showStatus(`Veriler alınırken bir sorun oluştu: ${error.message}`, true);
        }
    }

    opSelector.addEventListener('change', () => {
        const selectedOpId = opSelector.value;
        if (selectedOpId) {
            fetchAndRender(selectedOpId);
        } else {
            chartSection.style.display = 'none';
            statsContainer.style.display = 'none';
            opTitleDetails.textContent = '';
        }
    });

    // --- YENİ KOD: Açık operasyon linklerine tıklama olayı ---
    const openOpLinks = document.querySelectorAll('.open-op-link');
    openOpLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Sayfanın en üste gitmesini engelle
            const opId = this.getAttribute('data-op-id');
            if (opId) {
                opSelector.value = opId; // Dropdown'ı güncelle
                fetchAndRender(opId);   // Verileri yükle
            }
        });
    });
    
    window.addEventListener('resize', () => activeChartInstances.forEach(chart => chart.resize()));

    // --- DEĞİŞİKLİK BAŞLANGICI: Header/Footer Eklemeli PDF Mantığı ---
    document.getElementById('btn-save-pdf').addEventListener('click', function() {
        console.log("PDF kaydetme işlemi başlatıldı.");

        if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            console.error("Hata: html2canvas veya jsPDF kütüphanesi yüklenemedi.");
            alert("PDF kütüphaneleri yüklenemedi. Lütfen dosya yollarını kontrol edin.");
            return;
        }

        const filtersElement = document.getElementById('op-filters');
        const chartsElement = document.getElementById('chart-section');
        
        const gemiAdiText = document.getElementById('op-title-details').textContent;
        const gemiAdi = gemiAdiText ? gemiAdiText.split('|')[0].replace('-', '').trim() : "Rapor";
        const fileName = `${gemiAdi.replace(/ /g, '_')}_${new Date().toLocaleDateString('tr-TR')}.pdf`;

        const pdfButton = document.getElementById('btn-save-pdf');
        pdfButton.style.display = 'none';

        html2canvas(filtersElement, { scale: 2, useCORS: true }).then(canvas1 => {
            html2canvas(chartsElement, { scale: 2, useCORS: true }).then(canvas2 => {
                
                pdfButton.style.display = 'block';

                const imgData1 = canvas1.toDataURL('image/png');
                const imgData2 = canvas2.toDataURL('image/png');
                
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const margin = 15; // Kenar boşluğu

                // Resim 1'i ekle (Filtreler)
                const ratio1 = canvas1.height / canvas1.width;
                const imgHeight1 = (pdfWidth - margin * 2) * ratio1;
                pdf.addImage(imgData1, 'PNG', margin, margin + 20, pdfWidth - margin * 2, imgHeight1);
                
                // Resim 2'yi ekle (Grafikler)
                const ratio2 = canvas2.height / canvas2.width;
                const imgHeight2 = (pdfWidth - margin * 2) * ratio2;

                // Eğer ikinci resim sayfaya sığmıyorsa yeni sayfa ekle
                if (imgHeight1 + imgHeight2 + margin * 2 > pdfHeight) {
                    pdf.addPage();
                    pdf.addImage(imgData2, 'PNG', margin, margin + 25, pdfWidth - margin * 2, imgHeight2);
                } else {
                    pdf.addImage(imgData2, 'PNG', margin, imgHeight1 + margin + 25, pdfWidth - margin * 2, imgHeight2);
                }
                
                // --- YENİ BÖLÜM: Header ve Footer Ekleme ---
                const totalPages = pdf.internal.getNumberOfPages();
                const today = new Date().toLocaleDateString('tr-TR');

                for (let i = 1; i <= totalPages; i++) {
                    pdf.setPage(i); // Sayfayı seç

                    // Header (Başlık)
                    pdf.setFontSize(12);
                    pdf.setTextColor(40);
                    pdf.text('Liman Gemi Operasyon Raporu',  pdfWidth / 2, 30, { align: 'center' });

                    // Footer (Alt Bilgi)
                    pdf.setFontSize(8);
                    pdf.setTextColor(100);
                    const pageStr = `Sayfa ${i} / ${totalPages}`;
                    
                    // Tarih (sola)
                    pdf.text(today, margin, pdfHeight - 10);
                    
                    // Sayfa Numarası (sağa)
                    pdf.text(pageStr, pdfWidth - margin, pdfHeight - 10, { align: 'right' });
                }
                // --- YENİ BÖLÜM SONU ---

                // --- LOGO EKLEME ---
                const logoWidth = 30;
const logoHeight = 15;
const logoY = 5; // üstten boşluk

// 4 logo için eşit aralıklı X konumları hesaplanır
const logoCount = 4;
const logoSpacing = (pdfWidth - 2 * margin - logoCount * logoWidth) / (logoCount - 1);

let logoX = margin;

if (typeof logoRmt !== 'undefined') {
    pdf.addImage(logoRmt, 'PNG', logoX, logoY, logoWidth, logoHeight);
}
logoX += logoWidth + logoSpacing;
if (typeof logoKagem !== 'undefined') {
    pdf.addImage(logoKagem, 'PNG', logoX, logoY, logoWidth, logoHeight);
}

logoX += logoWidth + logoSpacing;
if (typeof logoCelebi !== 'undefined') {
    pdf.addImage(logoCelebi, 'JPEG', logoX, logoY, logoWidth, logoHeight);
}

logoX += logoWidth + logoSpacing;
if (typeof logoKaresi !== 'undefined') {
    pdf.addImage(logoKaresi, 'JPEG', logoX, logoY, logoWidth, logoHeight);
}




                // --- LOGO EKLEME SONU ---

                pdf.save(fileName);

            }).catch(err => {
                pdfButton.style.display = 'block';
                console.error("Grafik alanı oluşturulurken hata oluştu:", err);
                alert("PDF oluşturulurken grafik alanında bir hata oluştu.");
            });
        }).catch(err => {
            pdfButton.style.display = 'block';
            console.error("Filtre alanı oluşturulurken hata oluştu:", err);
            alert("PDF oluşturulurken filtre alanında bir hata oluştu.");
        });
    });
    // --- DEĞİŞİKLİK BİTİŞİ ---

});
</script>