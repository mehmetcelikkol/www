<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
error_reporting(E_ALL); ini_set('display_errors','1');

// Config okuma (grafikler.php ile aynı yol listesi)
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
$errors=[]; $gauges=[]; $user=auth_user();
if(!$config){ $errors[]='Config bulunamadı.'; }
else {
		foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($config[$k])) $errors[]='Eksik config anahtarı: '.$k; }
		if(!$errors){
				$db=@new mysqli($config['Server'],$config['Uid'],$config['Pwd'],$config['Database']);
				if($db->connect_error){ $errors[]='DB bağlanamadı: '.$db->connect_error; }
				else {
						$db->set_charset('utf8');
						// Her cihaz_adres kombinasyonu için en son kayıt
						$sql="SELECT o.cihaz_id,o.cihaz_adres_id,o.deger,o.kayit_zamani,c.cihaz_adi,c.konum,ca.ad AS adres_ad\nFROM olcumler o\nJOIN (SELECT cihaz_id,cihaz_adres_id,MAX(kayit_zamani) mx FROM olcumler GROUP BY cihaz_id,cihaz_adres_id) t ON t.cihaz_id=o.cihaz_id AND t.cihaz_adres_id=o.cihaz_adres_id AND t.mx=o.kayit_zamani\nJOIN cihazlar c ON c.id=o.cihaz_id\nJOIN cihaz_adresleri ca ON ca.id=o.cihaz_adres_id\nORDER BY c.konum,c.cihaz_adi,ca.ad";
						if($res=$db->query($sql)){
								while($r=$res->fetch_assoc()){ $gauges[]=$r; }
								$res->close();
						} else { $errors[]='Sorgu hatası: '.$db->error; }
						$db->close();
				}
		}
}

function range_for(string $name): array {
		$n=mb_strtolower($name,'UTF-8');
		if(str_contains($n,'gerilim')||str_contains($n,'volt')) return [0,400,'V'];
		if(str_contains($n,'akım')||str_contains($n,'current')||str_contains($n,'amper')) return [0,100,'A'];
		if(str_contains($n,'güç')||str_contains($n,'power')||str_contains($n,'kw')) return [0,10000,'W'];
		if(str_contains($n,'frekans')||str_contains($n,'hz')) return [0,100,'Hz'];
		if(str_contains($n,'sıcak')||str_contains($n,'temp')) return [-20,120,'°C'];
		return [0,100,''];
}

