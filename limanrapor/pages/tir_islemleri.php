<?php
// Tır İşlemleri Sayfası
// PHP veri çekme kodları buradan kaldırıldı. Sayfa artık dinamik olarak JS ile yüklenecek.
?>

<!-- Tır İşlemleri Tablosu -->
<div class="data-section">
    <div class="data-header">
        <h3>🚛 Tır Yükleme İşlemleri</h3>
        <div class="header-actions">
            <span class="data-count"><span id="stats-total-operations">0</span> kayıt</span>
            <button class="export-btn" onclick="window.print()">📄 Yazdır</button>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plaka</th>  
                    <th>Port</th>
                    <th>Dolum Başlama</th>
                    <th>Dolum Bitiş</th>
                    <th>Toplam (Ton)</th>
                    <th>Durdurma Şekli</th>
                    <th>İşlem Süresi</th>
                </tr>
            </thead>
            <tbody id="tir-table-body">
                <!-- Veriler JS ile buraya eklenecek -->
            </tbody>
        </table>
    </div>
    <div id="empty-state-container" style="display: none;" class="empty-state">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
        <h3>Veri bulunamadı</h3>
        <p>Sistemde herhangi bir tır yükleme operasyonu bulunamadı.</p>
    </div>
</div>

<!-- Tır İstatistikleri -->
<div style="background: linear-gradient(135deg, #00b894 0%, #00a085 100%); color: white; padding: 1.5rem; margin: 2rem 0; border-radius: 8px;">
    <h4>📊 Tır İşlemleri İstatistikleri</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <div><strong>Toplam İşlem:</strong><br><span style="font-size: 1.5em;" id="stats-total-operations-card">0</span> adet</div>
        <div><strong>Tamamlanan:</strong><br><span style="font-size: 1.5em; color: #2ecc71;" id="stats-completed">0</span> adet</div>
        <div><strong>Devam Eden:</strong><br><span style="font-size: 1.5em; color: #f39c12;" id="stats-ongoing">0</span> adet</div>
        <div><strong>Bugünkü İşlem:</strong><br><span style="font-size: 1.5em;" id="stats-today-ops">0</span> adet</div>
        <div><strong>Toplam Yüklenen:</strong><br><span style="font-size: 1.5em;" id="stats-total-weight">0</span> kg</div>
        <div><strong>Ortalama Yük:</strong><br><span style="font-size: 1.5em;" id="stats-avg-weight">0</span> kg</div>
    </div>
</div>

<!-- Günlük Özet -->
<div style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); color: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px;">
    <h4>📅 Günlük Özet (<?= date('d.m.Y') ?>)</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <div><strong>Bugün Yüklenen:</strong> <span id="stats-today-weight">0</span> kg</div>
        <div><strong>Tamamlanan:</strong> <span id="stats-today-completed">0</span> tır</div>
        <div><strong>Son İşlem:</strong> <span id="stats-last-op-time">Yok</span></div>
    </div>
</div>

<!-- Grafikler -->
<div id="tir-charts-section" style="margin-top: 2rem; background: #fff; padding: 1rem; border-radius: 8px;">
    <div id="daily-total-chart" style="height: 400px; width: 100%;"></div>
    <div id="chart-status" style="padding: 40px 20px; text-align: center; display: none;">Grafik için veri bulunamadı.</div>
</div>

