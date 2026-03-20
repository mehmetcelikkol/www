<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4"><i class="fa-solid fa-chart-pie text-primary"></i> İstatistikler</h2>
    
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-3 h-100">
                <h5>Toplam Tüketim (Son 7 Gün)</h5>
                <div id="consumptionChart" style="min-height: 350px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100">
                <h5>Cihaz Durumları</h5>
                <div id="statusChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tüketim Grafiği
    var options1 = {
        series: [{ name: 'Tüketim (kg)', data: [1200, 1500, 1100, 1800, 2000, 1700, 1600] }],
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] },
        colors: ['#0d6efd']
    };
    new ApexCharts(document.querySelector("#consumptionChart"), options1).render();

    // Durum Grafiği (Pie)
    var options2 = {
        series: [44, 55, 13],
        chart: { type: 'donut', height: 350 },
        labels: ['Online', 'Offline', 'Hata'],
        colors: ['#198754', '#6c757d', '#dc3545']
    };
    new ApexCharts(document.querySelector("#statusChart"), options2).render();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
