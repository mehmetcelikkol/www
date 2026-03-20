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
    
    // Cihaz Detaylarını Çek (Son Durum tablosundan)
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

    // Limit Değerleri (Yoksa 0)
    $max_agirlik = ($limit && $limit['max_agirlik'] > 0) ? floatval($limit['max_agirlik']) : 0;
    $min_agirlik = ($limit) ? floatval($limit['min_agirlik']) : 0;
    
} catch (Exception $e) {
    echo '<div class="alert alert-warning m-4">Hata: ' . $e->getMessage() . '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="container-fluid">
    
    <!-- Başlık ve Navigasyon -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">
                <i class="fa-solid fa-tower-observation text-primary me-2"></i>
                <?php echo !empty($cihaz['cihaz_adi']) ? $cihaz['cihaz_adi'] : $cihaz['cihaz_kimligi']; ?>
            </h2>
            <p class="text-muted small mb-0">
                Kimlik: <span class="text-dark"><?php echo $cihaz['cihaz_kimligi']; ?></span> | 
                Konum: <?php echo $cihaz['konum'] ?? '-'; ?>
            </p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-arrow-left me-2"></i> Geri Dön</a>
    </div>

    <div class="row g-4">
        
        <!-- Sol Panel: Canlı Durum -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100">
                <h5 class="fw-bold text-secondary mb-3">Anlık Durum</h5>
                
                <div class="text-center mb-4">
                    <div class="display-4 fw-bold text-primary" id="liveWeight">
                        <?php echo number_format($cihaz['agirlik_degeri'], 0, ',', '.'); ?> <small class="fs-4">kg</small>
                    </div>
                    <small class="text-muted" id="lastSeen">Son Veri: <?php echo $cihaz['son_gorulme']; ?></small>
                </div>

                <ul class="list-group list-group-flush small">
                    
                    <!-- TAHMİN KARTI -->
                    <li class="list-group-item bg-light-info border-0 rounded my-2 p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-white text-info rounded-circle p-2 me-3 shadow-sm">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Tahmini Bitiş</small>
                                <div class="fw-bold text-dark" id="tahminMetin">Hesaplanıyor...</div>
                                <small class="text-muted" id="tahminTarih" style="font-size: 0.7rem;">-</small>
                            </div>
                        </div>
                    </li>

                    <li class="list-group-item d-flex justify-content-between">
                        <span>Paket No:</span> <span class="fw-bold" id="livePacket"><?php echo $cihaz['paket_no']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Kapasite (Max):</span> 
                        <?php if($max_agirlik > 0): ?>
                            <span class="text-success fw-bold"><?php echo number_format($max_agirlik,0,',','.'); ?> kg</span>
                        <?php else: ?>
                            <span class="text-danger fw-bold">YOK</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Min Limit:</span> 
                        <?php if($min_agirlik > 0): ?>
                            <span class="text-danger fw-bold"><?php echo number_format($min_agirlik,0,',','.'); ?> kg</span>
                        <?php else: ?>
                            <span class="text-danger fw-bold">YOK</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Yazılım:</span> <span class="text-muted"><?php echo $cihaz['yazilim_surumu']; ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Sağ Panel: Grafik -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-secondary">Ağırlık Değişimi</h5>
                    <div>
                        <?php if($max_agirlik > 0): ?>
                        <span class="badge bg-light text-dark border me-2">Max: <?php echo $max_agirlik; ?></span>
                        <?php endif; ?>
                        <?php if($min_agirlik > 0): ?>
                        <span class="badge bg-light text-danger border">Min: <?php echo $min_agirlik; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="weightChart" style="min-height: 300px;"></div>
            </div>
        </div>

        <!-- Alt Panel: Geçmiş Veriler -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-3">
                <h5 class="fw-bold text-secondary mb-3">Son Veri Paketleri</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Zaman</th>
                                <th>Ağırlık (kg)</th>
                                <th>Trend</th>
                                <th>Stabilite</th>
                                <th>Darbe</th>
                                <th>Paket No</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="6" class="text-center text-muted">Veriler yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- AJAX Scriptleri -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const cihazID = "<?php echo $cihaz_kimligi; ?>";

    function veriGuncelle() {
        $.getJSON('cihaz_data.php?id=' + cihazID, function(data) {
            
            // 1. Canlı Veri Güncelle
            if(data.live) {
                $('#liveWeight').html(new Intl.NumberFormat('tr-TR').format(data.live.agirlik) + ' <small class="fs-4">kg</small>');
                $('#livePacket').text(data.live.paket);
                $('#lastSeen').text("Son Veri: " + data.live.zaman);
            }

            // Tahmin Güncelle
            if(data.tahmin) {
                $('#tahminMetin').text(data.tahmin.metin);
                if(data.tahmin.tarih) {
                    $('#tahminTarih').text(data.tahmin.tarih);
                } else {
                    $('#tahminTarih').text('');
                }
            }

            // 2. Tablo ve Grafik Güncelleme
            if(data.history && data.history.length > 0) {
                let rows = "";
                let chartData = [];
                let chartCategories = [];
                
                // Veriyi ters çevir (Eskiden yeniye) grafik için
                let reversedHistory = [...data.history].reverse();

                reversedHistory.forEach((row) => {
                    chartData.push(parseFloat(row.agirlik));
                    chartCategories.push(row.tarih);
                });

                // Grafiği Güncelle
                chart.updateSeries([{
                    data: chartData
                }]);
                chart.updateOptions({
                    xaxis: { categories: chartCategories }
                });

                // Tabloyu Doldur (Yeniden eskiye)
                data.history.forEach((row, index) => {
                    // ... (Mevcut Tablo Kodu)
                        let nextRowWeight = parseFloat(data.history[index+1].agirlik);
                        if(row.agirlik > nextRowWeight) trendIcon = '<i class="fa-solid fa-arrow-up text-success"></i>';
                        if(row.agirlik < nextRowWeight) trendIcon = '<i class="fa-solid fa-arrow-down text-danger"></i>';
                    }

                    let stabilBadge = row.stabil == 1 
                        ? '<span class="badge bg-success">OK</span>' 
                        : '<span class="badge bg-warning text-dark">Hareketli</span>';

                    rows += `<tr>
                        <td>${row.tarih}</td>
                        <td class="fw-bold">${row.agirlik}</td>
                        <td>${trendIcon}</td>
                        <td>${stabilBadge}</td>
                        <td>${row.darbe}</td>
                        <td>${row.paket}</td>
                    </tr>`;
                });
                $('#historyTableBody').html(rows);
            }
        });
    }

    // İlk Yükleme
    veriGuncelle();
    
    // Periyodik Güncelleme (5 sn)
    setInterval(veriGuncelle, 5000);
});
</script>

<!-- Grafik Placeholder (Sonra eklenecek) -->
<script>
    var minLimit = <?php echo $min_agirlik; ?>;
    var maxLimit = <?php echo $max_agirlik; ?>;

    var options = {
        series: [{
            name: "Ağırlık",
            data: [] // AJAX ile dolacak
        }],
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth' },
        xaxis: { categories: [] }, // AJAX ile dolacak
        colors: ['#0d6efd'],
        annotations: {
            yaxis: [
                {
                    y: minLimit,
                    borderColor: '#FF4560',
                    label: {
                        borderColor: '#FF4560',
                        style: { color: '#fff', background: '#FF4560' },
                        text: 'Min Limit (' + minLimit + ')'
                    }
                },
                {
                    y: maxLimit,
                    borderColor: '#00E396',
                    label: {
                        borderColor: '#00E396',
                        style: { color: '#fff', background: '#00E396' },
                        text: 'Kapasite (' + maxLimit + ')'
                    }
                }
            ]
        }
    };
    var chart = new ApexCharts(document.querySelector("#weightChart"), options);
    chart.render();

    // AJAX Veri Güncelleme Fonksiyonu (Grafik Dahil)
    // ... (Burada mevcut AJAX koduna grafik güncelleme satırı eklenecek)
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