<script src="assets/js/echarts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dailyTotalChartElement = document.getElementById('daily-total-chart');
    const dailyTotalChart = echarts.init(dailyTotalChartElement);

    function formatTon(num) {
        const n = Number(num);
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            .format(Number.isFinite(n) ? n : 0);
    }

    function toDateSafe(val) {
        if (!val) return null;
        const iso = typeof val === 'string' ? val.replace(' ', 'T') : val;
        const d = new Date(iso);
        return isNaN(d.getTime()) ? null : d;
    }

    function computeStats(rows) {
        const total = rows.length;
        const completedRows = rows.filter(r => !!r.bitis_zamani);
        const ongoing = total - completedRows.length;
        const todayStr = new Date().toISOString().slice(0,10);
        const todayOps = rows.filter(r => (r.baslama_zamani || '').slice(0,10) === todayStr).length;
        const totalTon = rows.reduce((s, r) => s + (parseFloat(r.toplam_ton) || 0), 0);
        const avgTon = total ? totalTon / total : 0;
        const todayCompletedRows = completedRows.filter(r => (r.bitis_zamani || '').slice(0,10) === todayStr);
        const todayTon = todayCompletedRows.reduce((s, r) => s + (parseFloat(r.toplam_ton) || 0), 0);
        const lastOpTime = completedRows.map(r => r.bitis_zamani).sort().slice(-1)[0] || null;
        return {
            total_operations: total,
            completed_operations: completedRows.length,
            ongoing_operations: ongoing,
            today_operations: todayOps,
            total_weight_ton: totalTon,
            avg_weight_ton: avgTon,
            today_weight_ton: todayTon,
            today_completed: todayCompletedRows.length,
            last_operation_time: lastOpTime
        };
    }

    function updateUI(response) {
        const data = Array.isArray(response) ? response
                    : Array.isArray(response?.data) ? response.data
                    : [];
        const stats = response?.stats || computeStats(data);

        document.getElementById('stats-total-operations-card').innerText = stats.total_operations ?? 0;
        document.getElementById('stats-completed').innerText = stats.completed_operations ?? 0;
        document.getElementById('stats-ongoing').innerText = stats.ongoing_operations ?? 0;
        document.getElementById('stats-today-ops').innerText = stats.today_operations ?? 0;
        document.getElementById('stats-total-weight').innerText = formatTon(stats.total_weight_ton) + ' Ton';
        document.getElementById('stats-avg-weight').innerText = formatTon(stats.avg_weight_ton) + ' Ton';
        document.getElementById('stats-today-weight').innerText = formatTon(stats.today_weight_ton) + ' Ton';
        document.getElementById('stats-today-completed').innerText = stats.today_completed ?? 0;
        document.getElementById('stats-last-op-time').innerText = stats.last_operation_time
            ? new Date(String(stats.last_operation_time).replace(' ','T')).toLocaleString('tr-TR') : 'Yok';

        const table = document.querySelector('.data-table');
        if (table && !table.querySelector('thead')) {
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>Plaka</th>
                        <th>Port</th>
                        <th>Başlama</th>
                        <th>Bitiş</th>
                        <th>Toplam (Ton)</th>
                        <th>Durdurma</th>
                        <th>Süre (dakikada Ton)</th>
                    </tr>
                </thead>
                <tbody id="tir-table-body"></tbody>
            `;
        }

        const tableBody = document.getElementById('tir-table-body');
        tableBody.innerHTML = '';

        if (!data || data.length === 0) {
            document.getElementById('empty-state-container').style.display = 'block';
            dailyTotalChart.clear();
            document.getElementById('chart-status').style.display = 'block';
            return;
        } else {
            document.getElementById('empty-state-container').style.display = 'none';
            document.getElementById('chart-status').style.display = 'none';
        }

        data.forEach(row => {
            const start = toDateSafe(row.baslama_zamani);
            const end = toDateSafe(row.bitis_zamani);

            let durationText = 'Devam ediyor...';
            let rateText = '';
            if (start && end) {
                const diffMs = end - start;
                const diffHrs = Math.floor(diffMs / 3600000);
                const diffMins = Math.floor((diffMs % 3600000) / 60000);
                const diffSecs = Math.floor((diffMs % 60000) / 1000);
                durationText = `${String(diffHrs).padStart(2,'0')}:${String(diffMins).padStart(2,'0')}:${String(diffSecs).padStart(2,'0')}`;

                const minutes = diffMs / 60000;
                if (minutes > 0) {
                    const rateTonPerMin = (parseFloat(row.toplam_ton) || 0) / minutes;
                    rateText = ` (${formatTon(rateTonPerMin)} Ton/dk)`;
                }
            }

            const portDisplay = (row.port ?? row.tir_no) ?? '';

            const tr = `
                <tr>
                    <td class="plate-number">${row.plaka || ''}</td>
                    <td>${portDisplay}</td>
                    <td>${start ? start.toLocaleString('tr-TR') : '-'}</td>
                    <td>${end ? end.toLocaleString('tr-TR') : '<span style="color:#f39c12;font-weight:bold;">Devam ediyor</span>'}</td>
                    <td class="amount">${formatTon(row.toplam_ton || 0)} Ton</td>
                    <td>${row.durdurma_sekli || (end ? 'Manuel' : '-')}</td>
                    <td>${durationText}${rateText}</td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', tr);
        });

        const dailyTotals = {};
        data.forEach(row => {
            const day = (row.baslama_zamani || '').slice(0,10);
            if (day) dailyTotals[day] = (dailyTotals[day] || 0) + (parseFloat(row.toplam_ton) || 0);
        });

        const days = Object.keys(dailyTotals).sort();
        const totals = days.map(day => Number(dailyTotals[day].toFixed(2)));

        dailyTotalChart.setOption({
            title: { text: 'Günlük Toplam Yüklenen (Ton)', left: 'center' },
            tooltip: { trigger: 'axis', formatter: params => {
                const p = params[0];
                return `${p.axisValue}<br/>Toplam: ${formatTon(p.data)} Ton`;
            }},
            xAxis: { type: 'category', data: days },
            yAxis: { type: 'value', name: 'Ton' },
            series: [{ name: 'Toplam', type: 'bar', data: totals, color: '#00b894' }],
            dataZoom: [{ type: 'slider', bottom: '10px' }, { type: 'inside' }],
            grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true }
        });
    }

    async function fetchData() {
        try {
            const res = await fetch('api/get_tir_data.php', { cache: 'no-store' });
            const text = await res.text();
            let json;
            try { json = JSON.parse(text); }
            catch (e) {
                console.error('JSON parse hatası. Dönen metin:', text);
                throw e;
            }
            if (json.status && json.status !== 'success' && !Array.isArray(json.data)) {
                console.error('API Hatası:', json.message || json.status);
                return;
            }
            updateUI(json);
        } catch (error) {
            console.error('Fetch Hatası:', error);
        }
    }

    fetchData();
    setInterval(fetchData, 30000);
});
</script>