<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();

date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors','1');

$errors = [];
$notes  = [];
$started = microtime(true);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmt_dt(?DateTimeInterface $dt, string $format, string $fallback=''): string {
    return $dt instanceof DateTimeInterface ? $dt->format($format) : $fallback;
}
function fmt_hm(int|float $seconds): string {
    $m = intdiv((int)round($seconds), 60);
    return sprintf('%d saat %02d dk', intdiv($m,60), $m % 60);
}

/* ---------------- CONFIG ---------------- */
function load_config(string $path): ?array {
    if(!is_file($path)) return null;
    $xml = @simplexml_load_file($path);
    if(!$xml || !isset($xml->connectionStrings)) return null;
    foreach($xml->connectionStrings->add as $add){
        if((string)$add['name']==='MySqlConnection'){
            $connStr = (string)$add['connectionString'];
            $out=[];
            foreach(explode(';',$connStr) as $piece){
                if($piece==='') continue;
                [$k,$v] = array_pad(explode('=',$piece,2), 2, '');
                $out[trim($k)] = trim($v);
            }
            return $out;
        }
    }
    return null;
} 
$configPaths = [
    'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config',
];
$config = null; $configPathUsed = null;
foreach($configPaths as $candidate){
    $cfg = load_config($candidate);
    if($cfg){
        $config = $cfg;
        $configPathUsed = $candidate;
        break;
    }
}
// Yeni: .env fallback
if(!$config){
    // basit .env okuyucu
    $envPath = __DIR__.'/.env';
    $envExamplePath = __DIR__.'/.env.example';
    $envFile = is_file($envPath) ? $envPath : (is_file($envExamplePath) ? $envExamplePath : null);
    if ($envFile) {
        $env = [];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line[0] === '#' || !str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $v = trim($v, "\"'");
            $env[$k] = $v;
        }
        if (!empty($env['DB_HOST']) && !empty($env['DB_USER']) && !empty($env['DB_NAME'])) {
            $config = [
                'Server'   => $env['DB_HOST'],
                'Uid'      => $env['DB_USER'],
                'Pwd'      => $env['DB_PASS'] ?? '',
                'Database' => $env['DB_NAME'],
            ];
            $configPathUsed = $envFile;
            $notes[] = '.env yapılandırması kullanıldı.';
        }
    }
}
if(!$config){
    $errors[] = 'Config dosyası bulunamadı. Bağlantı bilgilerini kontrol edin.';
}

/* ---------------- TARİH FİLTRESİ ---------------- */
$hasStart = isset($_GET['start']) && $_GET['start'] !== '';
$hasEnd   = isset($_GET['end']) && $_GET['end'] !== '';
$useDateFilter = $hasStart || $hasEnd;

$now = new DateTimeImmutable();
$defaultEnd = $now;
$defaultStart = $now->sub(new DateInterval('P1D'));

$startInput = $hasStart ? (string)$_GET['start'] : $defaultStart->format('Y-m-d\TH:i');
$endInput   = $hasEnd   ? (string)$_GET['end']   : $defaultEnd->format('Y-m-d\TH:i');

try { $startDT = new DateTime($startInput ?: $defaultStart->format('Y-m-d\TH:i')); }
catch(Throwable $e){ $errors[]='Başlangıç tarihi okunamadı, varsayılan kullanıldı.'; $startDT = new DateTime($defaultStart->format('Y-m-d\TH:i')); }

try { $endDT = new DateTime($endInput ?: $defaultEnd->format('Y-m-d\TH:i')); }
catch(Throwable $e){ $errors[]='Bitiş tarihi okunamadı, varsayılan kullanıldı.'; $endDT = new DateTime($defaultEnd->format('Y-m-d\TH:i')); }

if($startDT > $endDT){
    $tmp = $startDT;
    $startDT = $endDT;
    $endDT = $tmp;
    $notes[] = 'Tarih aralığı ters girildi, otomatik düzeltildi.';
}
$startSql = $startDT->format('Y-m-d H:i:s');
$endSql   = $endDT->format('Y-m-d H:i:s');

/* ---------------- VERİ TOPLAMA ---------------- */
$devicesCatalog = [];
$chartDevices = [];
$devicesOut = [];
$rowCount = 0;
$minRecord = null;
$maxRecord = null;