?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Özet Göstergeler</title>
<link rel="stylesheet" href="assets/app.css">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;min-height:100vh;transition:background .4s,color .4s}
main{flex:1;padding:22px 24px 40px}
.errors{background:#341a1a;color:#ffb9b9;padding:12px 16px;border:1px solid #5a2a2a;border-radius:10px;margin-bottom:24px;font-size:13px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:22px}
.group-block{border:1px solid #1f3650;background:#102131;border-radius:26px;padding:14px 18px 18px;display:flex;flex-direction:column;gap:16px;position:relative}
.group-title{margin:0;font-size:13px;letter-spacing:.6px;font-weight:600;color:#b6d5f7;display:flex;align-items:center;gap:10px}
.group-badge{background:#1e3954;color:#c6e5ff;font-size:10px;padding:2px 6px;border-radius:8px;letter-spacing:.5px}
.grp-select{padding:8px 10px;border-radius:10px;border:1px solid #2c445c;background:#13293c;color:#e8f1fb;font-size:13px;min-width:160px}
.grp-select:focus{outline:2px solid #3d8bff}
.gauge-card{background:linear-gradient(150deg,#162a3d,#101c29);border:1px solid #203448;position:relative;border-radius:22px;padding:46px 16px 18px;display:flex;flex-direction:column;gap:10px;overflow:hidden;min-height:240px}
.gauge-card::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 30% 25%,rgba(70,160,255,.25),transparent 70%);opacity:.6;pointer-events:none}
.g-title{font-size:13px;font-weight:600;letter-spacing:.4px;line-height:1.3;margin:0 0 2px;color:#b6d5f7}
.g-sub{font-size:10.5px;text-transform:uppercase;letter-spacing:1.2px;opacity:.55}
.g-time{font-size:11px;opacity:.55;margin-top:auto}
.g-value{font-size:24px;font-weight:600;letter-spacing:.5px;display:flex;align-items:baseline;gap:4px}
.g-unit{font-size:11px;opacity:.7;font-weight:500}
.ring{width:120px;height:120px;margin:4px auto 2px;position:relative}
.ring svg{width:100%;height:100%;overflow:visible}
.ring circle.back{stroke:#1f3347;stroke-width:14;fill:none}
.ring circle.fg{stroke-width:14;stroke-linecap:round;fill:none;transform:rotate(-90deg);transform-origin:50% 50%;stroke-dasharray:0 999;transition:stroke-dasharray .8s cubic-bezier(.65,.05,.36,1),stroke .4s}
.level-vlow{--grad-start:#4facfe;--grad-end:#00f2fe}
.level-low{--grad-start:#1fa2ff;--grad-end:#12d8fa}
.level-mid{--grad-start:#f6d365;--grad-end:#fda085}
.level-elev{--grad-start:#f7971e;--grad-end:#ffd200}
.level-high{--grad-start:#f5576c;--grad-end:#f093fb}
.level-critical{--grad-start:#d31027;--grad-end:#ea384d}
.level-max{--grad-start:#ffffff;--grad-end:#ffe36e}
.legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:4px;font-size:11px}
.legend span{display:flex;align-items:center;gap:6px;background:#173248;padding:4px 8px;border-radius:10px;letter-spacing:.4px;transition:background .3s}
body.light .legend span{background:#d9e9f5;color:#134563}
.legend i{width:20px;height:10px;border-radius:6px;display:block}
.legend .l-vlow i{background:linear-gradient(90deg,#4facfe,#00f2fe)}
.legend .l-low i{background:linear-gradient(90deg,#1fa2ff,#12d8fa)}
.legend .l-mid i{background:linear-gradient(90deg,#f6d365,#fda085)}
.legend .l-elev i{background:linear-gradient(90deg,#f7971e,#ffd200)}
.legend .l-high i{background:linear-gradient(90deg,#f5576c,#f093fb)}
.legend .l-critical i{background:linear-gradient(90deg,#d31027,#ea384d)}
.ring defs linearGradient stop:first-child{stop-color:var(--grad-start,#1fa2ff)}
.ring defs linearGradient stop:last-child{stop-color:var(--grad-end,#12d8fa)}
/* Grup overlay */
.group-block[class*="level-"]::after{content:"";position:absolute;inset:0;border-radius:inherit;pointer-events:none;opacity:.85;mix-blend-mode:overlay}
.group-block.level-vlow::after{background:linear-gradient(145deg,rgba(79,172,254,.18),rgba(0,242,254,.05))}
.group-block.level-low::after{background:linear-gradient(145deg,rgba(31,162,255,.18),rgba(18,216,250,.05))}
.group-block.level-mid::after{background:linear-gradient(145deg,rgba(246,211,101,.18),rgba(253,160,133,.06))}
.group-block.level-elev::after{background:linear-gradient(145deg,rgba(247,151,30,.22),rgba(255,210,0,.08))}
.group-block.level-high::after{background:linear-gradient(145deg,rgba(245,87,108,.22),rgba(240,147,251,.08))}
.group-block.level-critical::after{background:linear-gradient(145deg,rgba(211,16,39,.28),rgba(234,56,77,.10))}
.group-block.level-max::after{background:linear-gradient(145deg,rgba(255,255,255,.32),rgba(255,227,110,.10))}
.group-block[class*="level-"]{box-shadow:0 4px 18px -10px rgba(0,0,0,.55)}
.mini-bar{position:absolute;right:12px;top:10px;background:#0f2232;padding:4px 8px;border-radius:10px;font-size:10px;letter-spacing:.6px;display:flex;align-items:center;gap:4px;box-shadow:0 2px 6px -2px rgba(0,0,0,.5)}
.mini-bar::before{content:"ID";background:#1e3954;color:#c6e5ff;padding:2px 5px;border-radius:6px;font-size:9px;letter-spacing:.8px}
.search-box{margin:0 0 22px;display:flex;flex-wrap:wrap;gap:12px}
.search-box input{padding:8px 10px;border-radius:10px;border:1px solid #2c445c;background:#13293c;color:#e8f1fb;font-size:13px;min-width:240px;transition:background .3s,border-color .3s,color .3s}
.search-box input:focus{outline:2px solid #3d8bff}
body.light .search-box input{background:#ffffff;border-color:#c7d7e4;color:#1e2933}
.refresh-btn{background:#1e3954;border:1px solid #335879;color:#d2e9ff;padding:8px 14px;border-radius:12px;font-size:12px;cursor:pointer;letter-spacing:.5px;transition:.3s}
.refresh-btn:hover{background:#254562}
body.light .refresh-btn{background:#d9e9f5;border-color:#b9cedd;color:#124563}
body.light .refresh-btn:hover{background:#c5d8e8}
/* Trend */
.trend{position:absolute;left:12px;top:10px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:10px;background:rgba(0,0,0,.25);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);letter-spacing:.4px}
body.light .trend{background:rgba(255,255,255,.55)}
.trend.up{color:#3bdc9b}
.trend.down{color:#ff7d7d}
.trend.flat{color:#8aa9c2}
.trend .age{font-size:9px;opacity:.55;margin-left:4px}
.trend i{font-style:normal;font-size:14px;line-height:1}
.auto-refresh{display:inline-flex;align-items:center;gap:4px;font-size:10px;opacity:.75;padding:2px 6px;border-radius:8px;background:rgba(255,255,255,.06)}
body.light .auto-refresh{background:rgba(0,0,0,.05)}
.auto-refresh input{margin:0;width:14px;height:14px;cursor:pointer}
#autoCountdown{font-variant-numeric:tabular-nums;font-weight:600;letter-spacing:.5px}
.auto-info{font-size:11px;opacity:.6;align-self:center}
footer{padding:16px 22px;font-size:11px;opacity:.55;text-align:center}
@media (max-width:640px){main{padding:18px 16px}.gauge-card{min-height:220px}.ring{width:100px;height:100px}}
</style>
</head>
<body>
<?php require __DIR__.'/partials/topnav.php'; ?>
<main class="container">
	<div class="search-box" style="background:rgba(255,255,255,.05);padding:12px 16px;border-radius:22px;display:flex;flex-wrap:wrap;gap:14px;align-items:center">
		<select id="groupMode" class="grp-select" title="Gruplandırma" style="min-width:150px">
			<option value="none">Grup: Yok</option>
			<option value="cihaz">Cihaz</option>
			<option value="konum">Konum</option>
			<option value="adres">Adres</option>
			<option value="seviye">Seviye</option>
		</select>
		<button class="refresh-btn" id="btnRefresh" onclick="location.reload()">Yenile</button>
		<label class="auto-refresh"><input type="checkbox" id="autoRef" checked><span id="autoCountdown">60sn</span></label>
		<div class="auto-info" id="autoStatus"></div>
		<button id="legendToggle" class="refresh-btn" style="padding:6px 12px;font-size:11px;margin-left:auto">Legend</button>
		<div class="legend" id="legend" style="display:none;margin:0 0 0 8px">
			<span class="l-vlow"><i></i> <20%</span>
			<span class="l-low"><i></i> 20-40%</span>
			<span class="l-mid"><i></i> 40-60%</span>
			<span class="l-elev"><i></i> 60-80%</span>
			<span class="l-high"><i></i> 80-90%</span>
			<span class="l-critical"><i></i> 90-99%</span>
			<span class="l-max"><i style="background:linear-gradient(90deg,#ffffff,#ffe36e)"></i> ~100%</span>
			<button id="hideAll" class="refresh-btn" style="padding:4px 10px;font-size:10px">Hepsini Gizle</button>
			<button id="showAll" class="refresh-btn" style="padding:4px 10px;font-size:10px">Hepsini Göster</button>
		</div>
	</div>
	<?php if($errors): ?>
		<div class="errors">
			<strong>Hatalar:</strong><br>
			<ul style="margin:6px 0 0 16px;padding:0;list-style:disc">
				<?php foreach($errors as $e): ?><li><?= htmlspecialchars($e,ENT_QUOTES,'UTF-8') ?></li><?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<div class="grid" id="grid">
		<?php foreach($gauges as $g):
				$label = trim(($g['konum']??'').' - '.($g['cihaz_adi']??''));
				$addr  = $g['adres_ad'] ?? '';
				$val   = (float)$g['deger'];
				[$min,$max,$unit] = range_for($addr.' '.$label);
				if($max==$min) $max=$min+1;
				$pct = ($val - $min)/($max-$min); if($pct<0)$pct=0; if($pct>1)$pct=1;
		if($pct < 0.2) $cls='level-vlow';
		elseif($pct < 0.4) $cls='level-low';
		elseif($pct < 0.6) $cls='level-mid';
		elseif($pct < 0.8) $cls='level-elev';
		elseif($pct < 0.9) $cls='level-high';
		elseif($pct < 0.99) $cls='level-critical';
		else $cls='level-max';
				$time = $g['kayit_zamani'];
				$gradId = 'grad-'.(int)$g['cihaz_id'].'-'.(int)$g['cihaz_adres_id'];
		?>
		<div class="gauge-card <?= $cls ?>" data-filter="<?= htmlspecialchars(mb_strtolower($label.' '.$addr,'UTF-8'),ENT_QUOTES,'UTF-8') ?>" data-cihaz="<?= htmlspecialchars(mb_strtolower($g['cihaz_adi']??'', 'UTF-8'),ENT_QUOTES,'UTF-8') ?>" data-kurum="<?= htmlspecialchars(mb_strtolower($g['konum']??'', 'UTF-8'),ENT_QUOTES,'UTF-8') ?>" data-konum="<?= htmlspecialchars(mb_strtolower($g['konum']??'', 'UTF-8'),ENT_QUOTES,'UTF-8') ?>" data-adres="<?= htmlspecialchars(mb_strtolower($addr, 'UTF-8'),ENT_QUOTES,'UTF-8') ?>" data-seviye="<?= $cls ?>" data-value="<?= htmlspecialchars((string)$val,ENT_QUOTES,'UTF-8') ?>" data-pct="<?= htmlspecialchars((string)round($pct*100,2),ENT_QUOTES,'UTF-8') ?>" data-cid="<?= (int)$g['cihaz_id'] ?>" data-aid="<?= (int)$g['cihaz_adres_id'] ?>">
				<div class="mini-bar">ID <?= (int)$g['cihaz_id'] ?>/<?= (int)$g['cihaz_adres_id'] ?></div>
				<div class="g-title"><?= htmlspecialchars($addr,ENT_QUOTES,'UTF-8') ?></div>
				<div class="g-sub"><?= htmlspecialchars($label,ENT_QUOTES,'UTF-8') ?></div>
				<div class="ring">
					<svg viewBox="0 0 100 100">
						<defs>
							<linearGradient id="<?= $gradId ?>" x1="0%" y1="0%" x2="100%" y2="0%">
								<stop offset="0%" />
								<stop offset="100%" />
							</linearGradient>
						</defs>
						<circle class="back" cx="50" cy="50" r="40"></circle>
						<?php $circum=2*pi()*40; $len = $circum*$pct; ?>
						<circle class="fg" cx="50" cy="50" r="40" data-len="<?= round($len,2) ?>" data-circum="<?= round($circum,2) ?>" style="stroke:url(#<?= $gradId ?>)"></circle>
						<text x="50" y="54" text-anchor="middle" font-size="18" font-weight="600" fill="#fff"><?= htmlspecialchars(rtrim(rtrim(number_format($val,2,'.',''),'0'),'.'),ENT_QUOTES,'UTF-8') ?></text>
					</svg>
				</div>
				<div class="g-value"><?= htmlspecialchars(number_format($val,2,'.',''),ENT_QUOTES,'UTF-8') ?><span class="g-unit"><?= htmlspecialchars($unit,ENT_QUOTES,'UTF-8') ?></span></div>
				<div style="font-size:10px;opacity:.45">Min: <?= $min ?> | Max: <?= $max ?></div>
				<div class="g-time"><?= htmlspecialchars($time,ENT_QUOTES,'UTF-8') ?></div>
		</div>
		<?php endforeach; ?>
	</div>
</main>
<script src="assets/app.js"></script>
</body>
</html>
