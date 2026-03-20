<?php
require_once __DIR__ . '/includes/header.php';

// Güvenli ID Alımı (String)
$cihaz_kimligi = isset($_GET['id']) ? guvenli($_GET['id']) : '';

if (empty($cihaz_kimligi)) {
    echo '<div class="alert alert-danger m-4">Hatalı Cihaz Kimliği!</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

try {
    $db = Database::getConnection();
    
    // Cihaz Detaylarını Çek
    $sql = "SELECT s.*, c.cihaz_adi, c.konum 
            FROM cihaz_son_durum s 
            LEFT JOIN cihazlar c ON s.cihaz_kimligi = c.cihaz_kodu
            WHERE s.cihaz_kimligi = :id";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $cihaz_kimligi]);
    $cihaz = $stmt->fetch(PDO::FETCH_ASSOC);

    // Limit Bilgilerini Çek
    $sql_limit = "SELECT * FROM cihaz_limitleri WHERE cihaz_kimligi = :id";
    $stmt = $db->prepare($sql_limit);
    $stmt->execute([':id' => $cihaz_kimligi]);
    $limit = $stmt->fetch(PDO::FETCH_ASSOC);

    $max_agirlik = ($limit && $limit['max_agirlik'] > 0) ? floatval($limit['max_agirlik']) : 20000;
    $min_agirlik = ($limit) ? floatval($limit['min_agirlik']) : 0;
    
} catch (Exception $e) {
    echo '<div class="alert alert-warning m-4">Hata: ' . $e->getMessage() . '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="container-fluid animate__animated animate__fadeIn">
    
    <!-- Başlık ve Navigasyon -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="neon-text mb-1">SİSTEM ANALİZİ</h2>
            <p class="text-muted small mb-0">
                <i class="fa-solid fa-microchip me-1"></i> <?php echo !empty($cihaz['cihaz_adi']) ? $cihaz['cihaz_adi'] : $cihaz['cihaz_kimligi']; ?> 
                | <i class="fa-solid fa-location-dot ms-2 me-1"></i> <?php echo $cihaz['konum'] ?? 'Saha Tanımsız'; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-primary rounded-pill btn-sm px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> MERKEZE DÖN
            </a>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- SOL PANEL: CANLI DURUM -->
        <div class="col-xl-4">
            <div class="card h-100 p-3">
                <div class="card-header border-0 bg-transparent px-0">
                    <h5 class="neon-text" style="font-size: 0.9rem;"><i class="fa-solid fa-tower-broadcast me-2"></i> ANLIK TELEMETRİ</h5>
                </div>
                
                <div class="text-center py-4">
                    <!-- Industrial Silo Animation -->
                    <div class="silo-container mb-4">
                        <div id="liveSiloLiquid" class="silo-liquid" style="height: 0%;">
                            <div class="silo-wave"></div>
                        </div>
                        <div id="liveSiloLabel" class="silo-label">0%</div>
                    </div>

                    <div class="stat-value neon-text text-primary" id="liveWeight">
                        0 <span class="stat-unit">KG</span>
                    </div>
                    <div class="small text-muted mt-2" id="lastSeen">Bağlantı bekleniyor...</div>
                </div>

                <div class="list-group list-group-flush bg-transparent">
                    <div class="list-group-item bg-transparent border-opacity-10 d-flex justify-content-between px-0">
                        <span class="text-muted">Paket Dizisi</span>
                        <span class="fw-bold text-info" id="livePacket">-</span>
                    </div>
                    <div class="list-group-item bg-transparent border-opacity-10 d-flex justify-content-between px-0">
                        <span class="text-muted">Kapasite Üst Limit</span>
                        <span class="fw-bold text-success"><?php echo number_format($max_agirlik,0,',','.'); ?> kg</span>
                    </div>
                    <div class="list-group-item bg-transparent border-opacity-10 d-flex justify-content-between px-0">
                        <span class="text-muted">Kritik Alt Limit</span>
                        <span class="fw-bold text-danger"><?php echo number_format($min_agirlik,0,',','.'); ?> kg</span>
                    </div>
                    
                    <!-- TAHMİN KARTI (MODERNİZELİ) -->
                    <div class="mt-4 p-3 rounded bg-primary bg-opacity-10 border border-primary border-opacity-25">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-primary bg-opacity-20 text-primary rounded-circle me-3">
                                <i class="fa-solid fa-hourglass-start"></i>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">TAHMİNİ TÜKETİM BİTİŞ</small>
                                <div class="fw-bold text-primary" id="tahminMetin" style="font-family: 'Orbitron', sans-serif;">Hesaplanıyor...</div>
                                <small class="text-muted" id="tahminTarih" style="font-size: 0.7rem;">-</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ PANEL: GRAFİK VE VERİ -->
        <div class="col-xl-8">
            <div class="row g-4">
                <!-- Ağırlık Grafiği -->
                <div class="col-12">
                    <div class="card p-3">
                        <div class="card-header border-0 bg-transparent px-0 d-flex justify-content-between align-items-center">
                            <h5 class="neon-text" style="font-size: 0.9rem;"><i class="fa-solid fa-chart-line me-2"></i> ZAMANSAL VERİ AKIŞI</h5>
                            <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 pulse-dot-container">
                                <span class="pulse-dot me-1"></span> LIVE
                            </div>
                        </div>
                        <div id="weightChart" style="min-height: 350px;"></div>
                    </div>
                </div>

                <!-- Tablo -->
                <div class="col-12">
                    <div class="card p-3">
                        <div class="card-header border-0 bg-transparent px-0">
                            <h5 class="neon-text" style="font-size: 0.9rem;"><i class="fa-solid fa-list-ul me-2"></i> SON VERİ PAKETLERİ</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ZAMAN damgası</th>
                                        <th>NET AĞIRLIK (KG)</th>
                                        <th>TREND</th>
                                        <th>STABİLİTE</th>
                                        <th>HATA KODU</th>
                                        <th>PAKET ID</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Sistem verileri taranıyor...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
$(document).ready(function() {
    const cihazID = "<?php echo $cihaz_kimligi; ?>";
    const maxAgirlik = <?php echo $max_agirlik; ?>;

    function updateSiloUI(yuzde) {
        yuzde = Math.max(0, Math.min(100, yuzde));
        $('#liveSiloLiquid').css('height', yuzde + '%');
        $('#liveSiloLabel').text(Math.round(yuzde) + '%');
        
        // Renk değişimi
        $('#liveSiloLiquid').removeClass('liquid-success liquid-warning liquid-danger');
        if(yuzde < 20) $('#liveSiloLiquid').addClass('liquid-danger');
        else if(yuzde < 50) $('#liveSiloLiquid').addClass('liquid-warning');
        else $('#liveSiloLiquid').addClass('liquid-success');
    }

    function veriGuncelle() {
        $.getJSON('cihaz_data.php?id=' + cihazID, function(data) {
            
            if(data.live) {
                $('#liveWeight').html(new Intl.NumberFormat('tr-TR').format(data.live.agirlik) + ' <span class="stat-unit">KG</span>');
                $('#livePacket').text('#' + data.live.paket);
                $('#lastSeen').html('<i class="fa-regular fa-clock me-1"></i> ' + data.live.zaman);
                
                let yuzde = (data.live.agirlik / maxAgirlik) * 100;
                updateSiloUI(yuzde);
            }

            if(data.tahmin) {
                $('#tahminMetin').text(data.tahmin.metin);
                $('#tahminTarih').text(data.tahmin.tarih || '');
            }

            if(data.history && data.history.length > 0) {
                let rows = "";
                let chartData = [];
                let chartCategories = [];
                let reversedHistory = [...data.history].reverse();

                reversedHistory.forEach((row) => {
                    chartData.push(parseFloat(row.agirlik));
                    chartCategories.push(row.tarih.split(' ')[1]); // Sadece saati al
                });

                chart.updateSeries([{ data: chartData }]);
                chart.updateOptions({ xaxis: { categories: chartCategories } });

                data.history.forEach((row, index) => {
                    let trendIcon = '-';
                    if(index < data.history.length - 1) {
                        let nextRowWeight = parseFloat(data.history[index+1].agirlik);
                        if(row.agirlik > nextRowWeight) trendIcon = '<i class="fa-solid fa-arrow-trend-up text-success"></i>';
                        else if(row.agirlik < nextRowWeight) trendIcon = '<i class="fa-solid fa-arrow-trend-down text-danger"></i>';
                    }

                    let stabilBadge = row.stabil == 1 
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">STABİL</span>' 
                        : '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">HAREKETLİ</span>';

                    rows += `<tr>
                        <td class="text-muted small">${row.tarih}</td>
                        <td class="fw-bold">${new Intl.NumberFormat('tr-TR').format(row.agirlik)}</td>
                        <td class="text-center">${trendIcon}</td>
                        <td>${stabilBadge}</td>
                        <td class="text-muted">${row.darbe || '0'}</td>
                        <td class="small text-info">#${row.paket}</td>
                    </tr>`;
                });
                $('#historyTableBody').html(rows);
            }
        });
    }

    // Grafik Ayarları
    var options = {
        series: [{ name: "Silo Ağırlığı", data: [] }],
        chart: { 
            type: 'area', 
            height: 350, 
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        theme: { mode: 'dark' },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3, colors: ['#3b82f6'] },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] }
        },
        xaxis: { 
            categories: [],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#64748b' } }
        },
        yaxis: { labels: { style: { colors: '#64748b' } } },
        grid: { borderColor: '#1e293b', strokeDashArray: 4 },
        colors: ['#3b82f6']
    };
    var chart = new ApexCharts(document.querySelector("#weightChart"), options);
    chart.render();

    veriGuncelle();
    setInterval(veriGuncelle, 5000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
