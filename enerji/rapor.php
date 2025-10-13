<?php
declare(strict_types=1);

// Basit yardımcılar
error_reporting(E_ALL);
ini_set('display_errors','1');
date_default_timezone_set('Europe/Istanbul');

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function fmt_hm(int|float $seconds): string {
    $m = (int)round($seconds / 60);
    $h = intdiv($m, 60);
    $mm = $m % 60;
    return sprintf('%d saat %02d dk', $h, $mm);
}
function fmt_num(float $n, int $dec = 2): string {
    return number_format($n, $dec, ',', '.');
}

// Config dosyasından MySQL bağlantı bilgilerini oku (operasyon_giris ile aynı kaynağı kullanır)
function get_mysql_config(string $config_path): array {
    $xml = @simplexml_load_file($config_path);
    if (!$xml) return [];
    $connStr = '';
    foreach ($xml->connectionStrings->add as $add) {
        if ((string)$add['name'] === 'MySqlConnection') {
            $connStr = (string)$add['connectionString'];
            break;
        }
    }
    $params = [];
    foreach (explode(';', $connStr) as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) === 2) $params[trim($kv[0])] = trim($kv[1]);
    }
    return $params;
}

// CONFIG
$CONFIG_PATH = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config';
// Varsayım: olcumler.deger = anlık güç (kW). Eğer enerji sayacı (kWh) ise POWER_IS_KW = false yapıp alttaki alternatif yolu kullanın.
const POWER_IS_KW = true;

// DB
$config = get_mysql_config($CONFIG_PATH);
if (!$config) { http_response_code(500); die('Veritabanı config dosyası okunamadı!'); }

$conn = @new mysqli($config['Server'] ?? '', $config['Uid'] ?? '', $config['Pwd'] ?? '', $config['Database'] ?? '');
if ($conn->connect_errno) { http_response_code(500); die('Veritabanı bağlantı hatası: '.$conn->connect_error); }
$conn->set_charset('utf8mb4');
@$conn->query("SET time_zone = '+03:00'");

// Operasyon listesini çek (operasyon_giris.php ile aynı tablo)
$ops = [];
$res = $conn->query("SELECT id, ad, baslangic, bitis, miktar, birim FROM enerji_operasyonlar ORDER BY COALESCE(bitis, '9999-12-31') DESC, baslangic DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) $ops[] = $r;
    $res->free();
}

// Seçili operasyon
$opId = isset($_GET['op']) ? (int)$_GET['op'] : 0;
$selected = null;
if ($opId > 0) {
    foreach ($ops as $o) { if ((int)$o['id'] === $opId) { $selected = $o; break; } }
}
if (!$selected && !empty($ops)) {
    // Varsayılan: en güncel açık operasyon; yoksa en güncel bitmiş
    foreach ($ops as $o) { if ($o['bitis'] === null || $o['bitis'] === '0000-00-00 00:00:00') { $selected = $o; break; } }
    if (!$selected) $selected = $ops[0];
}

$chartData = [];
$calc = [
    'durationSec' => 0,
    'energyKwh'   => 0.0,
    'avgKw'       => 0.0,
    'qty'         => null,
    'unit'        => '',
    'kwhPerUnit'  => null,
    'start'       => null,
    'end'         => null,
    'name'        => null,
    'isOpen'      => false,
    'points'      => 0,
    'notes'       => [],
];