if($config){
    // MySQL bağlantısı ve veri çekme (mevcut kodunuz)
    // $devicesCatalog, $chartDevices, $devicesOut, $rowCount, $minRecord, $maxRecord dolduruluyor
    foreach (['Server','Uid','Database'] as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            $errors[] = "Config anahtarı eksik: {$key}";
        }
    }
    $pwd = $config['Pwd'] ?? '';

    if(empty($errors)){
        $db = @new mysqli($config['Server'], $config['Uid'], $pwd, $config['Database']);
        if($db->connect_errno){
            $errors[] = 'MySQL bağlantı hatası: '.$db->connect_error;
        } else {
            $db->set_charset('utf8');

            /* Cihaz listesi */
            $sqlDevices = "SELECT c.id, c.cihaz_adi, c.konum
                           FROM cihazlar c
                           WHERE EXISTS (SELECT 1 FROM olcumler o WHERE o.cihaz_id=c.id)
                           ORDER BY c.cihaz_adi";
            if($res = $db->query($sqlDevices)){
                while($row = $res->fetch_assoc()){
                    $devicesCatalog[(int)$row['id']] = [
                        'id'    => (int)$row['id'],
                        'label' => trim(($row['konum'] ?? '').' '.$row['cihaz_adi']) ?: ('Cihaz '.$row['id']),
                    ];
                }
                $res->free();
            } else {
                $errors[] = 'Cihaz listesi alınamadı: '.$db->error;
            }

            $selectedIds = [];
            if(isset($_GET['dev']) && is_array($_GET['dev'])){
                foreach($_GET['dev'] as $id){
                    $intId = (int)$id;
                    if(isset($devicesCatalog[$intId])){
                        $selectedIds[] = $intId;
                    }
                }
            }
            if(!$selectedIds){
                $selectedIds = array_keys($devicesCatalog);
            }
            $selectedIds = array_values(array_unique($selectedIds));

            if($selectedIds){
                $inClause = implode(',', array_fill(0, count($selectedIds), '?'));
                $sqlData = "
                    SELECT 
                        o.cihaz_id,
                        o.cihaz_adres_id,
                        ca.ad   AS kanal_ad,
                        o.deger,
                        o.kayit_zamani,
                        c.cihaz_adi,
                        c.konum
                    FROM olcumler o
                    INNER JOIN cihazlar c        ON c.id = o.cihaz_id
                    INNER JOIN cihaz_adresleri ca ON ca.id = o.cihaz_adres_id
                    WHERE o.cihaz_id IN ($inClause)
                ";
                $params = [];
                $types  = str_repeat('i', count($selectedIds));
                $params = $selectedIds;

                if($useDateFilter){
                    $sqlData .= " AND o.kayit_zamani BETWEEN ? AND ?";
                    $types .= 'ss';
                    $params[] = $startSql;
                    $params[] = $endSql;
                }
                $sqlData .= " ORDER BY o.cihaz_id, o.cihaz_adres_id, o.kayit_zamani";

                $stmt = $db->prepare($sqlData);
                if(!$stmt){
                    $errors[] = 'Veri sorgusu hazırlanamadı: '.$db->error;
                } else {
                    // mysqli::bind_param referans ister
                    $refs = [];
                    foreach ($params as $i => $v) { $refs[$i] = &$params[$i]; }
                    $okBind = $stmt->bind_param($types, ...$refs);
                    if(!$okBind){
                        $errors[] = 'Parametre bağlama hatası: bind_param başarısız.';
                    } elseif(!$stmt->execute()){
                        $errors[] = 'Veri sorgusu çalıştırılamadı: '.$stmt->error;
                    } else {
                        $stmt->bind_result($cihazId,$adresId,$kanalAd,$deger,$kayitZamani,$cihazAdi,$konum);
                        $tmp = [];
                        while($stmt->fetch()){
                            $rowCount++;
                            $cihazId = (int)$cihazId;
                            $adresId = (int)$adresId;
                            $key = "{$cihazId}|{$adresId}";
                            if($minRecord===null || $kayitZamani < $minRecord) $minRecord=$kayitZamani;
                            if($maxRecord===null || $kayitZamani > $maxRecord) $maxRecord=$kayitZamani;

                            if(!isset($tmp[$cihazId])){
                                $label = trim(($konum ?? '').' '.$cihazAdi) ?: 'Cihaz '.$cihazId;
                                $tmp[$cihazId] = [
                                    'id'     => $cihazId,
                                    'label'  => $label,
                                    'series' => [],
                                ];
                            }
                            if(!isset($tmp[$cihazId]['series'][$adresId])){
                                $tmp[$cihazId]['series'][$adresId] = [
                                    'id'   => $adresId,
                                    'name' => $kanalAd ?? ('Adres '.$adresId),
                                    'data' => [],
                                ];
                            }
                            $tmp[$cihazId]['series'][$adresId]['data'][] = [
                                'x' => str_replace(' ', 'T', $kayitZamani),
                                'y' => (float)$deger,
                            ];
                        }
                        // Sorgu okuma bittiğinde:
                        $chartDevices = [];
                        foreach ($tmp as $device) {
                            if (isset($device['series']) && is_array($device['series'])) {
                                $device['series'] = array_values($device['series']); // ÖNEMLİ: assoc -> dizi
                            } else {
                                $device['series'] = [];
                            }
                            $chartDevices[] = $device;
                        }

                        // grafikler.php ile uyumlu çıktı (DEVICES)
                        if($chartDevices){
                            $devicesOut = array_map(function($d){
                                return [
                                    'cihaz_id' => $d['id'] ?? null,
                                    'label'    => $d['label'] ?? '',
                                    'series'   => array_map(function($s){
                                        return [
                                            'name' => $s['name'] ?? '',
                                            'data' => $s['data'] ?? [],
                                        ];
                                    }, $d['series'] ?? [])
                                ];
                            }, $chartDevices);
                        }
                    }
                    $stmt->close();
                }
            }

            $db->close();
        }
    }
}

