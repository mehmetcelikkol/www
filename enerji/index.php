<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
error_reporting(E_ALL); ini_set('display_errors','1');
date_default_timezone_set('Europe/Istanbul');

/* Yardımcılar */
if(!function_exists('h')){
  function h($x): string { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); }
}
if(!function_exists('parse_ts_tr')){
  function parse_ts_tr(?string $s): int {
    if(!$s) return 0;
    $s = trim((string)$s);
    $tz = new DateTimeZone('Europe/Istanbul');
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $s, $tz);
    if($dt instanceof DateTime) return $dt->getTimestamp();
    $t = strtotime($s);
    return $t ? (int)$t : 0;
  }
}
if(!function_exists('ago_tr')){
  function ago_tr(int $ts): string {
    if($ts<=0) return '-';
    $diff = time() - $ts;
    if($diff < 0 && $diff > -120) return 'az önce';
    if($diff < 0) $diff = 0;
    if($diff < 60) return $diff.' sn';
    if($diff < 3600) return floor($diff/60).' dk';
    if($diff < 86400) return floor($diff/3600).' sa';
    return floor($diff/86400).' g';
  }
}
if(!function_exists('fmt_num')){
  function fmt_num(float $v, int $d=2): string {
    $s = number_format($v, $d, '.', '');
    return rtrim(rtrim($s, '0'), '.');
  }
}

/* Config okuma (mevcut uygulamanızdaki gibi) */
function load_config(string $path): ?array {
  if(!is_file($path)) return null;
  $xml=@simplexml_load_file($path); if(!$xml||!isset($xml->connectionStrings)) return null;
  foreach($xml->connectionStrings->add as $add){
    if((string)$add['name']==='MySqlConnection'){
      $connStr=(string)$add['connectionString']; $out=[];
      foreach(explode(';',$connStr) as $p){
        $kv=explode('=',$p,2); if(count($kv)==2) $out[trim($kv[0])]=trim($kv[1]);
      }
      return $out;
    }
  }
  return null;
}
$CONFIG_PATH_CANDIDATES=[
  'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config'
];
$config=null; foreach($CONFIG_PATH_CANDIDATES as $p){ if($c=load_config($p)){ $config=$c; break; } }

$errors=[]; $gauges=[]; $devicesSummary=[];