if ($selected) {
    $name = (string)$selected['ad'];
    $startStr = (string)$selected['baslangic'];
    $endStr   = ($selected['bitis'] === null || $selected['bitis'] === '0000-00-00 00:00:00') ? null : (string)$selected['bitis'];
    $startTs = strtotime($startStr);
    $endTs   = $endStr ? strtotime($endStr) : time();
    if (!$startTs || !$endTs || $endTs <= $startTs) {
        $calc['notes'][] = 'Operasyon tarihleri geçersiz görünüyor.';
    } else {
        // Zaman serisi: tüm cihazların aynı anda loglanan anlık güç (kW) toplamı
        // Büyük veri riskine karşı; dilerseniz aralığı kısıtlayın.
        $stmt = $conn->prepare("
            SELECT o.kayit_zamani, SUM(o.deger) AS kw
            FROM olcumler o
            WHERE o.kayit_zamani >= ? AND o.kayit_zamani <= ?
            GROUP BY o.kayit_zamani
            ORDER BY o.kayit_zamani ASC
        ");
        $startSql = date('Y-m-d H:i:s', $startTs);
        $endSql   = date('Y-m-d H:i:s', $endTs);
        if ($stmt) {
            $stmt->bind_param('ss', $startSql, $endSql);
            if ($stmt->execute()) {
                $rs = $stmt->get_result();
                $rows = [];
                while ($row = $rs->fetch_assoc()) {
                    $t = strtotime((string)$row['kayit_zamani']);
                    $v = (float)$row['kw'];
                    if ($t && is_finite($v)) $rows[] = [$t, $v];
                }
                $rs->free();

                // Eğer ölçüm enerji sayacı (kWh) olsaydı:
                // if (!POWER_IS_KW) {
                //   // Bu durumda SUM(deger) kWh anlık sayaç toplamı, enerji = pozitif farkların toplamı
                //   $energy = 0.0;
                //   for ($i=1; $i<count($rows); $i++) {
                //       $diff = $rows[$i][1] - $rows[$i-1][1];
                //       if ($diff > 0) $energy += $diff;
                //   }
                //   $calc['energyKwh'] = $energy;
                //   $calc['durationSec'] = $endTs - $startTs;
                //   $calc['avgKw'] = $calc['durationSec'] > 0 ? $calc['energyKwh'] / ($calc['durationSec']/3600) : 0;
                // } else { ... } // Aşağıda kW entegrasyonu

                // kW entegrasyonu (trapez) -> kWh
                $energy = 0.0;
                for ($i=1; $i<count($rows); $i++) {
                    $t0 = $rows[$i-1][0]; $v0 = $rows[$i-1][1];
                    $t1 = $rows[$i][0];   $v1 = $rows[$i][1];
                    $dtH = ($t1 - $t0) / 3600.0;
                    if ($dtH > 0 && is_finite($v0) && is_finite($v1)) {
                        $energy += (($v0 + $v1) / 2.0) * $dtH;
                    }
                }

                $calc['energyKwh'] = $energy;
                $calc['durationSec'] = $endTs - $startTs;
                $calc['avgKw'] = $calc['durationSec'] > 0 ? ($energy / ($calc['durationSec']/3600.0)) : 0.0;
                $calc['points'] = count($rows);

                // Chart verisi
                foreach ($rows as [$t,$v]) {
                    $chartData[] = ['t'=>date('c',$t), 'y'=>round($v, 3)];
                }
            } else {
                $calc['notes'][] = 'Ölçüm sorgusu çalıştırılamadı: '.$stmt->error;
            }
            $stmt->close();
        } else {
            $calc['notes'][] = 'Ölçüm sorgusu hazırlanamadı: '.$conn->error;
        }
    }

    // Miktar / birim ve kWh/birim
    $qty  = isset($selected['miktar']) ? (float)$selected['miktar'] : 0.0;
    $unit = trim((string)($selected['birim'] ?? ''));
    $calc['qty'] = $qty ?: null;
    $calc['unit'] = $unit;
    if ($calc['qty'] && $calc['qty'] > 0) $calc['kwhPerUnit'] = $calc['energyKwh'] / $calc['qty'];

    $calc['start'] = $startStr;
    $calc['end']   = $endStr ?: 'devam ediyor';
    $calc['name']  = $name;
    $calc['isOpen']= ($endStr === null);
}

// Tema (operasyon_giris benzeri sade)
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Operasyon Raporu</title>
<style>
:root{
  --bg:#f8fafc; --panel:#ffffff; --muted:#475569; --text:#0f172a;
  --border:#e5e7eb; --border-2:#cbd5e1; --primary:#0ea5e9; --primary-2:#0284c7;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px/1.5 "Segoe UI",Roboto,Arial,sans-serif}
.container{max-width:1200px;margin:18px auto;padding:0 14px}
h1{font-size:20px;margin:0 0 10px}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:12px}
.row{display:flex;flex-wrap:wrap;gap:12px}
.col{flex:1 1 300px;min-width:280px}
label{font-weight:600;font-size:12px;color:var(--muted);display:block;margin-bottom:6px}
select,input,button{font:inherit}
select,input[type=datetime-local]{width:100%;padding:8px 10px;border:1px solid var(--border-2);border-radius:8px;background:#fff;color:var(--text)}
.btn{appearance:none;border:1px solid var(--border-2);background:#fff;color:var(--text);padding:8px 12px;border-radius:10px;cursor:pointer}
.btn:hover{background:#f1f5f9;border-color:var(--border)}
.btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-2);border-color:var(--primary-2)}
.btn-xs{font-size:12px;padding:5px 9px;border-radius:8px}
.toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.kv{display:grid;grid-template-columns:180px 1fr;gap:6px 12px}
.kv b{font-weight:600;color:var(--muted)}
.small{font-size:12px;color:#334155}
hr{border:none;border-top:1px solid var(--border);margin:12px 0}
.chart-wrap{background:#fff;border:1px solid var(--border);border-radius:12px;padding:10px}
.note{font-size:12px;color:#64748b}
.badge{display:inline-block;background:#e2e8f0;color:#0f172a;border:1px solid #cbd5e1;border-radius:999px;padding:3px 8px;font-size:12px}
</style>
</head>
<body>
<div class="container">
  <h1>Operasyon Raporu</h1>

  <div class="panel" style="margin-bottom:12px">
    <form method="get" class="row" onsubmit="return true">
      <div class="col">
        <label>Operasyon</label>
        <select name="op" onchange="this.form.submit()">
          <?php foreach ($ops as $o): ?>
            <?php
              $isOpen = ($o['bitis'] === null || $o['bitis'] === '0000-00-00 00:00:00');
              $lab = ($o['ad'] ?? 'Operasyon').' — '.($isOpen ? 'Açık' : 'Bitti');
              $lab .= ' | '.$o['baslangic'].($isOpen ? ' → şimdi' : ' → '.$o['bitis']);
            ?>
            <option value="<?= (int)$o['id'] ?>" <?= ($selected && (int)$selected['id']===(int)$o['id'])?'selected':'' ?>>
              <?= h($lab) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col" style="align-self:flex-end">
        <button type="submit" class="btn btn-primary">Raporu Göster</button>
        <button type="button" class="btn btn-xs" onclick="exportCSV()">CSV Dışa Aktar</button>
      </div>
    </form>
  </div>

  <?php if ($selected): ?>
    <div class="chart-wrap" style="margin-bottom:12px">
      <canvas id="opChart" height="120"></canvas>
      <div class="small" style="margin-top:6px">
        <span class="badge">Operasyon: <?= h($calc['name'] ?? 'Operasyon') ?></span>
        <span class="badge">Aralık: <?= h($calc['start'] ?? '') ?> → <?= h((string)$calc['end']) ?></span>
        <span class="badge">Nokta: <?= (int)$calc['points'] ?></span>
      </div>
    </div>

    <div class="panel">
      <div class="kv">
        <b>Operasyon</b><div><?= h($calc['name'] ?? '') ?></div>
        <b>Başlangıç</b><div><?= h($calc['start'] ?? '') ?></div>
        <b>Bitiş</b><div><?= h((string)$calc['end']) ?></div>
        <b>Süre</b><div><?= fmt_hm((int)$calc['durationSec']) ?></div>
        <b>Toplam Enerji (kWh)</b><div><?= fmt_num((float)$calc['energyKwh'], 2) ?></div>
        <b>Ortalama Güç (kW)</b><div><?= fmt_num((float)$calc['avgKw'], 2) ?></div>
        <b>Üretim Miktarı</b>
          <div>
            <?php if ($calc['qty'] !== null): ?>
              <?= fmt_num((float)$calc['qty'], 3) ?> <?= h($calc['unit'] ?: '') ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </div>
        <b>kWh / Birim</b>
          <div>
            <?php if ($calc['kwhPerUnit'] !== null): ?>
              <?= fmt_num((float)$calc['kwhPerUnit'], 4) ?> kWh/<?= h($calc['unit'] ?: 'birim') ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </div>
      </div>
      <?php if (!empty($calc['notes'])): ?>
        <hr>
        <div class="note"><?= h(implode(' | ', $calc['notes'])) ?></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="panel"><b>Operasyon bulunamadı.</b></div>
  <?php endif; ?>
</div>

<script>
(function loadChartJs(){
  // Chart.js -> date adapter sırası önemli
  const s1=document.createElement('script');
  s1.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
  s1.onload=()=>{
    const s2=document.createElement('script');
    s2.src='https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3';
    s2.onload=renderChart;
    s2.onerror=renderChart; // adapter yüklenemezse yine de deneyelim
    document.head.appendChild(s2);
  };
  s1.onerror=renderChart;
  document.head.appendChild(s1);
})();

const DATA = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>;

function renderChart(){
  if (typeof Chart === 'undefined') { console.warn('Chart.js yüklenemedi'); return; }
  const el = document.getElementById('opChart');
  if (!el) return;

  // DATA: [{t: ISO, y: number}] -> Chart.js için {x,y}
  const series = (DATA||[]).map(p=>({ x: p.t, y: p.y }));

  // Boş veri durumunda kullanıcıya bilgi ver
  if (series.length === 0) {
    const msg = document.createElement('div');
    msg.className = 'small';
    msg.style.marginTop = '6px';
    msg.textContent = 'Seçili operasyon aralığında çizilecek veri bulunamadı.';
    el.parentElement.appendChild(msg);
  }

  new Chart(el, {
    type: 'line',
    data: {
      datasets: [{
        label: 'Toplam Güç (kW)',
        data: series,
        parsing: false,        // {x,y} kullanıyoruz
        borderColor: 'rgba(14,165,233,1)',
        backgroundColor: 'rgba(14,165,233,0.15)',
        fill: true,
        tension: 0.25,
        pointRadius: 0,
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          type: 'time',        // tarih adaptörü gerekli
          time: {
            unit: 'hour',
            tooltipFormat: 'yyyy-LL-dd HH:mm'
          },
          ticks: { maxRotation:0, autoSkip:true }
        },
        y: { beginAtZero:true, title:{ display:true, text:'kW' }, grace:'5%' }
      },
      plugins: {
        legend: { display:false },
        tooltip: {
          callbacks: { label(ctx){ return ' ' + (ctx.parsed.y ?? 0) + ' kW'; } }
        }
      }
    }
  });
}

// CSV export (zaman,kW)
function exportCSV(){
  const rows = [['zaman','kW']].concat(DATA.map(p=>[p.t, p.y]));
  const csv = rows.map(r=>r.map(x=>String(x).replace(/"/g,'""')).map(x=>`"${x}"`).join(',')).join('\r\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'operasyon_raporu.csv';
  document.body.appendChild(a); a.click(); a.remove();
}
</script>
</body>
</html>
