(function(){
  "use strict";

  function safeGetTheme(key){
    try{ return (window.localStorage && window.localStorage.getItem(key)) || null; }
    catch(e){ console.warn('Tema okuma hatası:', e); return null; }
  }
  function safeSetTheme(key,val){
    try{ if(window.localStorage){ window.localStorage.setItem(key,val); } }
    catch(e){ console.warn('Tema yazma hatası:', e); }
  }

  // --- Veri köprüsü ve normalizasyon ---
  function ensureSeriesArray(series){
    if(Array.isArray(series)) return series;
    if(series && typeof series === 'object'){
      return Object.keys(series).map(function(k){ return series[k]; });
    }
    return [];
  }
  function normalizePoint(p){
    if(!p) return null;
    var x = (typeof p.x === 'string') ? p.x.replace(' ','T') : p.x;
    var y = Number(p.y);
    if(!isFinite(y)) return null;
    return { x:x, y:y };
  }
  function normalizeSerie(s, idx){
    var name = s && (s.name || s.ad || s.kanal_ad || ('Seri '+(idx+1)));
    var data = Array.isArray(s && s.data) ? s.data.map(normalizePoint).filter(Boolean) : [];
    return { name:name, data:data };
  }
  function normalizeDevice(dev){
    var id = dev && (dev.id || dev.cihaz_id);
    var label = dev && (dev.label || dev.cihaz_adi || ('Cihaz '+id));
    var seriesArr = ensureSeriesArray(dev && dev.series).map(normalizeSerie).filter(function(s){ return s.data.length>0; });
    return { id:id, label:label, series:seriesArr };
  }

  var DEVICES_RAW =
    (Array.isArray(window.DEVICES_FROM_DB) && window.DEVICES_FROM_DB.length)
      ? window.DEVICES_FROM_DB
      : (Array.isArray(window.DEVICES)
          ? window.DEVICES.map(function(d){ return { id:d.cihaz_id, label:d.label, series:d.series }; })
          : []);

  var DEVICES_DATA = DEVICES_RAW.map(normalizeDevice).filter(function(d){ return d.series.length>0; });

  var RANGE_START = window.RANGE_START_ISO;
  var RANGE_END   = window.RANGE_END_ISO;

  console.debug('Grafik veri kontrolü', {
    fromDB: (window.DEVICES_FROM_DB || []).length,
    fromPHP: (window.DEVICES || []).length,
    normalized: DEVICES_DATA.length,
    useDate: window.USE_DATE_FILTER,
    range: [RANGE_START, RANGE_END]
  });

  var OPS_DATA = (window.OPS_FROM_SERVER || []).map(function(op){
    var start = String(op.baslangic || RANGE_START).replace(' ','T');
    var end   = String((op.bitis && op.bitis !== '0000-00-00 00:00:00') ? op.bitis : RANGE_END).replace(' ','T');
    return { name:(op.ad || 'Operasyon'), start:start, end:end };
  });

  // --- Renk/Yardımcılar ---
  function hexToRgb(hex){
    var value = String(hex || '').replace('#','').trim();
    if(value.length === 3) value = value.split('').map(function(c){return c+c;}).join('');
    if(value.length !== 6) return {r:56,g:189,b:248};
    var num = parseInt(value, 16);
    return {r:(num>>16)&255, g:(num>>8)&255, b:num&255};
  }
  function withAlpha(hex, alpha){
    var c = hexToRgb(hex);
    var a = Math.max(0, Math.min(1, alpha));
    return 'rgba('+c.r+','+c.g+','+c.b+','+a+')';
  }
  function mix(hex, target, ratio){
    var c = hexToRgb(hex);
    var t = hexToRgb(target);
    function m(k){ return Math.round(c[k] + (t[k]-c[k]) * ratio).toString(16).padStart(2,'0'); }
    return '#'+m('r')+m('g')+m('b');
  }

  // --- Eklentiler ---
  var NightBandsPlugin = {
    id:'nightBands',
    beforeDraw: function(chart){
      var scales = chart.scales;
      var x = scales && scales.x;
      if(!x || !isFinite(x.min) || !isFinite(x.max)) return;
      var ctx = chart.ctx, area = chart.chartArea;
      ctx.save();
      ctx.fillStyle = 'rgba(14,23,42,0.08)';
      var hourMs = 3600000;
      var cursor = x.min - (x.min % hourMs);
      while(cursor < x.max){
        var hour = new Date(cursor).getHours();
        if(hour < 6 || hour >= 19){
          var from = x.getPixelForValue(cursor);
          var to = x.getPixelForValue(cursor + hourMs);
          var L = Math.max(from, area.left), R = Math.min(to, area.right);
          if(R > L) ctx.fillRect(L, area.top, R-L, area.bottom-area.top);
        }
        cursor += hourMs;
      }
      ctx.restore();
    }
  };

  var OpsOverlayPlugin = {
    id:'opsOverlay',
    afterDatasetsDraw: function(chart, args, opts){
      if(!opts || !opts.enabled || !opts.items || !opts.items.length) return;
      var scales = chart.scales, x = scales && scales.x, area = chart.chartArea;
      if(!x || !area) return;
      var ctx = chart.ctx;
      ctx.save();
      var colors = ['rgba(14,165,233,0.18)','rgba(34,197,94,0.18)','rgba(249,115,22,0.18)','rgba(236,72,153,0.18)'];
      opts.items.forEach(function(item,idx){
        var s = x.getPixelForValue(item.start);
        var e = x.getPixelForValue(item.end);
        if(!isFinite(s) || !isFinite(e)) return;
        var L = Math.max(Math.min(s,e), area.left), R = Math.min(Math.max(s,e), area.right);
        if(R <= L) return;
        ctx.fillStyle = colors[idx % colors.length];
        ctx.fillRect(L, area.top, R-L, area.bottom-area.top);
      });
      ctx.font = '11px "Segoe UI", sans-serif';
      ctx.fillStyle = '#0f172a';
      opts.items.forEach(function(item){
        var s = x.getPixelForValue(item.start);
        var e = x.getPixelForValue(item.end);
        if(!isFinite(s) || !isFinite(e)) return;
        var L = Math.max(Math.min(s,e), area.left), R = Math.min(Math.max(s,e), area.right);
        if(R <= L) return;
        var text = item.name || 'Operasyon';
        var width = ctx.measureText(text).width + 12;
        var cx = Math.min(Math.max(area.left, (L+R)/2 - width/2), area.right - width);
        ctx.fillStyle = 'rgba(255,255,255,0.8)';
        ctx.fillRect(cx, area.top + 6, width, 18);
        ctx.fillStyle = '#0f172a';
        ctx.fillText(text, cx + 6, area.top + 19);
      });
      ctx.restore();
    }
  };

  // --- Temalar ---
  var THEMES = {
    smooth: {
      label: 'Cam Gibi Enerji',
      apply: function(chart){
        (chart.data.datasets||[]).forEach(function(ds){
          var base = ds.__baseColor || ds.borderColor || '#38bdf8';
          ds.borderColor = mix(base, '#0f172a', 0.1);
          ds.borderWidth = 2.4;
          ds.tension = 0.35;
          ds.fill = 'origin';
          ds.pointRadius = 0;
          ds.pointHoverRadius = 4;
          ds.backgroundColor = function(ctx){
            var area = ctx.chart.chartArea;
            if(!area) return withAlpha(base, 0.25);
            var gradient = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            gradient.addColorStop(0, withAlpha(base, 0.35));
            gradient.addColorStop(1, withAlpha(base, 0.02));
            return gradient;
          };
        });
      }
    },
    area: {
      label: 'Kademeli Alan',
      apply: function(chart){
        chart.options.scales.y.stacked = true;
        chart.options.scales.x.stacked = true;
        (chart.data.datasets||[]).forEach(function(ds, idx){
          var base = ds.__baseColor || ds.borderColor || '#14b8a6';
          ds.fill = 'origin';
          ds.tension = 0.25;
          ds.borderWidth = 1.6;
          ds.borderColor = mix(base, '#0f172a', 0.2);
          ds.backgroundColor = withAlpha(mix(base, '#ffffff', 0.45), 0.75);
          ds.pointRadius = 0;
          ds.pointHoverRadius = 0;
          ds.order = idx;
        });
      }
    },
    peaks: {
      label: 'Pik Talep',
      apply: function(chart){
        (chart.data.datasets||[]).forEach(function(ds){
          var base = ds.__baseColor || ds.borderColor || '#f97316';
          ds.borderColor = base;
          ds.backgroundColor = withAlpha(base, 0.15);
          ds.borderWidth = 2.2;
          ds.tension = 0.25;
          ds.fill = 'origin';
          ds.pointRadius = function(ctx){
            var value = (ctx && ctx.parsed) ? ctx.parsed.y : undefined;
            var max = ctx.chart.scales.y.max;
            if(!isFinite(value) || !isFinite(max)) return 0;
            if(value >= max*0.92) return 6;
            if(value >= max*0.8) return 4;
            return 0;
          };
          ds.pointHoverRadius = 6;
          ds.pointBackgroundColor = mix(base, '#ffffff', 0.1);
          ds.segment = {
            borderColor: function(ctx){
              return ctx.p1.parsed.y >= ctx.p0.parsed.y ? base : mix(base, '#0f172a', 0.45);
            }
          };
        });
      }
    },
    minimal: {
      label: 'Minimal Çizgi',
      apply: function(chart){
        (chart.data.datasets||[]).forEach(function(ds){
          var base = ds.__baseColor || ds.borderColor || '#0ea5e9';
          ds.borderColor = base;
          ds.backgroundColor = withAlpha(base, 0.08);
          ds.borderWidth = 1.6;
          ds.tension = 0.1;
          ds.fill = false;
          ds.pointRadius = 0;
          ds.pointHoverRadius = 3;
        });
      }
    }
  };
  var THEME_ORDER = ['smooth','area','peaks','minimal'];

  function baseOptions(){
    return {
      responsive:true,
      maintainAspectRatio:false,
      animation:false,
      interaction:{mode:'nearest',intersect:false},
      scales:{
        x:{
          type:'time',
          adapters:{date:{zone:'Europe/Istanbul'}},
          time:{ tooltipFormat:'dd.MM.yyyy HH:mm', displayFormats:{minute:'HH:mm',hour:'dd MMM HH:mm',day:'dd MMM'} },
          ticks:{autoSkip:true,maxTicksLimit:10}
        },
        y:{
          beginAtZero:false,
          grid:{color:'rgba(148,163,184,0.25)'},
          ticks:{precision:3}
        }
      },
      plugins:{
        legend:{position:'top',labels:{usePointStyle:false}},
        tooltip:{intersect:false,mode:'index'},
        nightBands:{},
        opsOverlay:{enabled:false,items:[]}
      }
    };
  }

  function ensureChartBootstrap(){
    if(typeof window.Chart === 'undefined') return false;
    if(!ensureChartBootstrap._done){
      if(Array.isArray(window.Chart.registerables)){
        window.Chart.register.apply(window.Chart, window.Chart.registerables);
      }
      window.Chart.register(NightBandsPlugin, OpsOverlayPlugin);
      ensureChartBootstrap._done = true;
    }
    return true;
  }

  function makeDeviceCard(device){
    if(!ensureChartBootstrap()){
      window.setTimeout(function(){ makeDeviceCard(device); }, 120);
      return;
    }
    var region = document.getElementById('chartRegion');
    if(!region) return;

    // panel tabanlı markup
    var card = document.createElement('div');
    card.className = 'panel device-card';

    var header = document.createElement('div');
    header.className = 'panel-header'; // grafikler.php’de yoksa CSS yine panel’i stiller
    var title = document.createElement('h2');
    title.className = 'panel-title';
    title.textContent = device.label || ('Cihaz ' + device.id);
    header.appendChild(title);

    var styleBox = document.createElement('div');
    styleBox.className = 'panel-actions';
    styleBox.innerHTML = '<span>Görsel stil</span>';
    var select = document.createElement('select');
    THEME_ORDER.forEach(function(key){
      var opt = document.createElement('option');
      opt.value = key;
      opt.textContent = THEMES[key].label;
      select.appendChild(opt);
    });
    var hint = document.createElement('small');
    hint.textContent = 'Tema anında uygulanır';
    styleBox.appendChild(select);
    styleBox.appendChild(hint);

    header.appendChild(styleBox);
    card.appendChild(header);

    var body = document.createElement('div');
    body.className = 'panel-body canvas-wrap';
    var canvas = document.createElement('canvas');
    body.appendChild(canvas);
    card.appendChild(body);

    region.appendChild(card);

    var palette = ['#22d3ee','#38bdf8','#34d399','#f97316','#fb7185','#a855f7','#facc15'];
    var datasets = (device.series||[]).map(function(serie, idx){
      var baseColor = palette[idx % palette.length];
      var ds = {
        label: serie.name || ('Seri ' + (idx+1)),
        data: Array.isArray(serie.data)? serie.data:[],
        borderColor: baseColor,
        backgroundColor: withAlpha(baseColor,0.15),
        borderWidth:1.8,
        tension:0.2,
        fill:false,
        pointRadius:0,
        pointHoverRadius:3,
        pointBackgroundColor:withAlpha(baseColor,0.85),
        pointBorderColor:'#ffffff'
      };
      ds.__baseColor = baseColor;
      return ds;
    });

    var cfg = { type:'line', data:{datasets:datasets}, options: baseOptions(), plugins:[NightBandsPlugin, OpsOverlayPlugin] };
    var chart = new window.Chart(canvas.getContext('2d'), cfg);

    var storageKey = 'grafik-theme-' + device.id;
    var saved = safeGetTheme(storageKey);
    select.value = THEMES[saved] ? saved : 'smooth';
    applyTheme(chart, select.value);
    select.addEventListener('change', function(){
      var val = select.value;
      safeSetTheme(storageKey, val);
      applyTheme(chart, val);
    });

    window.charts[device.id] = chart;
  }

  function applyTheme(chart, themeKey){
    var theme = THEMES[themeKey] || THEMES.smooth;
    chart.__lastTheme = themeKey;
    chart.options.scales.x.stacked = false;
    chart.options.scales.y.stacked = false;
    chart.options.plugins.legend.labels.usePointStyle = false;
    chart.data.datasets.forEach(function(ds){
      ds.borderColor = ds.__baseColor || ds.borderColor;
      ds.backgroundColor = withAlpha(ds.__baseColor || ds.borderColor, 0.12);
      ds.tension = 0.2;
      ds.fill = false;
      ds.segment = undefined;
    });
    chart.options.plugins.opsOverlay.enabled = (window.opsEnabled === true) && OPS_DATA.length>0;
    chart.options.plugins.opsOverlay.items = OPS_DATA.map(function(op){
      return {
        start: Date.parse(op.start.replace(' ','T')),
        end:   Date.parse(op.end.replace(' ','T')),
        name:  op.name
      };
    });
    theme.apply(chart);
    chart.update();
  }
  window.applyTheme = applyTheme;

  function bootstrapCharts(){
    try{
      var region = document.getElementById('chartRegion');
      if(region){ region.innerHTML = '<div class="panel">Grafikler hazırlanıyor…</div>'; }

      if(typeof window.Chart === 'undefined'){
        setTimeout(bootstrapCharts, 150);
        return;
      }
      if(!bootstrapCharts._bootstrapped){
        if(Array.isArray(window.Chart.registerables)){
          window.Chart.register.apply(window.Chart, window.Chart.registerables);
        }
        window.Chart.register(NightBandsPlugin, OpsOverlayPlugin);
        bootstrapCharts._bootstrapped = true;
      }

      var validDevices = DEVICES_DATA;
      if(!validDevices.length){
        if(region){ region.innerHTML = '<div class="panel warn">Grafiklenebilir cihaz verisi bulunamadı.</div>'; }
        return;
      }
      if(region){ region.innerHTML = ''; }
      window.charts = {};
      validDevices.forEach(makeDeviceCard);
    }catch(e){
      console.error('Grafikler JS hatası:', e);
      var region2 = document.getElementById('chartRegion');
      if(region2){
        region2.innerHTML = '<div class="panel warn">Ön uç JS hatası: '+ String(e && e.message || e) +'</div>';
      }
    }
  }
  window.opsEnabled = true;
  window.charts = {};
  window.bootstrapCharts = bootstrapCharts;

  document.addEventListener('DOMContentLoaded', function(){
    var deviceSelect = document.getElementById('deviceSelect');
    function updateSelectedCounter(){
      var counter = document.getElementById('selectedCounter');
      if(!deviceSelect || !counter) return;
      var total = deviceSelect.options.length;
      var selected = Array.from(deviceSelect.selectedOptions || []).length;
      counter.textContent = 'Seçili cihaz: '+selected+'/'+total;
      if(counter.classList){ counter.classList.toggle('warn', selected === 0); }
    }
    if(deviceSelect){ deviceSelect.addEventListener('change', updateSelectedCounter); updateSelectedCounter(); }

    var opsToggle = document.getElementById('opsToggle');
    if(opsToggle){
      opsToggle.addEventListener('change', function(){
        window.opsEnabled = !!opsToggle.checked;
        Object.keys(window.charts || {}).forEach(function(k){
          var c = window.charts[k];
          if(c){ applyTheme(c, c.__lastTheme || 'smooth'); }
        });
      });
    }
  });

  window.addEventListener('load', bootstrapCharts);

  window.addEventListener('error', function(e){
    var region = document.getElementById('chartRegion');
    if(region && !region.innerHTML){
      region.innerHTML = '<div class="panel warn">JS hatası: '+ String(e && e.message || e) +'</div>';
    }
  });
})();