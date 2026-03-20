/* dashboard.js - ApexCharts rendering için */
$(function(){
  function renderSummary(s){
    $('#totalSilos').text(s.total_silos);
    $('#criticalCount').text(s.critical);
    $('#commErrors').text(s.comm_errors);
  }

  function renderHourly(labels, series){
    var options = {
      chart: { type: 'area', height: 240 },
      series: [{ name: 'Tüketim (kg)', data: series }],
      xaxis: { categories: labels },
      stroke: { curve: 'smooth' },
      colors: ['#2b7a78']
    };
    var chart = new ApexCharts(document.querySelector('#hourlyChart'), options);
    chart.render();
  }

  function renderDarbe(labels, series){
    var options = {
      chart: { type: 'bar', height: 200 },
      series: [{ name: 'Darbe Sayısı', data: series }],
      xaxis: { categories: labels },
      colors: ['#ff7a59']
    };
    var chart = new ApexCharts(document.querySelector('#darbeChart'), options);
    chart.render();
  }

  function renderFillEvents(events){
    var el = $('#fillEvents').empty();
    if(!events || events.length===0){ el.append('<div class="text-muted">Dolum tespit edilmedi.</div>'); return; }
    events.forEach(function(ev){
      el.append('<div class="mb-2"><strong>'+ev.tarih+'</strong> — '+(ev.aciklama||'Dolum tespiti')+'</div>');
    });
  }

  function load(){
    apiGet('api/dashboard_stats.php', function(res){
      renderSummary(res.summary || {total_silos:0,critical:0,comm_errors:0});
      renderHourly(res.hourly.labels || [], res.hourly.series || []);
      renderDarbe(res.darbe.labels || [], res.darbe.series || []);
      renderFillEvents(res.fill_events || []);
    }, function(){
      console.error('API yüklenemedi');
    });
  }

  load();
  
  // Cihaz seçimi
  $(document).on('click', '.load-device-btn', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    $('#hourlyChart').html('Yükleniyor...');
    // id burada cihaz_kimligi veya numeric id olabilir. Eğer stringse kimlik paramesi kullan.
    var url = 'api/device_stats.php?';
    if (typeof id === 'number' || /^[0-9]+$/.test(String(id))) url += 'id='+id;
    else url += 'kimlik='+encodeURIComponent(id);
    apiGet(url, function(res){
      if(res.device){
        $('#totalSilos').text(res.device.cihaz_adi || res.device.id || res.device.kimlik || 'Cihaz');
      }
      renderSummary({ total_silos:1, critical:0, comm_errors:0 });
      renderHourly(res.hourly.labels || [], res.hourly.series || []);
      renderDarbe(res.darbe.labels || [], res.darbe.series || []);
      renderFillEvents(res.fill_events || []);
    }, function(){ toastError('Cihaz verisi yüklenemedi'); });
  });
});