if(!$config){ $errors[]='Config bulunamadı.'; }
else {
  foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($config[$k])) $errors[]='Eksik config anahtarı: '.$k; }
  if(!$errors){
    $db=@new mysqli($config['Server'],$config['Uid'],$config['Pwd'],$config['Database']);
    if($db->connect_error){ $errors[]='DB bağlanamadı: '.$db->connect_error; }
    else {
      $db->set_charset('utf8');
      // Her cihaz_adres için tek son kayıt + unit bilgisi
      $sql="SELECT o.cihaz_id,o.cihaz_adres_id,o.deger,o.kayit_zamani,
                   c.cihaz_adi,c.konum,
                   ca.ad AS adres_ad, ca.unit AS adres_unit
            FROM olcumler o
            JOIN (SELECT cihaz_id,cihaz_adres_id,MAX(kayit_zamani) mx FROM olcumler GROUP BY cihaz_id,cihaz_adres_id) t
              ON t.cihaz_id=o.cihaz_id AND t.cihaz_adres_id=o.cihaz_adres_id AND t.mx=o.kayit_zamani
            JOIN cihazlar c ON c.id=o.cihaz_id
            JOIN cihaz_adresleri ca ON ca.id=o.cihaz_adres_id
            ORDER BY c.konum,c.cihaz_adi,ca.ad";
      if($res=$db->query($sql)){
        while($r=$res->fetch_assoc()){ $gauges[]=$r; }
        $res->close();

        // Cihaz bazlı özet
        foreach($gauges as $g){
          $cid = (int)$g['cihaz_id'];
          $ts  = parse_ts_tr((string)$g['kayit_zamani']);
          if(!isset($devicesSummary[$cid])){
            $devicesSummary[$cid]=[
              'id'=>$cid,
              'cihaz_adi'=>(string)($g['cihaz_adi']??''),
              'konum'=>(string)($g['konum']??''),
              'last_seen_ts'=>$ts?:0,
              'last_seen' => (string)($g['kayit_zamani'] ?? ''), // FIX: => kullan
              'addr_count'=>1,
              'marka'=>null,'model'=>null,'seri_no'=>null,'ip'=>null
            ];
          }else{
            $devicesSummary[$cid]['addr_count']++;
            if($ts && $ts > $devicesSummary[$cid]['last_seen_ts']){
              $devicesSummary[$cid]['last_seen_ts']=$ts;
              $devicesSummary[$cid]['last_seen']=(string)($g['kayit_zamani']??'');
            }
          }
        }

        // cihazlar tablosunda varsa marka/model/seri_no/ip'yi ekle
        if(!empty($devicesSummary)){
          $want=['marka','model','seri_no','ip']; $have=[];
          if($resCols=$db->query("SHOW COLUMNS FROM cihazlar")){
            while($c=$resCols->fetch_assoc()){ $f=(string)$c['Field']; if(in_array($f,$want,true)) $have[]=$f; }
            $resCols->close();
          }
          if($have){
            $ids = implode(',', array_map('intval', array_keys($devicesSummary)));
            // FIX: PHP 7.3 uyumu (arrow function yerine klasik)
            $colList = implode(',', array_map(function($x){ return '`'.$x.'`'; }, $have));
            if($resInfo=$db->query("SELECT id,$colList FROM cihazlar WHERE id IN ($ids)")){
              while($row=$resInfo->fetch_assoc()){
                $cid=(int)$row['id'];
                foreach($have as $k){ $devicesSummary[$cid][$k]=$row[$k]??null; }
              }
              $resInfo->close();
            }
          }
        }
      } else {
        $errors[]='Sorgu hatası: '.$db->error;
      }
      $db->close();
    }
  }
}

/* KPI hesapları */
$deviceCount = count($devicesSummary);
$onlineCount = 0;
foreach($devicesSummary as $d){
  $ts=(int)($d['last_seen_ts']??0);
  if($ts>0 && (time()-$ts)<=3600) $onlineCount++;
}
$kpiTotals = [
  'p'=>['match'=>'toplam aktif güç','name'=>'Toplam Aktif Güç','sum'=>0.0,'unit'=>null],
  'q'=>['match'=>'toplam reaktif güç','name'=>'Toplam Reaktif Güç','sum'=>0.0,'unit'=>null],
  's'=>['match'=>'toplam görünür güç','name'=>'Toplam Görünür Güç','sum'=>0.0,'unit'=>null],
];
foreach($gauges as $g){
  $nm = mb_strtolower((string)($g['adres_ad']??''),'UTF-8');
  foreach($kpiTotals as &$t){
    if($nm === $t['match']){
      $t['sum'] += (float)$g['deger'];
      if(!$t['unit'] && !empty($g['adres_unit'])) $t['unit']=(string)$g['adres_unit'];
    }
  } unset($t);
}
// EKLENDİ: Cihaz bazında Görünür Güç toplamları
$sPerDevice = [];
foreach($gauges as $g){
  $nm = mb_strtolower((string)($g['adres_ad']??''),'UTF-8');
  if($nm === 'toplam görünür güç'){
    $cid = (int)$g['cihaz_id'];
    $sPerDevice[$cid] = ($sPerDevice[$cid] ?? 0) + (float)$g['deger'];
  }
}
$sByDevice = [];
foreach($sPerDevice as $cid=>$sum){
  $konum = (string)($devicesSummary[$cid]['konum'] ?? '');
  $cadi  = (string)($devicesSummary[$cid]['cihaz_adi'] ?? '');
  $label = trim(($konum!==''?$konum:'-').' • '.($cadi!==''?$cadi:('Cihaz '.$cid))." (ID: $cid)");
  $sByDevice[] = ['cid'=>$cid,'name'=>$label,'value'=>$sum];
}
// Büyükten küçüğe sırala
usort($sByDevice, function($a,$b){ return $b['value'] <=> $a['value']; });

