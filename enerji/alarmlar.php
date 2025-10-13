<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
error_reporting(E_ALL); ini_set('display_errors','1');

// Config okuma (diğer sayfalarla tutarlı)
function load_config(string $path): ?array {
    if(!is_file($path)) return null;
    $xml=@simplexml_load_file($path); if(!$xml||!isset($xml->connectionStrings)) return null;
    foreach($xml->connectionStrings->add as $add){
        if((string)$add['name']==='MySqlConnection'){
            $connStr=(string)$add['connectionString']; $out=[]; foreach(explode(';',$connStr) as $p){ $kv=explode('=',$p,2); if(count($kv)==2) $out[trim($kv[0])]=trim($kv[1]); }
            return $out;
        }
    }
    return null;
}

$CONFIG_PATH_CANDIDATES=[
    'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config'
];
$config=null; foreach($CONFIG_PATH_CANDIDATES as $p){ if($c=load_config($p)){ $config=$c; break; } }

$errors=[]; $devices=[]; $addresses=[]; $deviceChannels=[]; $user=auth_user();
if(!$config){ $errors[]='Config bulunamadı.'; }
else {
    foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($config[$k])) $errors[]='Eksik config anahtarı: '.$k; }
    if(!$errors){
        $db=@new mysqli($config['Server'],$config['Uid'],$config['Pwd'],$config['Database']);
        if($db->connect_error){ $errors[]='DB bağlanamadı: '.$db->connect_error; }
        else {
            $db->set_charset('utf8');
            // Cihaz etiketleri
            if($res=$db->query("SELECT id, cihaz_adi, konum FROM cihazlar ORDER BY id")){
                while($r=$res->fetch_assoc()){ $id=(int)$r['id']; $devices[$id]=[ 'cihaz_adi'=>$r['cihaz_adi']??'', 'konum'=>$r['konum']??'', 'label'=>trim(($r['konum']??'').' - '.($r['cihaz_adi']??'')) ]; }
                $res->close();
            }
            // Adres isimleri
            if($res=$db->query("SELECT id, ad FROM cihaz_adresleri ORDER BY id")){
                while($r=$res->fetch_assoc()){ $addresses[(int)$r['id']]=$r['ad']??('Adres #'.(int)$r['id']); }
                $res->close();
            }
            // Cihaz -> Kanal listesi (distinct adresler)
            if($res=$db->query("SELECT DISTINCT o.cihaz_id, o.cihaz_adres_id, ca.ad FROM olcumler o JOIN cihaz_adresleri ca ON ca.id=o.cihaz_adres_id ORDER BY o.cihaz_id, ca.ad")){
                while($r=$res->fetch_assoc()){
                    $cid=(int)$r["cihaz_id"]; $aid=(int)$r["cihaz_adres_id"]; $ad=$r['ad']??('Adres #'.$aid);
                    if(!isset($deviceChannels[$cid])) $deviceChannels[$cid]=[];
                    $deviceChannels[$cid][]=['id'=>$aid,'ad'=>$ad];
                }
                $res->close();
            }
            $db->close();
        }
    }
}

function h(?string $s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }

?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Alarmlar</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:radial-gradient(circle at 25% 20%,#12345b 0%,#061425 55%,#020c15 90%);color:#e9f1ff;min-height:100vh;display:flex;flex-direction:column}
body.light{background:linear-gradient(145deg,#eef7ff,#ffffff 55%,#e9fbf7);color:#1e2933}
header{padding:12px 20px;display:flex;align-items:center;gap:18px;background:rgba(0,0,0,.25);backdrop-filter:blur(12px) saturate(1.2);-webkit-backdrop-filter:blur(12px) saturate(1.2);border-bottom:1px solid rgba(255,255,255,.15)}
body.light header{background:rgba(255,255,255,.65);border-color:rgba(0,0,0,.08)}
.logo-wrap-big{width:54px;height:54px;border-radius:18px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0f2538;border:1px solid rgba(255,255,255,.2)}
.logo-wrap-big img{width:100%;height:100%;object-fit:contain}
.brand-text{font-size:22px;font-weight:700;letter-spacing:.8px;background:linear-gradient(90deg,#e6f7ff,#9cdeff,#53ffc8);-webkit-background-clip:text;background-clip:text;color:transparent;display:flex;flex-direction:column;line-height:1}
body.light .brand-text{background:linear-gradient(90deg,#0062ad,#009ed9,#05b793);-webkit-background-clip:text;background-clip:text;color:transparent}
.brand-text span{font-size:10px;letter-spacing:2px;opacity:.65}
nav.nav-links{display:flex;gap:14px;align-items:center;margin-left:6px}
nav.nav-links a{position:relative;display:inline-flex;align-items:center;justify-content:center;padding:8px 16px;font-size:12px;font-weight:600;letter-spacing:.65px;text-decoration:none;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.25);color:#cfe9ff;backdrop-filter:blur(10px) saturate(1.2);-webkit-backdrop-filter:blur(10px) saturate(1.2);transition:.35s}
body.light nav.nav-links a{background:rgba(0,0,0,.05);border-color:rgba(0,0,0,.15);color:#18506d}
nav.nav-links a.active{background:linear-gradient(90deg,#ff6b6b,#feca57);color:#fff;box-shadow:0 6px 18px -8px rgba(255,107,107,.55),0 2px 10px -4px rgba(0,0,0,.6)}
.user{margin-left:auto;font-size:12.5px;display:flex;align-items:center;gap:12px;color:#b9c6d8}
.user .theme-toggle{width:40px;height:40px;border-radius:14px;background:rgba(255,255,255,.14);display:grid;place-items:center;cursor:pointer;font-size:18px}
body.light .user .theme-toggle{background:rgba(0,0,0,.08)}
main{flex:1;padding:20px;max-width:1200px;width:100%;margin:0 auto;display:flex;flex-direction:column;gap:16px}
.panel{background:linear-gradient(150deg,rgba(255,255,255,.14),rgba(255,255,255,.06));border:1px solid rgba(255,255,255,.2);padding:16px;border-radius:22px;box-shadow:0 12px 28px -18px rgba(0,0,0,.6)}
body.light .panel{background:linear-gradient(150deg,rgba(255,255,255,.9),rgba(255,255,255,.7));border-color:rgba(0,0,0,.08)}
.panel.errors{border-color:rgba(255,80,80,.6);background:linear-gradient(150deg,rgba(255,50,50,.25),rgba(255,50,50,.08))}
.settings{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end}
.settings label{font-size:12px;color:#b9c6d8;display:flex;flex-direction:column;gap:6px}
body.light .settings label{color:#4d5b68}
.settings input{padding:8px 10px;border-radius:12px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.12);color:inherit;font-size:13px;min-width:120px}
body.light .settings input{background:rgba(0,0,0,.05);border-color:rgba(0,0,0,.18)}
.settings button{padding:10px 14px;border-radius:12px;border:0;background:linear-gradient(90deg,#1d8bff,#15d1b4);color:#fff;font-weight:600;font-size:12px;letter-spacing:.5px;cursor:pointer}
.stats{display:flex;gap:10px;flex-wrap:wrap}
.stat{display:flex;align-items:center;gap:8px;background:#173248;border:1px solid rgba(255,255,255,.24);padding:6px 10px;border-radius:12px;font-size:12px}
body.light .stat{background:#d9e9f5;border-color:rgba(0,0,0,.12);color:#124563}
.severity{display:inline-block;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:700;letter-spacing:.5px}
.sev-critical{background:#ff4d4f;color:#fff}
.sev-high{background:#ff9f43;color:#1b1111}
.sev-warning{background:#feca57;color:#1b1111}
.sev-info{background:#54a0ff;color:#fff}
.sev-low{background:#10ac84;color:#0e1b1b}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 10px;border-bottom:1px dashed rgba(255,255,255,.2);font-size:13px}
body.light th,body.light td{border-color:rgba(0,0,0,.12)}
th{text-align:left;color:#b9c6d8}
body.light th{color:#4d5b68}
tr:hover{background:rgba(255,255,255,.06)}
body.light tr:hover{background:rgba(0,0,0,.04)}
.ack{padding:6px 10px;border-radius:10px;border:0;background:#334155;color:#fff;cursor:pointer;font-size:11px}
.ack:hover{filter:brightness(1.08)}
.muted{opacity:.5}
.badge{display:inline-flex;align-items:center;gap:6px;background:#173248;padding:4px 8px;border-radius:10px;font-size:11px}
body.light .badge{background:#d9e9f5;color:#124563}
</style>
</head>
<body>
<header>
    <div class="logo-wrap-big"><img src="img/logo.png" alt="Logo" onerror="this.style.opacity=0"></div>
    <div class="brand-text">Enerji İzleme<span>ALARMLAR</span></div>
    <nav class="nav-links">
        <a href="index.php">Özet</a>
        <a href="grafikler.php">Grafikler</a>
        <a href="alarmlar.php" class="active">Alarmlar</a>
    </nav>
    <div class="user">
        <span><?= h(($user['ad']??'').' '.($user['soyad']??'')) ?></span>
        <div class="theme-toggle" id="themeToggle" title="Tema Değiştir">🌀</div>
        <a class="logout" href="logout.php" style="color:#ffb4b4;text-decoration:none;font-size:12px" onclick="return confirm('Çıkış yapılsın mı?')">Çıkış</a>
    </div>
</header>
<main>
    <?php if($errors): ?>
        <div class="panel errors">
            <strong>Hatalar:</strong>
            <ul style="margin:6px 0 0 16px;padding:0;list-style:disc">
                <?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="panel settings">
        <label>Offline (sn)
            <input type="number" id="set_offline" min="10" step="10" value="300" />
        </label>
        <label>Yüksek (%)
            <input type="number" id="set_high" min="0" max="100" step="1" value="90" />
        </label>
        <label>Kritik (%)
            <input type="number" id="set_critical" min="0" max="100" step="1" value="95" />
        </label>
        <label>Düşük (%)
            <input type="number" id="set_low" min="0" max="100" step="1" value="10" />
        </label>
        <label>Değişim eşiği (%) 
            <input type="number" id="set_spike" min="1" max="1000" step="1" value="50" />
        </label>
        <button id="saveSettings">Kaydet</button>
        <span class="badge" id="lastUpdate">—</span>
        <span class="badge" id="counts">—</span>
    </div>

    <div class="panel">
        <div class="stats" id="sevStats"></div>
        <div style="overflow:auto">
            <table id="alarmTable">
                <thead>
                    <tr>
                        <th>Seviye</th>
                        <th>Tür</th>
                        <th>Cihaz</th>
                        <th>Adres</th>
                        <th>Değer</th>
                        <th>Fark</th>
                        <th>Zaman</th>
                        <th>Yaş</th>
                        <th>Aksiyon</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="panel" id="rulePanel">
        <h3 style="margin:0 0 10px;font-size:16px">Alarm Kuralı Ekle</h3>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8;min-width:260px">
                Analizör (Konum - Ad)
                <select id="f_cid"><option value="">— Seçiniz —</option></select>
            </label>
            <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8;min-width:220px">
                Kanal
                <select id="f_aid"><option value="">— Önce analizör seçin —</option></select>
            </label>
            <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                Kural Türü
                <select id="f_rule">
                    <option value="threshold">Eşik</option>
                    <option value="rate">Yükselme Hızı (%)</option>
                </select>
            </label>
            <div id="f_thresholds" style="display:flex;gap:8px">
                <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                    Min
                    <input type="number" id="f_min" step="0.01" placeholder="boş bırakılabilir"/>
                </label>
                <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                    Max
                    <input type="number" id="f_max" step="0.01" placeholder="boş bırakılabilir"/>
                </label>
            </div>
            <div id="f_rateBox" style="display:none;gap:8px">
                <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                    % Değişim Eşiği
                    <input type="number" id="f_rate" step="1" placeholder="örn: 50"/>
                </label>
                <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                    Yön
                    <select id="f_dir">
                        <option value="herikisi">Yukarı/Aşağı</option>
                        <option value="yukari">Yukarı</option>
                        <option value="asagi">Aşağı</option>
                    </select>
                </label>
            </div>
            <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8">
                Herkes Görsün
                <select id="f_vis">
                    <option value="1">Evet</option>
                    <option value="0">Hayır</option>
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#b9c6d8;min-width:240px;flex:1">
                Not
                <input type="text" id="f_note" placeholder="İsteğe bağlı açıklama"/>
            </label>
            <div style="display:flex;flex-direction:column;gap:6px;min-width:280px;flex:1">
                <div style="font-size:12px;color:#b9c6d8">E-posta Alıcıları</div>
                <div id="mailPills" style="display:flex;flex-wrap:wrap;gap:6px"></div>
                <input type="text" id="mailInput" placeholder="e-posta yazın ve Enter'a basın" list="mailSuggest"/>
                <datalist id="mailSuggest"></datalist>
            </div>
            <button id="btnSaveRule">Kaydet</button>
        </div>
        <div id="ruleMsg" style="margin-top:8px;font-size:12px;opacity:.8"></div>
        <hr style="border:none;border-top:1px solid rgba(255,255,255,.15);margin:14px 0">
        <h3 style="margin:0 0 10px;font-size:16px">Mevcut Kurallar</h3>
        <div style="overflow:auto">
            <table id="rulesTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Cihaz</th><th>Adres</th><th>Tür</th><th>Detay</th><th>Görünürlük</th><th>Mail</th><th>Oluşturan</th><th>Tarih</th><th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</main>
<footer style="padding:18px 20px;font-size:11px;opacity:.55;text-align:center">© <?= date('Y') ?> Enerji İzleme • Alarmlar</footer>

<script>
// Tema
(()=>{const tg=document.getElementById('themeToggle'); const pref=localStorage.getItem('appTheme'); if(pref==='light') document.body.classList.add('light'); tg&&tg.addEventListener('click',()=>{document.body.classList.toggle('light');localStorage.setItem('appTheme',document.body.classList.contains('light')?'light':'dark');});})();

// Haritalar (PHP'den gömülü)
const DEVICES = <?= json_encode($devices, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>; // id -> {cihaz_adi,konum,label}
const ADDRS   = <?= json_encode($addresses, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>; // id -> ad
const CHANNELS = <?= json_encode($deviceChannels, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>; // cihaz_id -> [{id,ad}]

// Ayarlar (localStorage)
const SETTINGS_KEY='alarmSettingsV1';
const set_offline = document.getElementById('set_offline');
const set_high = document.getElementById('set_high');
const set_critical = document.getElementById('set_critical');
const set_low = document.getElementById('set_low');
const set_spike = document.getElementById('set_spike');
function loadSettings(){
  let s={offline_sec:300, high_pct:90, critical_pct:95, low_pct:10, spike_pct:50};
  try{const t=JSON.parse(localStorage.getItem(SETTINGS_KEY)||'{}'); Object.assign(s,t);}catch(e){}
  return s;
}
function saveSettings(s){ localStorage.setItem(SETTINGS_KEY,JSON.stringify(s)); }
function applyInputsFromSettings(){ const s=loadSettings();
  set_offline.value = s.offline_sec; set_high.value=s.high_pct; set_critical.value=s.critical_pct; set_low.value=s.low_pct; set_spike.value=s.spike_pct; }
function applySettingsFromInputs(){ const s={ offline_sec:parseInt(set_offline.value||'300'), high_pct:parseFloat(set_high.value||'90'), critical_pct:parseFloat(set_critical.value||'95'), low_pct:parseFloat(set_low.value||'10'), spike_pct:parseFloat(set_spike.value||'50') }; saveSettings(s); return s; }
applyInputsFromSettings();
document.getElementById('saveSettings').addEventListener('click',()=>{applySettingsFromInputs(); render();});

// Ack (onay) — localStorage ile basit bastırma
const ACK_KEY='alarmAckV1'; // { key -> timestamp }
function loadAcks(){ try{return JSON.parse(localStorage.getItem(ACK_KEY)||'{}');}catch(e){return{};} }
function saveAcks(m){ localStorage.setItem(ACK_KEY,JSON.stringify(m)); }
function ackKey(cid,aid,rule){ return cid+'|'+aid+'|'+rule; }
function doAck(cid,aid,rule){ const map=loadAcks(); map[ackKey(cid,aid,rule)]=Date.now(); saveAcks(map); render(); }
function clearStaleAcks(activeKeys){ const map=loadAcks(); let changed=false; for(const k of Object.keys(map)){ if(!activeKeys.has(k)){ delete map[k]; changed=true; } } if(changed) saveAcks(map); }

// Yardımcılar
function rangeFor(name){ const n=(name||'').toLowerCase();
  if(n.includes('gerilim')||n.includes('volt')) return [0,400,'V'];
  if(n.includes('akım')||n.includes('current')||n.includes('amper')) return [0,100,'A'];
  if(n.includes('güç')||n.includes('power')||n.includes('kw')||n.includes('watt')) return [0,10000,'W'];
  if(n.includes('frekans')||n.includes('hz')) return [0,100,'Hz'];
  if(n.includes('sıcak')||n.includes('temp')||n.includes('°c')) return [-20,120,'°C'];
  return [0,100,'']; }

function classify(row, settings){
  const out={ active:false, severity:'info', reasons:[], ruleKeys:[], unit:'', pct:null };
  const cid=row.cihaz_id, aid=row.cihaz_adres_id, val=Number(row.deger), age=Number(row.age_sec||0);
  const addrName = ADDRS[aid] || ('Adres #'+aid);
  const dev = DEVICES[cid];
  const label = (dev? dev.label : ('Cihaz #'+cid)) + ' / ' + addrName;
  // Offline
  if(isFinite(age) && age > settings.offline_sec){ out.active=true; out.severity='critical'; out.reasons.push('Veri gecikmesi: '+age+' sn'); out.ruleKeys.push('offline'); }
  // Range & yüzdelik
  const [min,max,unit]=rangeFor(addrName + ' ' + (dev? dev.label: ''));
  out.unit=unit;
  const rng=max-min; let pct=null; if(rng>0){ pct=(val-min)/rng; if(pct<0)pct=0; if(pct>1)pct=1; out.pct=pct; }
  const p = pct!==null? pct*100 : null;
  if(p!==null){
    if(p >= settings.critical_pct){ out.active=true; if(out.severity!=='critical') out.severity='high'; out.severity='critical'; out.reasons.push('Kritik yüksek: '+p.toFixed(1)+'%'); out.ruleKeys.push('pct_critical'); }
    else if(p >= settings.high_pct){ out.active=true; if(out.severity!=='critical') out.severity='high'; out.reasons.push('Yüksek: '+p.toFixed(1)+'%'); out.ruleKeys.push('pct_high'); }
    if(p <= settings.low_pct){ out.active=true; if(out.severity!=='critical') out.severity='warning'; out.reasons.push('Düşük: '+p.toFixed(1)+'%'); out.ruleKeys.push('pct_low'); }
  }
  // Ani değişim (prev_deger / diff_pct api'de var)
  const diff_pct = (row.diff_pct==null)? null : Number(row.diff_pct);
  if(diff_pct!==null && Math.abs(diff_pct) >= settings.spike_pct){ out.active=true; if(out.severity==='info') out.severity='warning'; out.reasons.push('Ani değişim: '+diff_pct.toFixed(1)+'%'); out.ruleKeys.push('spike'); }

  // ACK kontrolü
  if(out.active){
    const acks=loadAcks();
    // Eğer tüm ruleKeys ack'lenmişse, bastır.
    let suppressed=true; if(out.ruleKeys.length===0) suppressed=false; else {
      for(const rk of out.ruleKeys){ if(!acks[ackKey(cid,aid,rk)]) { suppressed=false; break; } }
    }
    if(suppressed){ out.suppressed=true; }
  }
  out.label=label; out.addrName=addrName; out.dev=dev; out.val=val; out.age=age; out.time=row.kayit_zamani; out.cid=cid; out.aid=aid;
  return out;
}

let lastData=[]; let lastRender=0;
async function fetchLatest(){
  try{
    const r = await fetch('api_latest.php',{cache:'no-store'});
    if(!r.ok) throw new Error(r.status+'');
    const data = await r.json();
    lastData = Array.isArray(data)? data : [];
    document.getElementById('lastUpdate').textContent = 'Güncelleme: '+ new Date().toLocaleString();
  }catch(e){ console.error('API hata', e); }
}

function render(){
  const s = loadSettings();
  const tbody = document.querySelector('#alarmTable tbody');
  tbody.innerHTML='';
  let rows=[]; const activeKeys=new Set(); const sevCounts={critical:0,high:0,warning:0,info:0,low:0};
  for(const r of lastData){
    const c = classify(r,s);
    if(!c.active) continue;
    const acked = c.suppressed===true;
    c.acked=acked;
    if(!acked){
      // diff bilgilerini kopyala (tabloda kullanmak için)
      c.diff = (r.diff==null ? null : Number(r.diff));
      c.diff_pct = (r.diff_pct==null ? null : Number(r.diff_pct));
      rows.push(c);
      for(const rk of c.ruleKeys){ activeKeys.add(ackKey(c.cid,c.aid,rk)); }
      sevCounts[c.severity]=(sevCounts[c.severity]||0)+1;
    }
  }
  clearStaleAcks(activeKeys);
  // Sırala: severity -> time desc
  const sevRank={critical:5, high:4, warning:3, info:2, low:1};
  rows.sort((a,b)=> (sevRank[b.severity]-sevRank[a.severity]) || ( (b.time||'').localeCompare(a.time||'') ) );
  for(const c of rows){
    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td><span class="severity sev-${c.severity}">${c.severity.toUpperCase()}</span></td>
      <td>${(c.reasons[0]||'')}</td>
      <td>${(c.dev? (c.dev.label):('Cihaz #'+c.cid))}</td>
      <td>${c.addrName}</td>
      <td>${Number.isFinite(c.val)? c.val.toFixed(2): ''} <span style="opacity:.7">${c.unit||''}</span>${c.pct!=null? ' <span style="opacity:.65">('+ (c.pct*100).toFixed(1)+'%)</span>':''}</td>
  <td>${(c.diff!=null? ( (c.diff>0?'+':'')+Number(c.diff).toFixed(2) ): '-')}${(c.diff_pct!=null? ' <span style=\"opacity:.65\">('+(c.diff_pct>0?'+':'')+Number(c.diff_pct).toFixed(1)+'%)</span>':'' )}</td>
      <td>${c.time||''}</td>
      <td>${(Number.isFinite(c.age)? c.age+' sn':'')}</td>
      <td>
  ${c.ruleKeys.map(rk=>`<button class=\"ack\" data-ack=\"${c.cid}|${c.aid}|${rk}\">ACK: ${rk}</button>`).join(' ')}
      </td>`;
    tbody.appendChild(tr);
  }
  // İstatistikler
  const stats=document.getElementById('sevStats'); stats.innerHTML='';
  const make = (cls,txt,count)=>{ const d=document.createElement('div'); d.className='stat'; d.innerHTML=`<span class="severity sev-${cls}">${txt}</span><b>${count}</b>`; stats.appendChild(d); };
  make('critical','Kritik', sevCounts.critical||0);
  make('high','Yüksek', sevCounts.high||0);
  make('warning','Uyarı', sevCounts.warning||0);
  // Genel sayaç
  document.getElementById('counts').textContent = 'Aktif: '+rows.length;
  // Ack butonları
  document.querySelectorAll('.ack').forEach(btn=>{ btn.addEventListener('click',()=>{ const k=btn.getAttribute('data-ack'); if(!k) return; const [cid,aid,rule]=k.split('|'); doAck(parseInt(cid),parseInt(aid),rule); }); });
}

async function tick(){ await fetchLatest(); render(); }
tick(); setInterval(tick, 20000);

// İlk yüklemede ufak gecikmeli render (CSS yerleşmesi için)
setTimeout(render, 400);

// --- Kural Ekleme UI ---
const ruleTypeSel = document.getElementById('f_rule');
const boxThr = document.getElementById('f_thresholds');
const boxRate = document.getElementById('f_rateBox');
ruleTypeSel.addEventListener('change',()=>{
    const v=ruleTypeSel.value; boxThr.style.display = (v==='threshold' || v==='esik'?'flex':'none'); boxRate.style.display=(v==='rate' || v==='oran'?'flex':'none');
});

// Mail öneri datalist'i
const mailInput = document.getElementById('mailInput');
const mailPills = document.getElementById('mailPills');
const mailSuggest = document.getElementById('mailSuggest');
let selectedEmails=[];
function isValidEmail(e){
    // Basit kontrol (RFC değil, pratik)
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
}
function addEmailPill(email){
    email=(email||'').trim(); if(!email) return; if(!isValidEmail(email)) return; if(selectedEmails.includes(email)) return;
    selectedEmails.push(email);
    const pill=document.createElement('span'); pill.className='badge'; pill.textContent=email; pill.style.cursor='pointer'; pill.title='Kaldırmak için tıklayın';
    pill.addEventListener('click',()=>{ selectedEmails=selectedEmails.filter(e=>e!==email); pill.remove(); });
    mailPills.appendChild(pill);
}
function addEmailsFromString(txt){
    const parts = (txt||'').split(/[;,\s]+/).map(s=>s.trim()).filter(Boolean);
    for(const p of parts){ addEmailPill(p); }
}
mailInput.addEventListener('keydown',async (e)=>{
    if(e.key==='Enter'){
    e.preventDefault(); const v=mailInput.value.trim(); if(v){ addEmailsFromString(v); mailInput.value=''; }
    }
});
// Enter'a basmadan fokus kaybolursa da ekle
mailInput.addEventListener('blur',()=>{ const v=mailInput.value.trim(); if(v){ addEmailsFromString(v); mailInput.value=''; } });
async function refreshMailSuggest(q=''){
    try{ const r=await fetch('api_alarm.php?act=emails&q='+encodeURIComponent(q)); const j=await r.json(); mailSuggest.innerHTML=''; if(j.ok){ j.items.forEach(it=>{ const opt=document.createElement('option'); opt.value=it.email; mailSuggest.appendChild(opt); }); } }catch(e){}
}
mailInput.addEventListener('input',()=>{ refreshMailSuggest(mailInput.value); });
refreshMailSuggest('');

// Kural kaydet
async function saveRule(){
    const rt = ruleTypeSel.value;
    // Kaydetmeden önce inputta bekleyen e-posta(lar) varsa pill’e al
    const pending = (mailInput.value||'').trim(); if(pending){ addEmailsFromString(pending); mailInput.value=''; }
    const payload={
        cihaz_id: parseInt(document.getElementById('f_cid').value||'0'),
        cihaz_adres_id: parseInt(document.getElementById('f_aid').value||'0'),
        rule_type: (rt==='threshold' ? 'esik' : (rt==='rate' ? 'oran' : rt)),
        threshold_min: document.getElementById('f_min').value,
        threshold_max: document.getElementById('f_max').value,
        rate_pct: document.getElementById('f_rate').value,
        direction: document.getElementById('f_dir').value,
        notify_all: parseInt(document.getElementById('f_vis').value||'1'),
        enabled: 1,
        note: document.getElementById('f_note').value,
        emails: selectedEmails,
    };
    const m=document.getElementById('ruleMsg');
    try{
        const r=await fetch('api_alarm.php?act=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const j=await r.json(); if(!j.ok){ throw new Error(j.error||'hata'); }
    m.textContent='Kaydedildi (ID '+j.id+') • e-posta kaydı: '+(j.emails_saved??0); m.style.color='#9ff';
        // Temizle
        selectedEmails=[]; mailPills.innerHTML='';
        listRules();
    }catch(e){ m.textContent='Hata: '+e.message; m.style.color='#f99'; }
}
document.getElementById('btnSaveRule').addEventListener('click',saveRule);

// Kuralları listele
async function listRules(){
    try{
        const r=await fetch('api_alarm.php?act=list'); const j=await r.json(); const tb=document.querySelector('#rulesTable tbody'); tb.innerHTML='';
        if(!j.ok) return;
        j.items.forEach(it=>{
            const tr=document.createElement('tr');
            const ruleTitle = (it.rule_type==='threshold'||it.rule_type==='esik')? 'Eşik':'Yüzde Oran';
            const dirTitle = (it.direction==='herikisi'?'Yukarı/Aşağı': (it.direction==='yukari'?'Yukarı': (it.direction==='asagi'?'Aşağı': it.direction)));
            const detail = (it.rule_type==='threshold'||it.rule_type==='esik') ? (`min=${it.threshold_min??''}, max=${it.threshold_max??''}`) : (`oran%=${it.rate_pct??''}, yön=${dirTitle}`);
            const vis = it.notify_all==1? 'Herkes' : 'Sadece Sahibi';
            const devLabel = (DEVICES[it.cihaz_id]?.label) || ('Cihaz #'+it.cihaz_id);
            const addrLabel = (ADDRS[it.cihaz_adres_id]) || ('Kanal #'+it.cihaz_adres_id);
            tr.innerHTML = `<td>${it.id}</td><td>${devLabel}</td><td>${addrLabel}</td><td>${ruleTitle}</td><td>${detail}</td><td>${vis}</td><td>${(it.emails||[]).join(', ')}</td><td>${it.created_by}</td><td>${it.created_at}</td><td><button data-del="${it.id}" class="ack">Sil</button></td>`;
            tb.appendChild(tr);
        });
        tb.querySelectorAll('button[data-del]').forEach(btn=>btn.addEventListener('click',async ()=>{
            const id=parseInt(btn.getAttribute('data-del')); if(!confirm('Silinsin mi?')) return;
            try{ const r=await fetch('api_alarm.php?act=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}); const j=await r.json(); if(j.ok) listRules(); else alert('Silinemedi'); }catch(e){ alert('Hata'); }
        }));
    }catch(e){ /* sessiz */ }
}
listRules();

// Analizör/Kanal dropdownları
const fCid = document.getElementById('f_cid');
const fAid = document.getElementById('f_aid');
function populateDevices(){
    const ids = Object.keys(DEVICES).map(Number).sort((a,b)=>{
        const la=(DEVICES[a]?.label||'').toLocaleLowerCase('tr');
        const lb=(DEVICES[b]?.label||'').toLocaleLowerCase('tr');
        return la.localeCompare(lb,'tr');
    });
    fCid.innerHTML = '<option value="">— Seçiniz —</option>' + ids.map(id=>`<option value="${id}">${DEVICES[id]?.label||('Cihaz #'+id)}</option>`).join('');
}
function populateChannelsForDevice(cid){
    const list = CHANNELS[cid] || [];
    if(list.length===0){ fAid.innerHTML='<option value="">— Bu analizör için kanal bulunamadı —</option>'; return; }
    fAid.innerHTML = '<option value="">— Seçiniz —</option>' + list.map(x=>`<option value="${x.id}">${x.ad}</option>`).join('');
}
populateDevices();
fCid.addEventListener('change',()=>{
    const cid = parseInt(fCid.value||'0');
    if(cid>0) populateChannelsForDevice(cid); else fAid.innerHTML='<option value="">— Önce analizör seçin —</option>';
});
</script>
</body>
</html>
