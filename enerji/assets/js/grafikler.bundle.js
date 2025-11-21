// grafikler.bundle.js
// Harici dosyaya taşındı: inline büyük script blok.
(function(){
  if(typeof window.stageLog === 'function') window.stageLog('BUNDLE_INIT');

  // Güvenli veri erişimi
  const DEVICES = window.DEVICES || [];
  const RANGE_START_ISO = window.RANGE_START_ISO || '';
  const RANGE_END_ISO   = window.RANGE_END_ISO   || '';
  const OPS_FROM_SERVER = window.OPS_FROM_SERVER || [];
  let chartInstances = window.chartInstances || (window.chartInstances = {});
  let OPS_CACHE = window.OPS_CACHE || [];
  let OPS_ENABLED = window.OPS_ENABLED !== undefined ? window.OPS_ENABLED : true;

  // Yardımcılar
  function parseTs(str){ if(!str) return NaN; return Date.parse(String(str).replace(' ','T')); }
  function fmt(d){ const z=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`; }
  function fmtDisp(ts){ const d=new Date(ts); if(!Number.isFinite(+d)) return '-'; return fmt(d).replace('T',' '); }
  function selectedDeviceIds(){
    const sel = document.getElementById('deviceSelect');
    if (sel) {
      const all = [...sel.options].map(o=>parseInt(o.value,10)).filter(n=>!isNaN(n));
      const chosen = [...sel.options].filter(o=>o.selected).map(o=>parseInt(o.value,10)).filter(n=>!isNaN(n));
      return chosen.length ? chosen : all;
    }
    const box=document.getElementById('deviceFilter');
    if(!box) return DEVICES.map(d=>d.cihaz_id);
    const ids=[...box.querySelectorAll('input[type=checkbox]')].filter(i=>i.checked).map(i=>parseInt(i.value,10));
    return ids.length? ids : DEVICES.map(d=>d.cihaz_id);
  }
  function refreshSelectedCount(){
    const total = DEVICES.length; const selCount = selectedDeviceIds().length; const el = document.getElementById('devSelCount'); if (el) el.textContent = `Seçili: ${selCount}/${total}`;
  }
  function initDeviceFilter(){ const sel = document.getElementById('deviceSelect'); if (sel) sel.addEventListener('change', refreshSelectedCount); refreshSelectedCount(); }

  function hexToRgba(hex, alpha){
    const h = hex.replace('#','');
    if(h.length===3) { const r=h[0]+h[0], g=h[1]+h[1], b=h[2]+h[2]; return `rgba(${parseInt(r,16)},${parseInt(g,16)},${parseInt(b,16)},${alpha})`; }
    if(h.length===6){ const r=h.slice(0,2), g=h.slice(2,4), b=h.slice(4,6); return `rgba(${parseInt(r,16)},${parseInt(g,16)},${parseInt(b,16)},${alpha})`; }
    return 'rgba(0,0,0,'+alpha+')';
  }

  function buildOpAreas(ops){
    const areas=[]; const lines=[]; (ops||[]).forEach(o=>{ if(!o.start || !o.end) return; areas.push([{ name:o.name||'Operasyon', xAxis:o.start },{ xAxis:o.end }]); lines.push({ xAxis:o.start }, { xAxis:o.end }); }); return { areas, lines }; }
  function buildTariffAreas(startIso,endIso){ return []; } // Kısaltılmış; tam mantık gerekirse inline sürümden taşı.

  function buildCharts(){
    if(typeof window.stageLog === 'function') window.stageLog('buildCharts_start');
    const container=document.getElementById('charts'); if(!container){ if(window.stageLog) stageLog('NO_CONTAINER'); return; }
    container.innerHTML=''; chartInstances={}; if(stageLog) stageLog('container_cleared');
    const ids = selectedDeviceIds(); const devicesToShow = DEVICES.filter(d=>ids.includes(d.cihaz_id)); stageLog && stageLog('selected_ids='+JSON.stringify(ids)); stageLog && stageLog('devicesToShow_count='+devicesToShow.length);
    if(!devicesToShow.length){ console.warn('Seçili cihaz bulunamadı.'); stageLog && stageLog('NO_DEVICES'); return; }
    const CHART_COLORS = { text:'#f1f5f9', legendText:'#f1f5f9', axisLine:'#94a3b8', gridLine:'rgba(241,245,249,0.25)', zoomText:'#f1f5f9', pointerLine:'#cbd5e1', pointerBg:'rgba(15,23,42,0.85)', pointerFg:'#ffffff' };
    const palette = window.SERIES_PALETTE || ['#1d9bf0','#14c9b0','#ff8a00','#ff5d6c','#8b5cf6','#22c55e','#f59e0b'];
    const minIso = RANGE_START_ISO.replace(' ','T'); const maxIso = RANGE_END_ISO.replace(' ','T');
    devicesToShow.forEach(dev=>{
      const title=document.createElement('div'); title.textContent=dev.label || ('Cihaz '+dev.cihaz_id); title.style.fontSize='12px'; title.style.color=CHART_COLORS.text; title.style.margin='6px 0';
      const row=document.createElement('div'); row.className='chart-row'; const wrap=document.createElement('div'); wrap.className='canvas-wrap'; const chartDiv=document.createElement('div'); chartDiv.style.width='100%'; chartDiv.style.height='100%'; wrap.appendChild(chartDiv);
      const side=document.createElement('div'); side.className='chart-side'; const grp1=document.createElement('div'); grp1.className='group'; const lbl1=document.createElement('div'); lbl1.className='label'; lbl1.textContent='Başlangıç'; const val1=document.createElement('div'); val1.className='value'; val1.textContent='-'; const lbl2=document.createElement('div'); lbl2.className='label'; lbl2.textContent='Bitiş'; const val2=document.createElement('div'); val2.className='value'; val2.textContent='-'; grp1.append(lbl1,val1,lbl2,val2);
      const grp2=document.createElement('div'); grp2.className='group'; const btnSummary=document.createElement('button'); btnSummary.className='btn btn-outline btn-xs'; btnSummary.textContent='Özet Rapor'; btnSummary.type='button'; const btnDetail=document.createElement('button'); btnDetail.className='btn btn-primary btn-xs'; btnDetail.textContent='Detaylı Rapor'; btnDetail.type='button'; grp2.append(btnSummary,btnDetail);
      const grp3=document.createElement('div'); grp3.className='group'; const togTitle=document.createElement('div'); togTitle.textContent='Seriler'; togTitle.style.fontSize='11px'; togTitle.style.fontWeight='600'; togTitle.style.color='#0f172a'; togTitle.style.marginBottom='4px'; grp3.appendChild(togTitle); const scrollBox=document.createElement('div'); scrollBox.className='series-toggles'; const seriesToggleDefs=[]; const primaryMatch='toplam aktif gü'; let foundPrimary=false; (dev.series||[]).forEach((s,idx)=>{ const nm=s.name || ('Seri '+(idx+1)); const lower=nm.toLowerCase(); const isPrimary=lower.startsWith(primaryMatch); if(isPrimary) foundPrimary=true; const rowTog=document.createElement('label'); rowTog.style.display='flex'; rowTog.style.alignItems='center'; rowTog.style.gap='6px'; rowTog.style.fontSize='11px'; rowTog.style.cursor='pointer'; rowTog.style.userSelect='none'; rowTog.style.padding='2px 0'; const col=palette[idx%palette.length]; const cb=document.createElement('input'); cb.type='checkbox'; cb.checked=isPrimary; cb.style.margin='0'; cb.style.width='14px'; cb.style.height='14px'; cb.style.accentColor=col; const txt=document.createElement('span'); txt.textContent=nm; txt.style.color=col; rowTog.append(cb,txt); scrollBox.appendChild(rowTog); seriesToggleDefs.push({ name:nm, input:cb }); }); if(!foundPrimary && seriesToggleDefs.length){ seriesToggleDefs[0].input.checked=true; } grp3.appendChild(scrollBox); side.append(grp1,grp2,grp3); row.appendChild(wrap); row.appendChild(side); const outer=document.createElement('div'); outer.style.marginBottom='12px'; outer.appendChild(title); outer.appendChild(row); container.appendChild(outer);
      function deviceExtent(devX){ let s=Infinity,e=-Infinity; (devX.series||[]).forEach(sv=>{ (sv.data||[]).forEach(p=>{ const t=parseTs(p[0]||p.x); if(Number.isFinite(t)){ if(t<s) s=t; if(t>e) e=t; } }); }); if(!Number.isFinite(s)||!Number.isFinite(e)||e<=s) return null; return {startTs:s,endTs:e}; }
      const devExt=deviceExtent(dev); const initialStart=(minIso ? Date.parse(minIso) : (devExt?.startTs ?? Date.now()-3600*1000)); const initialEnd=(maxIso ? Date.parse(maxIso) : (devExt?.endTs ?? Date.now())); function setSideRange(sTs,eTs){ val1.textContent=fmtDisp(sTs); val2.textContent=fmtDisp(eTs); } setSideRange(initialStart,initialEnd);
      const series=(dev.series||[]).map((s,idx)=>{ const col=palette[idx%palette.length]; return { name:s.name||('Seri '+(idx+1)), type:'line', showSymbol:false, smooth:0.25, sampling:'lttb', progressive:800, progressiveThreshold:5000, lineStyle:{ width:1.6, color:col }, areaStyle:{ color:new echarts.graphic.LinearGradient(0,0,0,1,[ {offset:0,color:hexToRgba(col,0.28)}, {offset:1,color:hexToRgba(col,0.05)} ]) }, emphasis:{ focus:'series', lineStyle:{ width:2.6 } }, blur:{ lineStyle:{ opacity:0.25 }, itemStyle:{ opacity:0.25 }, areaStyle:{ opacity:0.15 } }, data:Array.isArray(s.data)? s.data.map(p=>[p.x ?? p[0], p.y ?? p[1]]) : [] }; });
      const tariffAreas=buildTariffAreas(RANGE_START_ISO,RANGE_END_ISO); if(tariffAreas.length){ series.push({ name:'Tarife', type:'line', data:[], silent:true, markArea:{ z:2, label:{ show:false }, itemStyle:{ color:'rgba(148,163,184,0.10)' }, data:tariffAreas } }); }
      if(OPS_ENABLED && OPS_CACHE.length){ const {areas,lines}=buildOpAreas(OPS_CACHE); series.push({ name:'Operasyon', type:'line', data:[], silent:true, markArea:{ z:7, zlevel:1, label:{ show:false }, itemStyle:{ color:'rgba(20,201,176,0.18)' }, data:areas }, markLine:{ z:8, zlevel:1, symbol:'none', label:{ show:false }, lineStyle:{ color:'#14c9b0', width:1.2, type:'solid' }, data:lines } }); }
      function calcLeftPad(){ let maxAbs=0, hasNeg=false; (dev.series||[]).forEach(s=>{ (s.data||[]).forEach(p=>{ const y=Number(p.y ?? p[1]); if(Number.isFinite(y)){ const a=Math.abs(y); if(a>maxAbs) maxAbs=a; if(y<0) hasNeg=true; } }); }); const digits=maxAbs>0? Math.floor(Math.log10(maxAbs))+1 : 1; const charW=7.2; const extra=24; const signW=hasNeg?8:0; return Math.max(56, Math.min(160, Math.ceil(digits*charW+extra+signW))); }
      const leftPad=calcLeftPad();
      const option={ color:palette, grid:{ left:leftPad, right:18, top:56, bottom:44 }, axisPointer:{ link:[{ xAxisIndex:'all' }], label:{ show:true, backgroundColor:CHART_COLORS.pointerBg, color:CHART_COLORS.pointerFg, borderColor:'#1e293b', borderWidth:1, padding:[4,6], shadowBlur:0, formatter:(params)=>{ const v=params.value; const t=new Date(v); if(!Number.isFinite(+t)) return ''; const z=n=>String(n).padStart(2,'0'); return `${t.getFullYear()}-${z(t.getMonth()+1)}-${z(t.getDate())} ${z(t.getHours())}:${z(t.getMinutes())}`; } }, lineStyle:{ color:CHART_COLORS.pointerLine, width:1, type:'dashed' }, crossStyle:{ color:CHART_COLORS.pointerLine } }, tooltip:{ trigger:'axis', order:'valueDesc', axisPointer:{ type:'cross' } }, legend:{ show:false }, toolbox:{ show:true, right:8, bottom:4, orient:'horizontal', itemSize:16, z:20, feature:{ saveAsImage:{ title:'Kaydet' }, dataZoom:{ title:{ zoom:'Yakınlaştır', back:'Geri' } }, restore:{ title:'Sıfırla' } }, iconStyle:{ borderColor:'#0f172a' }, emphasis:{ iconStyle:{ color:'#0ea5e9' } } }, xAxis:{ type:'time', min:minIso||undefined, max:maxIso||undefined, axisLabel:{ color:CHART_COLORS.text }, axisLine:{ lineStyle:{ color:CHART_COLORS.axisLine }}, splitLine:{ lineStyle:{ color:CHART_COLORS.gridLine }} }, yAxis:{ type:'value', scale:true, axisLabel:{ color:CHART_COLORS.text, fontSize:11, margin:8 }, axisLine:{ lineStyle:{ color:CHART_COLORS.axisLine }}, splitLine:{ lineStyle:{ color:CHART_COLORS.gridLine }} }, dataZoom:[ { type:'inside', xAxisIndex:0 }, { type:'slider', xAxisIndex:0, height:18, start:0, end:100, realtime:true, showDetail:true, textStyle:{ color:CHART_COLORS.zoomText } } ], animationDuration:600, stateAnimation:{ duration:200 }, series };
      const ec=echarts.init(chartDiv,null,{renderer:'canvas'}); ec.setOption(option);
      function applySeriesVisibility(){ const selected={}; series.forEach(sObj=>{ const def=seriesToggleDefs.find(d=>d.name===sObj.name); selected[sObj.name]=def ? !!def.input.checked : true; }); ec.setOption({ legend:{ selected } }); }
      seriesToggleDefs.forEach(def=>{ def.input.addEventListener('change', applySeriesVisibility); }); applySeriesVisibility();
      (function attachZoomExport(){ const totalStart=(minIso ? Date.parse(minIso) : (devExt?.startTs ?? Date.now()-3600*1000)); const totalEnd=(maxIso ? Date.parse(maxIso) : (devExt?.endTs ?? Date.now())); function pushZoom(payload){ const b=payload||{}; const startPct=typeof b.start==='number'? b.start : 0; const endPct=typeof b.end==='number'? b.end : 100; let sTs=b.startValue ? Date.parse(String(b.startValue)) : (totalStart + (totalEnd-totalStart)*(startPct/100)); let eTs=b.endValue ? Date.parse(String(b.endValue)) : (totalStart + (totalEnd-totalStart)*(endPct/100)); if(!Number.isFinite(sTs)||!Number.isFinite(eTs)) return; setSideRange(sTs,eTs); const sIso=new Date(sTs).toISOString().slice(0,16); const eIso=new Date(eTs).toISOString().slice(0,16); window.ZOOM_RANGE={ startTs:sTs, endTs:eTs, startIso:sIso, endIso:eIso, startPct, endPct, deviceId:dev.cihaz_id }; if(chartInstances[dev.cihaz_id]) chartInstances[dev.cihaz_id].lastRange={ startTs:sTs, endTs:eTs }; window.dispatchEvent(new CustomEvent('chartRangeChange',{ detail:window.ZOOM_RANGE })); } ec.on('dataZoom',(evt)=>{ const first=(evt && evt.batch && evt.batch[0]) ? evt.batch[0] : evt || {}; pushZoom(first); }); pushZoom({ start:option.dataZoom[1].start ?? 0, end:option.dataZoom[1].end ?? 100 }); })();
      btnSummary.addEventListener('click',(e)=>{ e.preventDefault(); e.stopPropagation(); try{ const lr=chartInstances[dev.cihaz_id]?.lastRange || { startTs:initialStart, endTs:initialEnd }; console.log('Özet rapor henüz harici dosyada tanımlı değil.'); }catch(err){ console.error('Özet Rapor hata:',err); alert('Özet rapor açılamadı.'); } });
      btnDetail.addEventListener('click',(e)=>{ e.preventDefault(); e.stopPropagation(); try{ const lr=chartInstances[dev.cihaz_id]?.lastRange || { startTs:initialStart, endTs:initialEnd }; console.log('Detaylı rapor henüz harici dosyada tanımlı değil.'); }catch(err){ console.error('Detaylı Rapor hata:',err); alert('Detaylı rapor açılamadı.'); } });
      window.addEventListener('resize', ()=> ec.resize()); chartInstances[dev.cihaz_id]={ chart:ec, wrapper:wrap, sideEl:side, startEl:val1, endEl:val2, lastRange:{ startTs:initialStart, endTs:initialEnd } };
    });
    try{ const toConnect=Object.values(chartInstances).map(o=>o.chart); if(toConnect.length>1) echarts.connect(toConnect); }catch(e){ console.warn('Connect hatası',e); }
    console.log('ECharts kurulum tamam. Grafik sayısı:', Object.keys(chartInstances).length);
    if(stageLog) stageLog('charts_done='+Object.keys(chartInstances).length);
    window.buildCharts = buildCharts; // dış erişim
  }

  document.addEventListener('DOMContentLoaded', ()=>{ try{ initDeviceFilter(); buildCharts(); }catch(e){ console.error('DOMContentLoaded hata:', e); stageLog && stageLog('DOMContent_ERROR'); } });

})();