// YENİ: MySQL 0 kayıt dönerse SQLite fallback
if ($rowCount === 0 && empty($errors)) {
    try {
        $sqlitePath = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/energy.db';
        if (!is_file($sqlitePath)) {
            $notes[] = 'SQLite fallback: energy.db bulunamadı.';
        } else {
            $pdo = new PDO('sqlite:' . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Cihaz kataloğu
            $sqlDevices = "
                SELECT c.id, c.cihaz_adi
                FROM cihazlar c
                WHERE EXISTS (SELECT 1 FROM olcumler o WHERE o.cihaz_id = c.id)
                ORDER BY c.cihaz_adi
            ";
            $devicesCatalog = [];
            foreach ($pdo->query($sqlDevices) as $row) {
                $devicesCatalog[(int)$row['id']] = [
                    'id'    => (int)$row['id'],
                    'label' => trim($row['cihaz_adi']) ?: ('Cihaz ' . (int)$row['id']),
                ];
            }

            // Seçim
            $selectedIds = [];
            if(isset($_GET['dev']) && is_array($_GET['dev'])){
                foreach($_GET['dev'] as $id){
                    $intId = (int)$id;
                    if($intId>0 && isset($devicesCatalog[$intId])) $selectedIds[] = $intId;
                }
            }
            if(!$selectedIds) $selectedIds = array_keys($devicesCatalog);
            $selectedIds = array_values(array_unique($selectedIds));

            if ($selectedIds) {
                $in = implode(',', array_fill(0, count($selectedIds), '?'));
                $sql = "
                    SELECT o.cihaz_id, o.kanal_id, k.ad AS kanal_ad, o.deger, o.kayit_zamani, c.cihaz_adi
                    FROM olcumler o
                    JOIN cihazlar c ON c.id = o.cihaz_id
                    LEFT JOIN kanallar k ON k.id = o.kanal_id
                    WHERE o.cihaz_id IN ($in)
                ";
                $params = $selectedIds;
                if ($useDateFilter) {
                    $sql .= " AND o.kayit_zamani BETWEEN ? AND ?";
                    $params[] = $startSql;
                    $params[] = $endSql;
                }
                $sql .= " ORDER BY o.cihaz_id, o.kanal_id, o.kayit_zamani";
                $st = $pdo->prepare($sql);
                $st->execute($params);

                $tmp = [];
                while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                    $rowCount++;
                    $cihazId = (int)$row['cihaz_id'];
                    $kanalId = (int)$row['kanal_id'];
                    $kayitZamani = $row['kayit_zamani'];
                    $kanalAd = $row['kanal_ad'] ?? null;
                    $cihazAdi = $row['cihaz_adi'] ?? null;

                    if($minRecord===null || $kayitZamani < $minRecord) $minRecord=$kayitZamani;
                    if($maxRecord===null || $kayitZamani > $maxRecord) $maxRecord=$kayitZamani;

                    if (!isset($tmp[$cihazId])) {
                        $label = trim($cihazAdi ?? '') ?: ('Cihaz '.$cihazId);
                        $tmp[$cihazId] = ['id'=>$cihazId, 'label'=>$label, 'series'=>[]];
                    }
                    if (!isset($tmp[$cihazId]['series'][$kanalId])) {
                        $tmp[$cihazId]['series'][$kanalId] = [
                            'id' => $kanalId,
                            'name' => $kanalAd ?? ('Kanal '.$kanalId),
                            'data' => [],
                        ];
                    }
                    $tmp[$cihazId]['series'][$kanalId]['data'][] = [
                        'x' => str_replace(' ','T',$kayitZamani),
                        'y' => (float)$row['deger'],
                    ];
                }
                foreach ($tmp as $device) {
                    $device['series'] = array_values($device['series']);
                    $chartDevices[] = $device;
                }
                if ($chartDevices) {
                    $devicesOut = array_map(function($d){
                        return [
                            'cihaz_id' => $d['id'],
                            'label' => $d['label'],
                            'series' => array_map(function($s){
                                return [
                                    'cihaz_adres_id' => $s['id'],
                                    'name' => $s['name'],
                                    'data' => $s['data'],
                                ];
                            }, $d['series'])
                        ];
                    }, $chartDevices);
                }
                $notes[] = 'SQLite fallback kullanıldı: energy.db';
                if(!$useDateFilter){
                    try { $startDT = new DateTime($minRecord ?? $startSql); } catch(Throwable) {}
                    try { $endDT   = new DateTime($maxRecord ?? $endSql); } catch(Throwable) {}
                }
            }
        }
    } catch (Throwable $e) {
        $errors[] = 'SQLite fallback hatası: ' . $e->getMessage();
    }
}

