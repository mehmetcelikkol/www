<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enerji Analizörleri - Grafik Değerlendirme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <style>
        body.dark-theme {
            background: #111;
            color: #eee;
        }
        .card {
            background: #222;
            color: #eee;
            border: none;
        }
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999;
        }
    </style>
</head>
<body class="dark-theme">
    <button id="themeBtn" class="btn btn-light theme-toggle" onclick="toggleTheme()">Karanlık Tema</button>
    <div class="container py-5">
        <h1 class="mb-4 text-center">Enerji Analizörleri Grafik Değerlendirme</h1>
        <div class="d-flex justify-content-end align-items-center mb-2">
            <button id="autoRefreshBtn" class="btn btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Otomatik yenileme için önce cihaz seçin">Otomatik Yenileme: Kapalı</button>
            <button id="exportExcelBtn" class="btn btn-success ms-2">Excel’e Aktar</button>
        </div>
        <form id="filterForm" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Cihaz</label>
                <select class="form-select" name="cihaz" id="cihaz">
                    <option value="">Tümü</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kanal</label>
                <select class="form-select" name="kanal" id="kanal">
                    <option value="">Tümü</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Başlangıç Tarihi/Saati</label>
                <input type="datetime-local" class="form-control" name="baslangic" id="baslangic">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bitiş Tarihi/Saati</label>
                <input type="datetime-local" class="form-control" name="bitis" id="bitis">
            </div>
            <div class="col-md-2">
                <label class="form-label">Veri Limiti</label>
                <select class="form-select" name="limit" id="limit">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100" selected>100</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </div>
        </form>
        <div class="card p-4 mb-4" style="background:#222;color:#eee;">
            <canvas id="enerjiChart" style="width:100%;height:60vh;"></canvas>
        </div>
        <div class="card p-4 mb-4" style="background:#222;color:#eee;">
            <h5>Veri Tablosu</h5>
            <div class="table-responsive">
                <table class="table table-dark table-striped" id="dataGrid">
                    <thead>
                        <tr>
                            <th>Tarih/Saat</th>
                            <th>Değer</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="lib/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
    // ...existing code...
    // Tema butonu dinamik metin
        function updateThemeBtn() {
            const btn = document.getElementById('themeBtn');
            if(document.body.classList.contains('dark-theme')) {
                btn.textContent = 'Aydınlık Tema';
                btn.classList.remove('btn-dark');
                btn.classList.add('btn-light');
            } else {
                btn.textContent = 'Karanlık Tema';
                btn.classList.remove('btn-light');
                btn.classList.add('btn-dark');
            }
        }
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            updateThemeBtn();
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeBtn();
            // Cihaz ve kanal seçeneklerini doldur
            fetch('enerji_grafik_options.php')
                .then(res => res.json())
                .then(data => {
                    // Cihazlar
                    const cihazSelect = document.getElementById('cihaz');
                    cihazSelect.innerHTML = '<option value="">Tümü</option>';
                    data.cihazlar.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.cihaz_adi;
                        cihazSelect.appendChild(opt);
                    });
                    // Kanallar
                    const kanalSelect = document.getElementById('kanal');
                    kanalSelect.innerHTML = '<option value="">Tümü</option>';
                    data.kanallar.forEach(k => {
                        const opt = document.createElement('option');
                        opt.value = k.id;
                        opt.textContent = k.ad;
                        kanalSelect.appendChild(opt);
                    });
                });
            // Grafik başlangıcı
            const ctx = document.getElementById('enerjiChart').getContext('2d');
            const enerjiChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#eee'
                            }
                        },
                        zoom: {
                            pan: {
                                enabled: true,
                                mode: 'xy',
                                drag: true
                            },
                            zoom: {
                                wheel: {
                                    enabled: true,
                                },
                                pinch: {
                                    enabled: true
                                },
                                mode: 'xy',
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#eee' },
                            grid: { color: '#333' }
                        },
                        y: {
                            ticks: { color: '#eee' },
                            grid: { color: '#333' }
                        }
                    }
                }
            });
        });

        // Dinamik veri ve filtreler için AJAX altyapısı
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const cihaz = document.getElementById('cihaz').value;
            const kanal = document.getElementById('kanal').value;
            const baslangic = document.getElementById('baslangic').value;
            const bitis = document.getElementById('bitis').value;
            const limit = document.getElementById('limit').value;
            // AJAX ile veri çek
            fetch('enerji_grafik_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cihaz, kanal, baslangic, bitis, limit })
            })
            .then(res => res.json())
            .then(data => {
                // Grafik ve tablo güncelle
                enerjiChart.data.labels = data.labels;
                enerjiChart.data.datasets = data.datasets.map((ds, i) => ({
                    ...ds,
                    borderColor: `hsl(${i*60},80%,60%)`,
                    backgroundColor: `hsla(${i*60},80%,60%,0.2)`,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                }));
                enerjiChart.update();
                // Data grid güncelle
                const tbody = document.querySelector('#dataGrid tbody');
                tbody.innerHTML = '';
                if(data.labels && data.datasets && data.datasets.length > 0){
                    for(let i=0;i<data.labels.length;i++){
                        let row = `<td>${data.labels[i]}</td>`;
                        data.datasets.forEach(ds => {
                            row += `<td>${ds.data[i] !== undefined ? ds.data[i] : ''}</td>`;
                        });
                        const tr = document.createElement('tr');
                        tr.innerHTML = row;
                        tbody.appendChild(tr);
                    }
                    // Grid başlıklarını güncelle
                    const thead = document.querySelector('#dataGrid thead tr');
                    thead.innerHTML = '<th>Tarih/Saat</th>' + data.datasets.map(ds => `<th>${ds.label}</th>`).join('');
                }
            });
        });

        // Otomatik yenileme
        let autoRefresh = false;
        let autoRefreshInterval = null;
        const autoRefreshBtn = document.getElementById('autoRefreshBtn');
        const cihazSelect = document.getElementById('cihaz');
        let autoRefreshTooltip = null;
        let autoRefreshBtnDefaultText = 'Otomatik Yenileme: Kapalı';
        autoRefreshBtn.textContent = autoRefreshBtnDefaultText;
        function enableTooltip() {
            if(autoRefreshTooltip) autoRefreshTooltip.dispose();
            autoRefreshTooltip = new bootstrap.Tooltip(autoRefreshBtn, {trigger: 'hover focus'});
        }
        function disableTooltip() {
            if(autoRefreshTooltip) autoRefreshTooltip.dispose();
        }
        cihazSelect.addEventListener('change', function(){
            if(!cihazSelect.value){
                autoRefreshBtn.title = 'Otomatik yenileme için önce cihaz seçin';
                enableTooltip();
                autoRefreshBtn.textContent = autoRefreshBtnDefaultText;
                autoRefresh = false;
                stopAutoRefresh();
            } else {
                autoRefreshBtn.title = '';
                disableTooltip();
                autoRefreshBtn.textContent = autoRefreshBtnDefaultText;
            }
        });
        autoRefreshBtn.addEventListener('click', function(){
            if(!cihazSelect.value){
                // Sadece tooltip gösterilecek, işlem yapılmayacak
                return;
            }
            autoRefresh = !autoRefresh;
            autoRefreshBtn.textContent = 'Otomatik Yenileme: ' + (autoRefresh ? 'Açık' : 'Kapalı');
            if(autoRefresh){
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        function startAutoRefresh(){
            stopAutoRefresh();
            autoRefreshInterval = setInterval(() => {
                runFilter();
            }, 60000); // 1 dakika
        }
        function stopAutoRefresh(){
            if(autoRefreshInterval){
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }
        function runFilter(){
            const cihaz = cihazSelect.value;
            const kanal = document.getElementById('kanal').value;
            const baslangic = document.getElementById('baslangic').value;
            const bitis = document.getElementById('bitis').value;
            const limit = document.getElementById('limit').value;
            fetch('enerji_grafik_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cihaz, kanal, baslangic, bitis, limit })
            })
            .then(res => res.json())
            .then(data => {
                enerjiChart.data.labels = data.labels;
                enerjiChart.data.datasets = data.datasets.map((ds, i) => ({
                    ...ds,
                    borderColor: `hsl(${i*60},80%,60%)`,
                    backgroundColor: `hsla(${i*60},80%,60%,0.2)`,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                }));
                enerjiChart.update();
                // Data grid güncelle
                const tbody = document.querySelector('#dataGrid tbody');
                tbody.innerHTML = '';
                if(data.labels && data.datasets && data.datasets.length > 0){
                    for(let i=0;i<data.labels.length;i++){
                        let row = `<td>${data.labels[i]}</td>`;
                        data.datasets.forEach(ds => {
                            row += `<td>${ds.data[i] !== undefined ? ds.data[i] : ''}</td>`;
                        });
                        const tr = document.createElement('tr');
                        tr.innerHTML = row;
                        tbody.appendChild(tr);
                    }
                    // Grid başlıklarını güncelle
                    const thead = document.querySelector('#dataGrid thead tr');
                    thead.innerHTML = '<th>Tarih/Saat</th>' + data.datasets.map(ds => `<th>${ds.label}</th>`).join('');
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeBtn();
            fetch('enerji_grafik_options.php')
                .then(res => res.json())
                .then(data => {
                    // Cihazlar
                    const cihazSelect = document.getElementById('cihaz');
                    cihazSelect.innerHTML = '<option value="">Tümü</option>';
                    data.cihazlar.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.cihaz_adi;
                        cihazSelect.appendChild(opt);
                    });
                    // Kanallar
                    const kanalSelect = document.getElementById('kanal');
                    kanalSelect.innerHTML = '<option value="">Tümü</option>';
                    data.kanallar.forEach(k => {
                        const opt = document.createElement('option');
                        opt.value = k.id;
                        opt.textContent = k.ad;
                        kanalSelect.appendChild(opt);
                    });
                });
        });
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (typeof enerjiChart !== 'undefined' && enerjiChart) {
                runFilter();
            }
        });
        // Grafik başlangıcı
        const ctx = document.getElementById('enerjiChart').getContext('2d');
        const enerjiChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#eee'
                        }
                    },
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'xy',
                            drag: true
                        },
                        zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'xy',
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#eee' },
                        grid: { color: '#333' }
                    },
                    y: {
                        ticks: { color: '#eee' },
                        grid: { color: '#333' }
                    }
                }
            }
        });
        document.getElementById('exportExcelBtn').addEventListener('click', function(){
            const table = document.getElementById('dataGrid');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Veriler"});
            XLSX.writeFile(wb, 'enerji_verileri.xlsx');
        });
    </script>
</body>
</html>
