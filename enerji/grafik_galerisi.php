<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Grafik Galerisi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/app.css">
  <style>
    body{background:#0f172a;color:#e2e8f0;}
    .grid{display:grid;gap:32px;padding:32px;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));}
    .card{background:#1e293b;border-radius:18px;padding:22px;box-shadow:0 20px 50px rgba(15,23,42,0.35);}
    .card h2{margin:0 0 12px;font-size:18px;color:#f8fafc;}
    .card p{margin:0 0 18px;font-size:13px;color:#cbd5f5;}
    canvas{width:100%;height:320px;}
    svg{width:100%;height:320px;}
    .selector{display:flex;align-items:center;gap:12px;margin:24px 32px 0;flex-wrap:wrap;}
    .selector label{font-size:14px;color:#cbd5f5;}
    .selector select{background:#1e293b;color:#e2e8f0;border:1px solid #334155;border-radius:12px;padding:10px;min-width:260px;}
    .selector small{color:#94a3b8;font-size:12px;}
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js"></script>
</head>
<body>
  <?php require __DIR__.'/partials/topnav.php'; ?>
  <div class="container">
    <h1>Grafik Galerisi</h1>
    <div class="selector">
      <label for="chartSelector">Grafik seç:</label>
      <select id="chartSelector" multiple size="6">
        <option value="smooth" selected>Cam Gibi Enerji Trendleri</option>
        <option value="area" selected>Kademeli Alan Grafiği</option>
        <option value="peaks" selected>Pik Talep Dalga</option>
        <option value="phases" selected>Üç Faz Dengesi</option>
        <option value="day" selected>Gün İçi Profil</option>
        <option value="target" selected>Trend vs Hedef</option>
      </select>
      <small>Ctrl tuşu ile çoklu seçim yapabilirsiniz.</small>
    </div>
    <div class="grid">
      <div class="card" data-chart="smooth">
        <h2>Cam Gibi Enerji Trendleri</h2>
        <p>İki seri, yumuşatılmış çizgi + yarı şeffaf alan.</p>
        <canvas id="chartSmooth"></canvas>
      </div>
      <div class="card" data-chart="area">
        <h2>Kademeli Alan Grafiği</h2>
        <p>D3 ile pürüzsüz alan ve sinyal gürültüsü.</p>
        <svg id="chartArea"></svg>
      </div>
      <div class="card" data-chart="peaks">
        <h2>Pik Talep Dalga</h2>
        <p>Gradient dolgulu tek seri, kritik pikler etiketli.</p>
        <canvas id="chartPeaks"></canvas>
      </div>
      <div class="card" data-chart="phases">
        <h2>Üç Faz Dengesi</h2>
        <p>Üst üste alanlar ile faz başına yük dağılımı.</p>
        <canvas id="chartPhases"></canvas>
      </div>
      <div class="card" data-chart="day">
        <h2>Gün İçi Profil</h2>
        <p>Gündüz/gece ayrımı için çift tonlu arka plan.</p>
        <canvas id="chartDayProfile"></canvas>
      </div>
      <div class="card" data-chart="target">
        <h2>Trend vs Hedef</h2>
        <p>Gerçekleşen değer ile kırıklı hedef çizgisi.</p>
        <canvas id="chartTarget"></canvas>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const tickColor = '#cbd5f5';
      const gridColor = 'rgba(148,163,184,0.2)';

      // 1) Cam Gibi Enerji Trendleri
      const ctxSmooth = document.getElementById('chartSmooth');
      const gradSmooth = ctxSmooth.getContext('2d').createLinearGradient(0,0,0,320);
      gradSmooth.addColorStop(0,'rgba(56,189,248,0.45)');
      gradSmooth.addColorStop(1,'rgba(56,189,248,0)');
      new Chart(ctxSmooth,{
        type:'line',
        data:{
          labels:Array.from({length:24},(_,i)=>`${String(i).padStart(2,'0')}:00`),
          datasets:[
            {label:'Aktif Güç',data:[12,19,17,22,35,40,38,33,30,26,20,18,15,12,10,14,19,28,31,29,25,20,16,13],borderColor:'#38bdf8',backgroundColor:gradSmooth,tension:.35,fill:true},
            {label:'Reaktif Güç',data:[6,9,8,10,13,14,15,13,12,11,9,8,7,6,5,6,7,9,11,10,9,7,6,5],borderColor:'#f97316',tension:.35,fill:false}
          ]
        },
        options:{
          plugins:{legend:{labels:{color:'#f8fafc'}},tooltip:{mode:'index',intersect:false}},
          scales:{
            x:{ticks:{color:tickColor}},
            y:{ticks:{color:tickColor},grid:{color:gridColor}}
          }
        }
      });

      // 2) Kademeli Alan Grafiği (D3)
      const svg = d3.select('#chartArea');
      const width = svg.node().getBoundingClientRect().width;
      const height = 320;
      const dataArea = Array.from({length:48},(_,i)=>({
        x:i,
        y:Math.round(32 + 12*Math.sin(i/5)+ d3.randomNormal(0,2.2)())
      }));
      const x = d3.scalePoint().domain(dataArea.map(d=>d.x)).range([40,width-20]);
      const y = d3.scaleLinear().domain([0,d3.max(dataArea,d=>d.y)*1.1]).range([height-30,20]);
      const area = d3.area().x(d=>x(d.x)).y0(height-30).y1(d=>y(d.y)).curve(d3.curveCatmullRom.alpha(0.5));
      svg.append('defs').append('linearGradient').attr('id','gradArea').attr('x1','0%').attr('x2','0%').attr('y1','0%').attr('y2','100%')
        .selectAll('stop').data([{offset:'0%',color:'rgba(94,234,212,0.5)'},{offset:'100%',color:'rgba(94,234,212,0)'}])
        .enter().append('stop').attr('offset',d=>d.offset).attr('stop-color',d=>d.color);
      svg.append('path').datum(dataArea).attr('fill','url(#gradArea)').attr('d',area);
      svg.append('path').datum(dataArea).attr('fill','none').attr('stroke','#5eead4').attr('stroke-width',2.5)
        .attr('d',d3.line().x(d=>x(d.x)).y(d=>y(d.y)).curve(d3.curveCatmullRom.alpha(0.5)));
      svg.append('g').attr('transform',`translate(0,${height-30})`).call(d3.axisBottom(x).tickValues(x.domain().filter((_,i)=>i%6===0))).attr('color','#94a3b8');
      svg.append('g').attr('transform','translate(40,0)').call(d3.axisLeft(y).ticks(5)).attr('color','#94a3b8');

      // 3) Pik Talep Dalga
      const ctxPeaks = document.getElementById('chartPeaks').getContext('2d');
      const gradPeaks = ctxPeaks.createLinearGradient(0,0,0,320);
      gradPeaks.addColorStop(0,'rgba(251,191,36,0.5)');
      gradPeaks.addColorStop(1,'rgba(251,191,36,0)');
      const peakData = [28,34,31,36,48,55,62,58,49,44,39,33,29,31,40,47,52,60,63,58,50,41,35,30];
      new Chart(ctxPeaks,{
        type:'line',
        data:{
          labels:Array.from({length:24},(_,i)=>`${i}:00`),
          datasets:[{
            label:'Talep (kW)',
            data:peakData,
            borderColor:'#facc15',
            backgroundColor:gradPeaks,
            tension:.35,
            fill:true,
            pointRadius:(ctx)=> peakData[ctx.dataIndex] >= 55 ? 5 : 0,
            pointBackgroundColor:'#fb923c'
          }]
        },
        options:{
          plugins:{
            legend:{labels:{color:'#f8fafc'}},
            tooltip:{callbacks:{afterLabel:(ctx)=> ctx.parsed.y>=55 ? '⚠ Pik yük' : ''}}
          },
          scales:{
            x:{ticks:{color:tickColor}},
            y:{ticks:{color:tickColor},grid:{color:gridColor}}
          }
        }
      });

      // 4) Üç Faz Dengesi (stacked area)
      const ctxPhases = document.getElementById('chartPhases').getContext('2d');
      const labelsPhases = Array.from({length:12},(_,i)=>`T${i+1}`);
      const phaseA = [24,26,29,34,33,31,28,26,24,23,22,20];
      const phaseB = phaseA.map((v,i)=>v-4 + (i%3));
      const phaseC = phaseA.map((v,i)=>v-6 + ((i+1)%4));
      new Chart(ctxPhases,{
        type:'line',
        data:{
          labels:labelsPhases,
          datasets:[
            {label:'Faz A',data:phaseA,borderColor:'#38bdf8',backgroundColor:'rgba(56,189,248,0.35)',fill:true,tension:.35},
            {label:'Faz B',data:phaseB,borderColor:'#34d399',backgroundColor:'rgba(52,211,153,0.32)',fill:true,tension:.35},
            {label:'Faz C',data:phaseC,borderColor:'#a855f7',backgroundColor:'rgba(168,85,247,0.28)',fill:true,tension:.35}
          ]
        },
        options:{
          plugins:{legend:{labels:{color:'#f8fafc'}}},
          interaction:{mode:'index',intersect:false},
          scales:{
            x:{stacked:true,ticks:{color:tickColor}},
            y:{stacked:true,ticks:{color:tickColor},grid:{color:gridColor}}
          }
        }
      });

      // 5) Gün İçi Profil
      const ctxDay = document.getElementById('chartDayProfile').getContext('2d');
      const labelsDay = Array.from({length:48},(_,i)=>`${String(Math.floor(i/2)).padStart(2,'0')}:${i%2 ? '30' : '00'}`);
      const dayData = labelsDay.map((_,i)=>{
        const hour = i/2;
        const base = 18 + 8*Math.sin((hour-6)/3);
        return Math.max(8, Math.round(base + (hour>18 ? -6 : 0) + (hour<6 ? -4 : 0) + (Math.random()*4-2)));
      });
      const bg = ctxDay.createLinearGradient(0,0,ctxDay.canvas.width,0);
      bg.addColorStop(0,'rgba(59,130,246,0.12)');
      bg.addColorStop(0.5,'rgba(59,130,246,0.28)');
      bg.addColorStop(1,'rgba(59,130,246,0.12)');
      new Chart(ctxDay,{
        type:'line',
        data:{
          labels:labelsDay,
          datasets:[{label:'kWh',data:dayData,borderColor:'#60a5fa',backgroundColor:bg,fill:true,tension:.35}]
        },
        options:{
          plugins:{legend:{labels:{color:'#f8fafc'}}},
          scales:{
            x:{ticks:{color:tickColor, maxRotation:0,autoSkip:true,autoSkipPadding:12}},
            y:{ticks:{color:tickColor},grid:{color:gridColor}}
          }
        }
      });

      // 6) Trend vs Hedef
      const ctxTarget = document.getElementById('chartTarget').getContext('2d');
      const labelsTarget = Array.from({length:14},(_,i)=>`Hafta ${i+1}`);
      const trendData = [82,85,83,87,88,90,92,95,97,94,96,98,99,101];
      const targetData = Array.from({length:14},()=>95);
      const gradTarget = ctxTarget.createLinearGradient(0,0,0,320);
      gradTarget.addColorStop(0,'rgba(14,165,233,0.35)');
      gradTarget.addColorStop(1,'rgba(14,165,233,0)');
      new Chart(ctxTarget,{
        type:'line',
        data:{
          labels:labelsTarget,
          datasets:[
            {label:'Gerçekleşen',data:trendData,borderColor:'#0ea5e9',backgroundColor:gradTarget,fill:true,tension:.25},
            {label:'Hedef',data:targetData,borderColor:'#fbbf24',borderDash:[6,6],pointRadius:0}
          ]
        },
        options:{
          plugins:{legend:{labels:{color:'#f8fafc'}},tooltip:{mode:'index',intersect:false}},
          scales:{
            x:{ticks:{color:tickColor}},
            y:{ticks:{color:tickColor},grid:{color:gridColor},suggestedMin:75,suggestedMax:105}
          }
        }
      });

      const selector = document.getElementById('chartSelector');
      const cards = document.querySelectorAll('.card');
      const updateVisibility = () => {
        const selected = Array.from(selector.selectedOptions).map(o=>o.value);
        cards.forEach(card=>{
          card.style.display = !selected.length || selected.includes(card.dataset.chart) ? '' : 'none';
        });
      };
      selector.addEventListener('change', updateVisibility);
      updateVisibility();
    });
  </script>
</body>
</html>