// Konuma göre Görünür Güç toplamları
$locTotals = [];
foreach($gauges as $g){
  $nm = mb_strtolower((string)($g['adres_ad']??''),'UTF-8');
  if($nm === 'toplam görünür güç'){
    $loc = (string)($g['konum'] ?? '-');
    $locTotals[$loc] = ($locTotals[$loc] ?? 0) + (float)$g['deger'];
  }
}
arsort($locTotals);
$locNames = array_keys($locTotals);
$locValues = array_values($locTotals);

?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enerji İzleme</title>
  <?php
  // YENİ: Projedeki mevcut CSS’i yükle (ilk bulunanı)
  $cssCand = [
    __DIR__.'/assets/app.css'   => 'assets/app.css',
    __DIR__.'/assets/style.css' => 'assets/style.css',
    __DIR__.'/assets/styles.css'=> 'assets/styles.css',
    __DIR__.'/assets/css/app.css'=> 'assets/css/app.css',
  ];
  foreach($cssCand as $fs=>$href){
    if(is_file($fs)){ echo '<link rel="stylesheet" href="'.h($href).'">'; break; }
  }
  ?>
  <style>
    :root{color-scheme:dark light}
    body{margin:0;background:#0b1a27;color:#e8f1fb;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
    main{max-width:1200px;margin:0 auto;padding:22px}
    h1{font-size:18px;margin:0 0 8px}
    /* Şeritler */
    .anlzr-bar{
      display:flex;flex-wrap:wrap;gap:12px;margin:12px 0;padding:8px 10px;border-radius:14px;
      background:rgba(255,255,255,.05);justify-content:center
    }
    .anlzr-bar.justify-between{justify-content:space-between}
    .anlzr-chip{min-width:220px;max-width:280px;flex:0 1 260px;background:#13293c;border:1px solid #2c445c;border-radius:12px;padding:8px 10px;color:#e8f1fb}
    .anlzr-chip .t{font-size:12px;font-weight:600;line-height:1.2;margin:0 0 2px}
    .anlzr-chip .s{font-size:10.5px;opacity:.9;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .anlzr-chip .m{font-size:10px;opacity:.6;margin-top:4px}
    /* KPI chip */
    .kpi-chip .val{font-size:20px;font-weight:700;letter-spacing:.4px;display:flex;align-items:baseline;gap:6px;color:#e8f1fb;margin-top:2px}
    .kpi-chip .sub{font-size:10px;opacity:.6;margin-top:2px}

    /* Grafik chip’leri */
    .chart-chip{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:0 1 420px}
    .chart-chip .t{margin:2px 0 10px;font-size:12px;font-weight:600}
    .chart-box{
      width:360px;
      height:360px;
      max-width:100%;
    }
    @media (max-width:1100px){
      .anlzr-bar.charts-bar .chart-left,
      .anlzr-bar.charts-bar .chart-mid{
        flex:0 0 320px;
      }
    }
    @media (max-width:900px){
      .anlzr-bar.charts-bar .chart-left,
      .anlzr-bar.charts-bar .chart-mid,
      .anlzr-bar.charts-bar .chart-wide{
        flex:1 1 100%;
      }
      .anlzr-bar.charts-bar .chart-box{width:100%;height:300px}
    }
    @media (max-width:640px){
      .chart-chip{flex:0 1 280px}
      .chart-box{width:240px;height:240px}
    }
    /* Durum pill */
    .status-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 6px;border-radius:9999px;font-size:10px;font-weight:600;border:1px solid transparent}
    .status-pill .dot{width:7px;height:7px;border-radius:50%}
    .status-online{background:rgba(16,185,129,.12);color:#34d399;border-color:rgba(16,185,129,.35)}
    .status-online .dot{background:#34d399}
    .status-offline{background:rgba(239,68,68,.10);color:#fda4a4;border-color:rgba(239,68,68,.35)}
    .status-offline .dot{background:#ef4444}
    /* Tablo */
    .table-wrap{margin-top:10px;background:#0f2232;border:1px solid #2c445c;border-radius:14px;overflow:auto}
    .table-wrap table{width:100%;border-collapse:collapse;font-size:12px}
    .table-wrap thead th{position:sticky;top:0;background:#0f2232;color:#9dc7ee;text-align:left;padding:10px;border-bottom:1px solid #2c445c}
    .table-wrap tbody td{padding:8px 10px;border-bottom:1px solid #203448;color:#e8f1fb}
    .table-wrap tbody tr:hover{background:#13293c}
    .row-offline{opacity:.75}
    .badge{display:inline-flex;align-items:center;gap:6px;padding:2px 6px;border-radius:9999px;font-size:10px;font-weight:600;border:1px solid transparent}
    .badge .dot{width:7px;height:7px;border-radius:50%}
    .badge.online{background:rgba(16,185,129,.12);color:#34d399;border-color:rgba(16,185,129,.35)}
    .badge.online .dot{background:#34d399}
    .badge.offline{background:rgba(239,68,68,.10);color:#fda4a4;border-color:rgba(239,68,68,.35)}
    .badge.offline .dot{background:#ef4444}
    .filter-row{display:flex;gap:8px;align-items:center;padding:8px 10px;background:rgba(255,255,255,.04);border-bottom:1px solid #203448}
    .filter-row input{padding:7px 10px;border-radius:10px;border:1px solid #2c445c;background:#13293c;color:#e8f1fb;font-size:12px;min-width:260px}
    .filter-row input:focus{outline:2px solid #3d8bff}

    .anlzr-bar.charts-bar{justify-content:flex-start !important}

    /* Soldaki iki grafik sabit genişlik */
    .anlzr-bar.charts-bar .chart-left,
    .anlzr-bar.charts-bar .chart-mid{
      flex:0 0 380px;
      max-width:380px;          /* EKLENDİ: sabitle */
    }
    /* EKLENDİ: Yer tutucu da aynı hizada */
    .anlzr-bar.charts-bar .chart-ph{
      flex:0 0 380px;
      max-width:380px;
    }

    /* Sağdaki grafik tüm boşluğu alsın */
    .anlzr-bar.charts-bar .chart-wide{
      flex:1 1 auto;
      min-width:420px;
      max-width:none;            /* ÖNEMLİ: 280px sınırını kaldır */
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
    }

    /* İçteki kutu tam genişlik */
    .anlzr-bar.charts-bar .chart-wide .chart-box{
      width:100%;
      height:420px;
      max-width:100%;
    }

    /* Genel .anlzr-chip max-width’i bu şeritte kaldır (genişlemesi gerekirse) */
    .anlzr-bar.charts-bar .anlzr-chip{max-width:none}

    /* Responsive uyarlamalar */
    @media (max-width:1100px){
      .anlzr-bar.charts-bar .chart-left,
      .anlzr-bar.charts-bar .chart-mid{
        flex:0 0 320px;
        max-width:320px;
      }
    }
    @media (max-width:900px){
      .anlzr-bar.charts-bar .chart-left,
      .anlzr-bar.charts-bar .chart-mid,
      .anlzr-bar.charts-bar .chart-wide{
        flex:1 1 100%;
        min-width:0;
      }
      .anlzr-bar.charts-bar .chart-box{width:100%;height:300px}
    }

    /* ÜST ŞERİT: 3 donut sabit genişlikte yan yana */
    .anlzr-bar.charts-bar-top{justify-content:flex-start !important}
    .anlzr-bar.charts-bar-top .anlzr-chip{flex:0 0 360px;max-width:360px}
    @media (max-width:1100px){
      .anlzr-bar.charts-bar-top .anlzr-chip{flex:0 0 320px;max-width:320px}
    }
    @media (max-width:900px){
      .anlzr-bar.charts-bar-top .anlzr-chip{flex:1 1 100%;max-width:none}
      .anlzr-bar.charts-bar-top .chart-box{width:100%;height:300px}
    }

    /* ALT ŞERİT: Bar grafik tam genişlik */
    .anlzr-bar.charts-bar-bottom{justify-content:flex-start !important}
    .anlzr-bar.charts-bar-bottom .anlzr-chip{flex:1 1 100%;max-width:none}
    .anlzr-bar.charts-bar-bottom .chart-box{width:100%;height:420px;max-width:100%}
    @media (max-width:900px){
      .anlzr-bar.charts-bar-bottom .chart-box{height:300px}
    }

    /* filepath: c:\wamp64\www\enerji\index.php */
    /* ÜST ŞERİT: tüm donutlar eşit esnesin */
    .anlzr-bar.charts-bar-top{
      justify-content:space-between !important;
      gap: 10px;                 /* aralığı artır/azalt: 6-16 arası deneyebilirsin */
    }
    .anlzr-bar.charts-bar-top .chart-box{
      max-width: 420px;          /* donut çapını büyüt/küçült (ör. 360/420/480) */
      aspect-ratio: 1;
    }
    @media (max-width:900px){
      .anlzr-bar.charts-bar-top .anlzr-chip{
        flex:1 1 100%;
      }
      .anlzr-bar.charts-bar-top .chart-box{
        max-width:none;
        aspect-ratio:1;
      }
    }
  </style>
</head>
<body>
<?php
// Navbar: önce topnavbar.php, yoksa topnav.php
$navCandidates = [__DIR__.'/partials/topnavbar.php', __DIR__.'/partials/topnav.php'];
foreach ($navCandidates as $nav) {
    if (is_file($nav)) { require $nav; break; }
}
?>
<main>
  <h1>Enerji İzleme</h1>

  <?php if(!empty($devicesSummary)): ?>
  <!-- Analizör Şeridi -->
  <div class="anlzr-bar" title="Analizör Bilgisi">
    <?php foreach($devicesSummary as $d):
      $ts = (int)($d['last_seen_ts'] ?? 0);
      $ago = ago_tr($ts);
      $mk = trim((string)($d['marka'] ?? ''));
      $md = trim((string)($d['model'] ?? ''));
      $mm = trim($mk.($mk&&$md?' ':'').$md);
      $isOnline = ($ts>0 && (time()-$ts) <= 3600);
    ?>
    <div class="anlzr-chip">
      <div class="t"><?= h($d['konum'] ?: '-') ?></div>
      <div class="s">
        <?= h($d['cihaz_adi'] ?: ('Cihaz '.$d['id'])) ?> (ID: <?= (int)$d['id'] ?>)
        <span class="status-pill <?= $isOnline ? 'status-online':'status-offline' ?>"><span class="dot"></span><?= $isOnline?'Online':'Offline' ?></span>
      </div>
      <div class="m">
        <!-- FIX: Güvenli erişim (undefined uyarısını önle) -->
        Son erişim: <?= h($d['last_seen'] ?? '-') ?> (<?= h($ago) ?> önce)
        • Adres: <?= (int)$d['addr_count'] ?>
        <?php if($mm!==''): ?> • <?= h($mm) ?><?php endif; ?>
        <?php if(!empty($d['seri_no'])): ?> • SN: <?= h((string)$d['seri_no']) ?><?php endif; ?>
        <?php if(!empty($d['ip'])): ?> • IP: <?= h((string)$d['ip']) ?><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- KPI Şeridi -->
  <div class="anlzr-bar" title="KPI">
    <div class="anlzr-chip kpi-chip">
      <div class="t">Cihaz Durumu</div>
      <div class="val">
        <?= (int)$onlineCount ?> / <?= (int)$deviceCount ?>
        <span class="status-pill <?= $onlineCount>0 ? 'status-online':'status-offline' ?>"><span class="dot"></span><?= $onlineCount>0?'Online':'Offline' ?></span>
      </div>
      <div class="sub">Son 1 saatte veri gelen cihaz</div>
    </div>
    <div class="anlzr-chip kpi-chip">
      <div class="t"><?= h($kpiTotals['p']['name']) ?></div>
      <div class="val"><?= fmt_num((float)$kpiTotals['p']['sum'],2) ?> <span class="g-unit"><?= h($kpiTotals['p']['unit'] ?? '') ?></span></div>
      <div class="sub">Son değerlerin toplamı</div>
    </div>
    <div class="anlzr-chip kpi-chip">
      <div class="t"><?= h($kpiTotals['q']['name']) ?></div>
      <div class="val"><?= fmt_num((float)$kpiTotals['q']['sum'],2) ?> <span class="g-unit"><?= h($kpiTotals['q']['unit'] ?? '') ?></span></div>
      <div class="sub">Son değerlerin toplamı</div>
    </div>
    <div class="anlzr-chip kpi-chip">
      <div class="t"><?= h($kpiTotals['s']['name']) ?></div>
      <div class="val"><?= fmt_num((float)$kpiTotals['s']['sum'],2) ?> <span class="g-unit"><?= h($kpiTotals['s']['unit'] ?? '') ?></span></div>
      <div class="sub">Son değerlerin toplamı</div>
    </div>
  </div>

  <!-- YENİ: Grafik Şeritleri -->
  <div class="anlzr-bar charts-bar-top" title="Grafikler">
    <div class="anlzr-chip chart-chip">
      <div class="t">Cihaz Online / Offline</div>
      <div id="chartDeviceStatus" class="chart-box"></div>
    </div>
    <div class="anlzr-chip chart-chip">
      <div class="t">Analizörlere Göre Görünür Güç</div>
      <div id="chartPowerMix" class="chart-box"></div>
    </div>
    <div class="anlzr-chip chart-chip">
      <div class="t">Yer Tutucu (Örnek)</div>
      <div id="chartPlaceholder" class="chart-box"></div>
    </div>
  </div>

  <div class="anlzr-bar charts-bar-bottom" title="Grafikler">
    <div class="anlzr-chip chart-chip">
      <div class="t">Konuma Göre Görünür Güç</div>
      <div id="chartByLocation" class="chart-box"></div>
    </div>
  </div>

  <!-- Son Ölçümler Tablosu -->
  <div class="table-wrap">
    <div class="filter-row">
      <input id="measureFilter" type="search" placeholder="Ara: konum, cihaz, seri, birim..." />
    </div>
    <table id="measureTable">
      <thead>
        <tr>
          <th style="min-width:160px">Konum</th>
          <th style="min-width:180px">Cihaz (ID)</th>
          <th style="min-width:200px">Seri</th>
          <th style="min-width:140px">Değer</th>
          <th style="min-width:140px">Zaman</th>
          <th style="min-width:100px">Durum</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($gauges as $g):
          $cid = (int)$g['cihaz_id'];
          $tsLast = (int)($devicesSummary[$cid]['last_seen_ts'] ?? 0);
          $isOnline = ($tsLast>0 && (time()-$tsLast) <= 3600);
          $rowCls = $isOnline ? '' : 'row-offline';
          $val = (float)$g['deger'];
          $unit = (string)($g['adres_unit'] ?? '');
          $konum = (string)($g['konum'] ?? '');
          $cadi  = (string)($g['cihaz_adi'] ?? '');
          $seri  = (string)($g['adres_ad'] ?? '');
          $time  = (string)($g['kayit_zamani'] ?? '');
        ?>
        <tr class="<?= $rowCls ?>">
          <td><?= h($konum) ?></td>
          <td><?= h($cadi) ?> (ID: <?= $cid ?>)</td>
          <td><?= h($seri) ?></td>
          <td><?= fmt_num($val, 3) ?> <?= h($unit) ?></td>
          <td><?= h($time) ?></td>
          <td><span class="badge <?= $isOnline?'online':'offline' ?>"><span class="dot"></span><?= $isOnline?'Online':'Offline' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if($errors): ?>
    <div style="margin-top:12px;background:#3b0d0d;color:#ffd5d5;border:1px solid #5a1a1a;border-radius:10px;padding:10px 12px">
      <strong>Hatalar:</strong>
      <ul style="margin:6px 0 0 16px;padding:0">
        <?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

</main>

<!-- JS: tek blok (Chart.js kaldırıldı, ECharts kullanılıyor) -->
<script>
(function(){
  function layoutBars(){
    const bars = document.querySelectorAll('.anlzr-bar');
    bars.forEach(bar=>{
      if(bar.classList.contains('charts-bar-top') || bar.classList.contains('charts-bar-bottom')){
        bar.classList.remove('justify-between');
        return;
      }
      const items = Array.from(bar.children).filter(el=>el.classList.contains('anlzr-chip'));
      if(items.length===0){ bar.classList.remove('justify-between'); return; }
      const cs=getComputedStyle(bar);
      const gap=parseFloat(cs.gap)||0, padL=parseFloat(cs.paddingLeft)||0, padR=parseFloat(cs.paddingRight)||0;
      const barW=bar.clientWidth - padL - padR;
      let total=0; items.forEach(el=> total += el.getBoundingClientRect().width);
      total += gap * Math.max(0, items.length-1);
      if(items.length>1 && total <= barW+0.5){ bar.classList.add('justify-between'); } else { bar.classList.remove('justify-between'); }
    });
  }
  window.addEventListener('resize', layoutBars);
  document.addEventListener('DOMContentLoaded', layoutBars);
  layoutBars();
})();

(function(){
  const input=document.getElementById('measureFilter');
  const tbody=document.querySelector('#measureTable tbody');
  if(!input||!tbody) return;
  const norm=s=> (s||'').toString().toLowerCase();
  input.addEventListener('input', ()=>{
    const q=norm(input.value);
    for(const tr of tbody.rows){
      tr.style.display = norm(tr.textContent).indexOf(q) >= 0 ? '' : 'none';
    }
  });
})();

// ECharts yükle (önce yerel, yoksa CDN)
(function loadEcharts(){
  if(window.echarts){ initEcharts(); return; }
  const localPaths = [
    'assets/echarts.min.js',
    'assets/js/echarts.min.js',
    'vendor/echarts/echarts.min.js'
  ];
  (function tryLoad(i){
    if(i >= localPaths.length){
      const cdn = document.createElement('script');
      cdn.src = 'https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js';
      cdn.onload = initEcharts;
      document.head.appendChild(cdn);
      return;
    }
    const s=document.createElement('script');
    s.src=localPaths[i];
    s.onload=initEcharts;
    s.onerror=()=>tryLoad(i+1);
    document.head.appendChild(s);
  })(0);
})();

function initEcharts(){
  if(!window.echarts) return;

  const onlineCount = <?= (int)$onlineCount ?>;
  const deviceCount = <?= (int)$deviceCount ?>;
  const offlineCount = Math.max(0, deviceCount - onlineCount);

  // EKLENDİ: cihaz bazında görünür güçler
  const sByDevice = <?= json_encode($sByDevice, JSON_UNESCAPED_UNICODE) ?>;

  const textColor = '#e8f1fb';

  // Online / Offline (değişmedi)
  const elStatus = document.getElementById('chartDeviceStatus');
  if(elStatus){
    const c = echarts.init(elStatus);
    c.setOption({
      backgroundColor: 'transparent',
      tooltip: { trigger:'item' },
      legend: { bottom: 0, textStyle:{ color:textColor } },
      series: [{
        name:'Cihaz Durumu',
        type:'pie',
        radius:['55%','80%'],
        label:{ color:textColor, formatter:'{b}\n{c}' },
        data:[
          {value:onlineCount,  name:'Online',  itemStyle:{color:'#34d399'}},
          {value:offlineCount, name:'Offline', itemStyle:{color:'#ef4444'}}
        ]
      }]
    });
    window.addEventListener('resize', ()=>c.resize());
  }

  // Güç Dağılımı
  const elMix = document.getElementById('chartPowerMix');
  if(elMix){
    const c = echarts.init(elMix);
    c.setOption({
      backgroundColor: 'transparent',
      tooltip: { trigger:'item', formatter: p => `${p.name}: ${p.value} (${p.percent}%)` },
      legend: {
        type:'scroll',
        bottom: 0,
        textStyle:{ color:textColor }
      },
      series: [{
        name:'Görünür Güç',
        type:'pie',
        radius:['55%','80%'],
        label:{ show:false, color:textColor },      // kalabalıkta etiketleri gizle
        labelLine:{ show:false },
        data: sByDevice
      }]
    });
    window.addEventListener('resize', ()=>c.resize());
  }

  // EKLENDİ: Konuma göre görünür güç
  const elLoc = document.getElementById('chartByLocation');
  if(elLoc){
    const names = <?= json_encode($locNames, JSON_UNESCAPED_UNICODE) ?>;
    const vals  = <?= json_encode($locValues) ?>;
    const c = echarts.init(elLoc);
    c.setOption({
      backgroundColor:'transparent',
      tooltip:{ trigger:'axis', axisPointer:{ type:'shadow' } },
      grid:{ left: 10, right: 10, top: 10, bottom: 30, containLabel: true },
      xAxis:{ type:'value', axisLabel:{ color:'#9dc7ee' }, splitLine:{ lineStyle:{ color:'#203448' } } },
      yAxis:{ type:'category', data:names, axisLabel:{ color:'#9dc7ee' } },
      series:[{
        type:'bar',
        data: vals,
        barWidth: 14,
        itemStyle:{ color:'#22c55e' }
      }]
    });
    window.addEventListener('resize', ()=>c.resize());
  }

  // EKLENDİ: Yer Tutucu (örnek donut)
  const elPh = document.getElementById('chartPlaceholder');
  if(elPh){
    const c = echarts.init(elPh);
    c.setOption({
      backgroundColor:'transparent',
      tooltip:{ trigger:'item' },
      legend: { top: 6, left: 'center', textStyle:{ color:textColor } },
      series:[{
        name:'Örnek',
        type:'pie',
        radius:['55%','80%'],
        label:{ color:textColor, formatter:'{b}\n{c}' },
        data:[
          {value:40, name:'A', itemStyle:{color:'#60a5fa'}},
          {value:25, name:'B', itemStyle:{color:'#f472b6'}},
          {value:20, name:'C', itemStyle:{color:'#f59e0b'}},
          {value:15, name:'D', itemStyle:{color:'#22c55e'}}
        ]
      }]
    });
    window.addEventListener('resize', ()=>c.resize());
  }
}

function forceChartResize(){
  if(!window.echarts) return;
  const ids=['chartDeviceStatus','chartPowerMix','chartByLocation','chartPlaceholder']; // EKLENDİ
  ids.forEach(id=>{
     const el=document.getElementById(id);
     if(!el) return;
     const inst=echarts.getInstanceByDom(el);
     if(inst) inst.resize();
  });
}
window.addEventListener('load', ()=>setTimeout(forceChartResize,150));
window.addEventListener('resize', ()=>forceChartResize());
</script>
</body>
</html>