/* ---------------- OPERASYONLAR ---------------- */
$opsOut = [];
$opsOpenCount=0; $opsClosedCount=0;
$opsOpenSec=0; $opsClosedSec=0;
$opsCoverage = 0;

try{
    $authDb = auth_db();
    if($authDb){
        @$authDb->query("SET time_zone = '+03:00'");
        $opsSql = "
            SELECT id, ad, baslangic, bitis, miktar, birim
            FROM enerji_operasyonlar
            WHERE baslangic <= ? AND (bitis IS NULL OR bitis='0000-00-00 00:00:00' OR bitis >= ?)
            ORDER BY baslangic
        ";
        $opsStmt = $authDb->prepare($opsSql);
        if($opsStmt){
            $opsStmt->bind_param('ss', $endSql, $startSql);
            if($opsStmt->execute()){
                $opsStmt->bind_result($oid,$oad,$obas,$obit,$omik,$obir);
                $rangeStartTs = strtotime($startSql);
                $rangeEndTs   = strtotime($endSql);
                if(!$useDateFilter){
                    $rangeStartTs = strtotime($minRecord ?? $startSql);
                    $rangeEndTs   = strtotime($maxRecord ?? $endSql);
                }
                $intervals = [];
                while($opsStmt->fetch()){
                    $startTs = strtotime((string)$obas);
                    $bitisRaw = $obit ? trim((string)$obit) : null;
                    $isOpen = !$bitisRaw || $bitisRaw === '0000-00-00 00:00:00';
                    $endTs = $isOpen ? ($rangeEndTs ?: time()) : strtotime($bitisRaw);
                    if(!$startTs || !$endTs) continue;

                    // grafikler.php ile aynı alan adları
                    $opsOut[] = [
                        'id'        => (int)$oid,
                        'ad'        => (string)$oad,
                        'baslangic' => (string)$obas,
                        'bitis'     => $isOpen ? null : (string)$bitisRaw,
                        'miktar'    => $omik === null ? null : (float)$omik,
                        'birim'     => $obir ?? '',
                    ];

                    // kapsama ve sayaçlar
                    $a = max($startTs, $rangeStartTs);
                    $b = min($endTs, $rangeEndTs);
                    if($b > $a){
                        $intervals[] = [$a,$b];
                        if($isOpen){ $opsOpenCount++; $opsOpenSec += ($b-$a); }
                        else { $opsClosedCount++; $opsClosedSec += ($b-$a); }
                    }
                }
                if($intervals){
                    usort($intervals, fn($x,$y)=> $x[0] <=> $y[0]);
                    $merged = [];
                    [$curStart,$curEnd] = $intervals[0];
                    for($i=1,$n=count($intervals); $i<$n; $i++){
                        [$s,$e] = $intervals[$i];
                        if($s <= $curEnd){ $curEnd = max($curEnd, $e); }
                        else { $merged[] = [$curStart,$curEnd]; [$curStart,$curEnd] = [$s,$e]; }
                    }
                    $merged[] = [$curStart,$curEnd];
                    $total = max(1, ($rangeEndTs ?? 0) - ($rangeStartTs ?? 0));
                    $covered = 0;
                    foreach($merged as [$s,$e]){ $covered += ($e-$s); }
                    $opsCoverage = $covered * 100 / $total;
                }
            }
            $opsStmt->close();
        }
    }
}catch(Throwable $e){
    $errors[] = 'Operasyon verisi okunamadı: '.$e->getMessage();
}

