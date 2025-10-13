document.addEventListener('DOMContentLoaded', () => {
  // Tüm grafik canvas'larını yakala (class veya data-* ile)
  const canvases = document.querySelectorAll('canvas.op-chart, canvas[data-operation-name], canvas[data-operation-id]');
  if (canvases.length === 0) return;

  if (typeof Chart === 'undefined') {
    console.error('Chart.js yüklenmemiş. charts.js dosyasından ÖNCE Chart.js ekleyin.');
    return;
  }

  canvases.forEach((el) => {
    const operationName = el.dataset.operationName || 'Bilinmiyor';
    const operationId = el.dataset.operationId || '-';
    const seriesLabel = el.dataset.seriesLabel || 'Güç (kW)';

    // Veri bağlama: her canvas için id_tablosu şeklinde global değişkenleri de destekle
    const labels = (window[`${el.id}_labels`] || window.powerLabels || []);
    const values = (window[`${el.id}_values`] || window.powerValues || []);

    // Üst karta etiket (.tag) ekle (yoksa)
    const device = el.closest('.device');
    if (device && !device.querySelector('.tag.op-tag')) {
      const tag = document.createElement('div');
      tag.className = 'tag op-tag';
      tag.textContent = `Operasyon: ${operationName} (ID: ${operationId})`;
      device.insertBefore(tag, device.firstChild);
    }

    new Chart(el.getContext('2d'), {
      type: el.dataset.type || 'line',
      data: {
        labels,
        datasets: [{
          label: seriesLabel,
          data: values,
          operationName,
          operationId
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: `Operasyon: ${operationName} (ID: ${operationId})`
          },
          tooltip: {
            callbacks: {
              beforeLabel: () => `Operasyon: ${operationName} (ID: ${operationId})`
            }
          }
        }
      }
    });
  });
});