/* ---------------- ÖZET ---------------- */
if(!$rowCount){
    $notes[] = 'Grafiklenebilir kayıt bulunamadı.';
} else {
    $notes[] = 'Sorgu satırı: '.$rowCount;
    if($minRecord) $notes[] = 'En eski kayıt: '.$minRecord;
    if($maxRecord) $notes[] = 'En yeni kayıt: '.$maxRecord;
    if(!$useDateFilter){
        try { $startDT = new DateTime($minRecord ?? $startSql); } catch(Throwable) {}
        try { $endDT   = new DateTime($maxRecord ?? $endSql); } catch(Throwable) {}
    }
}
$genMs = round((microtime(true)-$started)*1000,2);

// JSON bayrakları (eski PHP sürümlerinde güvenli)
$JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if(defined('JSON_INVALID_UTF8_SUBSTITUTE')) { $JSON_FLAGS |= JSON_INVALID_UTF8_SUBSTITUTE; }

/* >>> Köprü verilerini garantiye al (biri boşsa diğerinden türet) <<< */
if (empty($devicesOut) && !empty($chartDevices)) {
    $devicesOut = array_map(function($d){
        return [
            'cihaz_id' => $d['id'] ?? null,
            'label'    => $d['label'] ?? '',
            'series'   => array_map(function($s){
                return [
                    'name' => $s['name'] ?? '',
                    'data' => $s['data'] ?? [],
                ];
            }, is_array($d['series'] ?? null) ? $d['series'] : []),
        ];
    }, $chartDevices);
}
if (empty($chartDevices) && !empty($devicesOut)) {
    $chartDevices = array_map(function($d){
        return [
            'id'     => $d['cihaz_id'] ?? null,
            'label'  => $d['label'] ?? '',
            'series' => array_map(function($s){
                return [
                    'name' => $s['name'] ?? '',
                    'data' => $s['data'] ?? [],
                ];
            }, is_array($d['series'] ?? null) ? $d['series'] : []),
        ];
    }, $devicesOut);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Grafikler 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- grafikler.php’de ne link varsa birebir kopyalayın -->
    <link rel="stylesheet" href="assets/app.css">
    <!-- Eğer grafikler.php başka css dosyaları kullanıyorsa buraya ekleyin -->
    <!-- <link rel="stylesheet" href="assets/style.css"> -->

    <!-- NOT: Buradaki inline <style> bloklarını kaldırın -->
</head>
<body>
<?php require __DIR__.'/partials/topnav.php'; ?>
<div class="container">
  <div id="chartRegion"></div>
  <!-- ...existing code (özet/hata panelleri) ... -->
</div>

<script>
window.DEVICES_FROM_DB = <?= json_encode($chartDevices, $JSON_FLAGS) ?>;
window.DEVICES         = <?= json_encode($devicesOut,   $JSON_FLAGS) ?>;
window.OPS_FROM_SERVER = <?= json_encode($opsOut,       $JSON_FLAGS) ?>;
window.USE_DATE_FILTER = <?= $useDateFilter ? 'true' : 'false' ?>;
window.RANGE_START_ISO = '<?= h($startDT->format('Y-m-d H:i:s')) ?>';
window.RANGE_END_ISO   = '<?= h($endDT->format('Y-m-d H:i:s')) ?>';
</script>

<script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js" defer></script>
<script src="assets/grafikler2.js" defer></script>
</body>
</html>
