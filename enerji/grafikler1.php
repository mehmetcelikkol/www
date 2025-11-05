<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
/*
    grafikler.php (yeniden yazıldı)
    - Beyaz sayfa olmaması için tüm hatalar yakalanır ve HTML panelde gösterilir.
    - Config otomatik okunur, bulunamazsa uyarı verir (manuel override alanı var).
    - mysqlnd gerekmez (bind_result kullanılır).
    - Tarih filtresi (son 24 saat varsayılan) + hazır aralık butonları.
    - Chart.js + Luxon offline/online fallback.
*/
error_reporting(E_ALL); ini_set('display_errors','1');
set_time_limit(60); // ) eklendi
// EKLENDİ: Saat dilimi
date_default_timezone_set('Europe/Istanbul');

$started = microtime(true);
$errors  = [];
$notes   = [];
$devicesOut = [];
$opsOut = []; // EKLENDİ: JS’ye gömeceğimiz operasyon listesi için varsayılan
$openTotalAll = 0;   // EKLENDİ
$closedTotalAll = 0; // EKLENDİ
$openSecNow = 0;     // EKLENDİ: Açık operasyonların şu ana kadar toplam süresi (sn)

// Merkezi hata yakalama (fatal hariç)
set_exception_handler(function($ex) use (&$errors){ $errors[] = 'İstisna: '. $ex->getMessage(); });
set_error_handler(function($no,$str,$file,$line) use (&$errors){ $errors[] = "PHP Hatası [$no] $str ($file:$line)"; return false; });

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
// EKLENDİ: Süre formatlayıcı (saniye -> "Hs MMdk")
function fmt_hm(int|float $seconds): string {
    $m = intdiv((int)round($seconds), 60);
    $h = intdiv($m, 60);
    $mm = $m % 60;
    return sprintf('%d saat %02d dk', $h, $mm);
}

// ---- CONFIG OKUMA ----
function load_config(string $path): ?array {
    if(!is_file($path)) return null;
    $xml = @simplexml_load_file($path);
    if(!$xml || !isset($xml->connectionStrings)) return null;
    foreach($xml->connectionStrings->add as $add){
        if((string)$add['name']==='MySqlConnection'){
            $connStr = (string)$add['connectionString'];
            $out=[]; foreach(explode(';',$connStr) as $p){ $kv=explode('=',$p,2); if(count($kv)==2) $out[trim($kv[0])] = trim($kv[1]); }
            return $out; }
    }
    return null;
}

$CONFIG_PATH_CANDIDATES = [
    // Mevcut bilinen yol (gerekirse düzenleyin)
    'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config',
    // Aynı klasörden göreli bir örnek; gerekiyorsa ekleyin
];
$config = null; $configPathUsed = null;
foreach($CONFIG_PATH_CANDIDATES as $p){
    $c = load_config($p);
    if($c){ $config=$c; $configPathUsed=$p; break; }
}
if(!$config){
    $errors[]='Config bulunamadı. Manuel veritabanı bilgisi girilmesi gerekir.';
    // MANUEL OVERRIDE (doldurmak için yorum satırını açın)
    // $config = ['Server'=>'127.0.0.1','Uid'=>'root','Pwd'=>'','Database'=>'veritabani_adi'];
    // $notes[]='Manuel override kullanıldı.';
}

// ---- TARİH FİLTRESİ ----
// Tarih parametreleri yoksa (ilk açılış) filtre kullanma -> tüm veriyi getir
$noFilter = !isset($_GET['start']) && !isset($_GET['end']);
$endDefault = new DateTime();
$startDefault = (clone $endDefault)->modify('-24 hours'); // sadece varsayılan gösterim için
$startInput = $_GET['start'] ?? $startDefault->format('Y-m-d\\TH:i');
$endInput   = $_GET['end']   ?? $endDefault->format('Y-m-d\\TH:i');
// Sadece başlangıç verilmişse bitiş aynı gün 23:59
if(isset($_GET['start']) && !isset($_GET['end'])){
    if(preg_match('/^(\\d{4}-\\d{2}-\\d{2})/', $startInput, $m)){
        $endInput = $m[1].'T23:59';
    }
}
// Sadece bitiş verilmişse başlangıç aynı gün 00:00
if(isset($_GET['end']) && !isset($_GET['start'])){
    if(preg_match('/^(\\d{4}-\\d{2}-\\d{2})/', $endInput, $m)){
        $startInput = $m[1].'T00:00';
    }
}
// '--:--' içeren (tarayıcıda saat seçilmemiş) değerleri düzelt (server side güvence)
if(isset($_GET['start']) && preg_match('/^(\\d{4}-\\d{2}-\\d{2})T--:--$/',$startInput,$m)){
    $startInput = $m[1].'T00:00';
}
if(isset($_GET['end']) && preg_match('/^(\\d{4}-\\d{2}-\\d{2})T--:--$/',$endInput,$m)){
    $endInput = $m[1].'T23:59';
}
// Eski davranış: iki tarih alanı birlikte kullanılır (varsayılan son 24 saat). Tek taraf seçilirse diğeri varsayılan olur.
// Eksik saat/dakika (örn '--:--' veya sadece tarih) durumlarında varsayılan saatleri ekle
if(isset($_GET['start'])){
    if(preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$startInput)){
        $startInput .= 'T00:00';
    } elseif(preg_match('/^(\\d{4}-\\d{2}-\\d{2})T?$/',$startInput,$m)) {
        $startInput = $m[1].'T00:00';
    } elseif(str_contains($startInput,'--')){ // 2025-09-04T--:-- gibi
        if(preg_match('/^(\\d{4}-\\d{2}-\\d{2})/',$startInput,$m)) $startInput = $m[1].'T00:00';
    }
}
if(isset($_GET['end'])){
    if(preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$endInput)){
        $endInput .= 'T23:59';
    } elseif(preg_match('/^(\\d{4}-\\d{2}-\\d{2})T?$/',$endInput,$m)) {
        $endInput = $m[1].'T23:59';
    } elseif(str_contains($endInput,'--')){
        if(preg_match('/^(\\d{4}-\\d{2}-\\d{2})/',$endInput,$m)) $endInput = $m[1].'T23:59';
    }
}
try { $startDT = new DateTime($startInput); } catch(Throwable $t){ $errors[]='Geçersiz başlangıç tarihi, varsayılan kullanıldı.'; $startDT=$startDefault; }
try { $endDT   = new DateTime($endInput); }   catch(Throwable $t){ $errors[]='Geçersiz bitiş tarihi, varsayılan kullanıldı.'; $endDT=$endDefault; }
if($startDT > $endDT){ $tmp=$startDT; $startDT=$endDT; $endDT=$tmp; $notes[]='Ters tarih aralığı düzeltildi.'; }

// Aynı dakika ise tam güne genişlet
if($startDT->format('Y-m-d H:i') === $endDT->format('Y-m-d H:i')){
    $startDT->setTime(0,0); $endDT->setTime(23,59);
    $notes[]='Tek nokta algılandı; gün 00:00-23:59 olarak genişletildi.';
}

$useDateFilter = !$noFilter; // parametre yoksa tüm veri
$startSql = $startDT->format('Y-m-d H:i:s');
$endSql   = $endDT->format('Y-m-d H:i:s');

// ---- VERİ TABANI ----
if($config){
    foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($config[$k])) $errors[]='Eksik config anahtarı: '.$k; }
    if(empty(array_filter(['Server','Uid','Pwd','Database'], fn($k)=>!isset($config[$k])))){
        $mysqli = @new mysqli($config['Server'],$config['Uid'],$config['Pwd'],$config['Database']);
        if($mysqli->connect_error){ $errors[]='MySQL bağlantı hatası: '.$mysqli->connect_error; }
        else {
            $mysqli->set_charset('utf8');
            $sql = "SELECT o.cihaz_id, o.cihaz_adres_id, o.deger, o.kayit_zamani, c.cihaz_adi, c.konum, ca.ad AS adres_ad\n                    FROM olcumler o\n                    JOIN cihazlar c ON c.id=o.cihaz_id\n                    JOIN cihaz_adresleri ca ON ca.id=o.cihaz_adres_id";
            if($useDateFilter){
                $sql .= " WHERE o.kayit_zamani BETWEEN ? AND ?";
            }
            $sql .= " ORDER BY o.cihaz_id, o.cihaz_adres_id, o.kayit_zamani";
            $st = $mysqli->prepare($sql);
            if(!$st){ $errors[]='Prepare başarısız: '.$mysqli->error; }
            else {
                if($useDateFilter){ if(!$st->bind_param('ss',$startSql,$endSql)) $errors[]='bind_param başarısız: '.$st->error; }
                if(empty($errors) && !$st->execute()) $errors[]='Sorgu çalıştırma hatası: '.$st->error;
                else {
                    if(!$st->bind_result($cihaz_id,$cihaz_adres_id,$deger,$kayit_zamani,$cihaz_adi,$konum,$adres_ad)){
                        $errors[]='bind_result hatası: '.$st->error;
                    } else {
                        $rowCounter=0; $devicesTmp=[];
                        $minFetched=null; $maxFetched=null; $firstRows=[]; $lastRows=[]; // debug amaçlı
                        while($st->fetch()){
                            $rowCounter++;
                            $cid=(int)$cihaz_id; $addrId=(int)$cihaz_adres_id;
                            if($minFetched===null || $kayit_zamani < $minFetched) $minFetched=$kayit_zamani;
                            if($maxFetched===null || $kayit_zamani > $maxFetched) $maxFetched=$kayit_zamani;
                            if(count($firstRows) < 5){
                                $firstRows[] = [$kayit_zamani,$cihaz_id,$cihaz_adres_id,$adres_ad,$deger];
                            }
                            if(count($lastRows)===5){ array_shift($lastRows); }
                            $lastRows[] = [$kayit_zamani,$cihaz_id,$cihaz_adres_id,$adres_ad,$deger];
                            if(!isset($devicesTmp[$cid])){
                                $label = trim(($konum??'').' - '.$cihaz_adi);
                                $devicesTmp[$cid]=['label'=>$label,'series'=>[]];
                            }
                            $key=$addrId.'|'.$adres_ad;
                            if(!isset($devicesTmp[$cid]['series'][$key])){
                                $devicesTmp[$cid]['series'][$key]=['name'=>$adres_ad,'data'=>[]];
                            }
                            $isoTime = str_replace(' ','T',$kayit_zamani);
                            $devicesTmp[$cid]['series'][$key]['data'][]=['x'=>$isoTime,'y'=>(float)$deger];
                        }
                        if($rowCounter===0) $notes[]= $useDateFilter ? 'Bu aralıkta ölçüm yok.' : 'Kayıt bulunamadı.';
                        else {
                            $notes[]='Sorguda toplam satır: '.$rowCounter;
                            $notes[]='En eski kayıt: '.$minFetched;
                            $notes[]='En yeni kayıt: '.$maxFetched;
                            if(!$useDateFilter){
                                // Formda gösterilsin diye başlangıç/bitişi güncelle
                                try { $startDT = new DateTime($minFetched); } catch(Throwable $t){}
                                try { $endDT   = new DateTime($maxFetched); } catch(Throwable $t){}
                            }
                        }
                        $GLOBALS['__debugFirstRows']=$firstRows; $GLOBALS['__debugLastRows']=$lastRows; // template erişimi için
                        foreach($devicesTmp as $cid=>$d){
                            $series=[]; foreach($d['series'] as $s){ $series[]=['name'=>$s['name'],'data'=>$s['data']]; }
                            $devicesOut[]=['cihaz_id'=>$cid,'label'=>$d['label'],'series'=>$series];
                        }
                        unset($devicesTmp);
                    }
                }
                $st->close();

                // DEĞİŞTİRİLDİ: Operasyonları auth.php'deki DB'den çek (ölçüm DB’si değil)
                $opsOut = [];
                // Aralık: filtre varsa onu, yoksa ölçümlerin kapsadığı aralığı kullan
                if ($useDateFilter) {
                    $opsStartSql = $startSql;
                    $opsEndSql   = $endSql;
                } else {
                    $opsStartSql = isset($minFetched) ? $minFetched : $startSql;
                    $opsEndSql   = isset($maxFetched) ? $maxFetched : $endSql;
                }

                // auth.php içindeki DB (operasyonların bulunduğu DB)
                try {
                    $authDb = auth_db();
                    if ($authDb) {
                        // EKLENDİ: MySQL oturum saat dilimi (Türkiye)
                        @$authDb->query("SET time_zone = '+03:00'");

                        $stmtOp = $authDb->prepare("
                            SELECT id, ad, baslangic, bitis, miktar, birim
                            FROM enerji_operasyonlar
                            WHERE baslangic <= ? AND (bitis IS NULL OR bitis >= ?)
                            ORDER BY baslangic ASC
                        ");
                        if ($stmtOp) {
                            $stmtOp->bind_param('ss', $opsEndSql, $opsStartSql);
                            if ($stmtOp->execute()) {
                                $stmtOp->bind_result($opId, $opAd, $opBas, $opBit, $opMiktar, $opBirim);
                                while ($stmtOp->fetch()) {
                                    $opsOut[] = [
                                        'id'        => (int)$opId,
                                        'ad'        => (string)$opAd,
                                        'baslangic' => (string)$opBas,
                                        // 0000... da açık say
                                        'bitis'     => ($opBit === null || $opBit === '0000-00-00 00:00:00') ? null : (string)$opBit,
                                        'miktar'    => $opMiktar === null ? null : (float)$opMiktar,
                                        'birim'     => $opBirim === null ? '' : (string)$opBirim,
                                    ];
                                }
                            } else {
                                $errors[] = 'Operasyon sorgusu çalıştırılamadı: '.$stmtOp->error;
                            }
                            $stmtOp->close();

                            // EKLENDİ: Genel toplam (adet) — açık ve bitmiş
                            if ($authDb) {
                                $res = @$authDb->query("
                                    SELECT
                                      SUM(CASE WHEN bitis IS NULL OR bitis='0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS open_cnt,
                                      SUM(CASE WHEN bitis IS NOT NULL AND bitis<>'0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS closed_cnt
                                    FROM enerji_operasyonlar
                                ");
                                if ($res) {
                                    if ($row = $res->fetch_row()) {
                                        $openTotalAll   = (int)$row[0];
                                        $closedTotalAll = (int)$row[1];
                                    }
                                    $res->free();
                                }

                                // EKLENDİ: Açık operasyonların şu ana kadar toplam süresi
                                $nowTs = time();
                                $res2 = @$authDb->query("
                                    SELECT baslangic
                                    FROM enerji_operasyonlar
                                    WHERE bitis IS NULL OR bitis='0000-00-00 00:00:00'
                                ");
                                if ($res2) {
                                    while ($r = $res2->fetch_row()) {
                                        $s = strtotime((string)$r[0]);
                                        if (is_int($s) && $s <= $nowTs) {
                                            $openSecNow += ($nowTs - $s);
                                        }
                                    }
                                    $res2->free();
                                }
                            }
                        } else {
                            $errors[] = 'Operasyon sorgusu hazırlanamadı: '.$authDb->error;
                        }
                    } else {
                        $errors[] = 'auth_db() bağlantısı yok (operasyonlar).';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Operasyon okuma hatası: '.$e->getMessage();
                }
            }
            $mysqli->close();
        }
    }
}

// EKLENDİ: Operasyon metrikleri (açık/bitmiş ve kapsama)
$opsCount = is_array($opsOut) ? count($opsOut) : 0;

// Grafik aralığı (özet ve kapsama için) — formdaki aralık esas alınır
$rangeStartTs = strtotime($startDT->format('Y-m-d H:i:s'));
$rangeEndTs   = strtotime($endDT->format('Y-m-d H:i:s'));
if (!is_int($rangeStartTs) || !is_int($rangeEndTs) || $rangeEndTs <= $rangeStartTs) {
    $rangeStartTs = $rangeStartTs ?: time();
    $rangeEndTs   = max($rangeStartTs + 1, $rangeEndTs ?: ($rangeStartTs + 1));
}

$openCount = 0; $closedCount = 0;
$openSec = 0;   $closedSec = 0;
$intervals = [];

if ($opsCount > 0) {
    foreach ($opsOut as $op) {
        $s = strtotime((string)$op['baslangic']);

        // Normalize bitiş
        $bitisRaw = $op['bitis'] ?? null;
        if ($bitisRaw !== null) $bitisRaw = trim((string)$bitisRaw);
        $isOpen = ($bitisRaw === null || $bitisRaw === '' || $bitisRaw === '0000-00-00 00:00:00' || $bitisRaw === '1970-01-01 00:00:00');

        $e = $isOpen ? $rangeEndTs : strtotime((string)$bitisRaw);
        if (!is_int($s) || !is_int($e)) continue;

        // Aralık kesişimi
        $a = max($s, $rangeStartTs);
        $b = min($e, $rangeEndTs);
        if ($b <= $a) continue;

        if ($isOpen) { $openCount++; $openSec += ($b - $a); }
        else { $closedCount++; $closedSec += ($b - $a); }

        $intervals[] = [$a, $b];
    }
}

// (İsteğe bağlı debug)
if (isset($_GET['debug'])) {
    $openRawCnt = 0;
    foreach ($opsOut as $op) {
        $r = $op['bitis'] ?? null; if ($r !== null) $r = trim((string)$r);
        if ($r === null || $r === '' || $r === '0000-00-00 00:00:00' || $r === '1970-01-01 00:00:00') $openRawCnt++;
    }
    $notes[] = "Debug: Açık(ham): $openRawCnt, Açık(kesişen): $openCount, Toplam ops: $opsCount";
}

// Grafik aralığı (özet ve kapsama için) — formdaki aralık esas alınır
$rangeStartTs = strtotime($startDT->format('Y-m-d H:i:s'));
$rangeEndTs   = strtotime($endDT->format('Y-m-d H:i:s'));
if (!is_int($rangeStartTs) || !is_int($rangeEndTs) || $rangeEndTs <= $rangeStartTs) {
    $rangeStartTs = $rangeStartTs ?: time();
    $rangeEndTs   = max($rangeStartTs + 1, $rangeEndTs ?: ($rangeStartTs + 1));
}

$openCount = 0; $closedCount = 0;
$openSec = 0;   $closedSec = 0;
$intervals = [];

if ($opsCount > 0) {
    foreach ($opsOut as $op) {
        $s = strtotime((string)$op['baslangic']);

        // Normalize bitiş
        $bitisRaw = $op['bitis'] ?? null;
        if ($bitisRaw !== null) $bitisRaw = trim((string)$bitisRaw);
        $isOpen = ($bitisRaw === null || $bitisRaw === '' || $bitisRaw === '0000-00-00 00:00:00' || $bitisRaw === '1970-01-01 00:00:00');

        $e = $isOpen ? $rangeEndTs : strtotime((string)$bitisRaw);
        if (!is_int($s) || !is_int($e)) continue;

        // Aralık kesişimi
        $a = max($s, $rangeStartTs);
        $b = min($e, $rangeEndTs);
        if ($b <= $a) continue;

        if ($isOpen) { $openCount++; $openSec += ($b - $a); }
        else { $closedCount++; $closedSec += ($b - $a); }

        $intervals[] = [$a, $b];
    }
}

// (İsteğe bağlı debug)
if (isset($_GET['debug'])) {
    $openRawCnt = 0;
    foreach ($opsOut as $op) {
        $r = $op['bitis'] ?? null; if ($r !== null) $r = trim((string)$r);
        if ($r === null || $r === '' || $r === '0000-00-00 00:00:00' || $r === '1970-01-01 00:00:00') $openRawCnt++;
    }
    $notes[] = "Debug: Açık(ham): $openRawCnt, Açık(kesişen): $openCount, Toplam ops: $opsCount";
}

// Kapsama: aralıkların birleşimi
$coveredSec = 0;
if (!empty($intervals)) {
    usort($intervals, fn($x,$y)=> $x[0] <=> $y[0]);
    [$cs, $ce] = $intervals[0];
    for ($i=1; $i<count($intervals); $i++) {
        [$s,$e] = $intervals[$i];
        if ($s <= $ce) { $ce = max($ce, $e); }
        else { $coveredSec += ($ce - $cs); [$cs,$ce] = [$s,$e]; }
    }
    $coveredSec += ($ce - $cs);
}

$totalSec = max(1, $rangeEndTs - $rangeStartTs);
$percentOp   = round($coveredSec * 100 / $totalSec, 1);
$percentIdle = round(100 - $percentOp, 1);
$percentOpStr   = number_format($percentOp, 1, ',', '.');
$percentIdleStr = number_format($percentIdle, 1, ',', '.');

// ---- GRAFİK HAZIRLIK ----
// (Henüz hata yoksa) başlangıç/bitiş tarihlerini kontrol et
if(empty($errors)){
    foreach(['start'=>$startDT,'end'=>$endDT] as $k=>$dt){
        if($dt->format('Y') < 2000 || $dt->format('Y') > 2100){
            $errors[] = ucfirst($k).' tarihi geçersiz: '.h($dt->format('Y-m-d H:i'));
        }
    }
}

// ---- TEMPLATE ----
$genMs = round((microtime(true)-$started)*1000,2);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Grafikler</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/app.css">
  <!-- Grafikler için gerekli scriptler -->
  <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js" defer></script>
  <style>
    .canvas-wrap{position:relative;height:330px}
    @media (max-width:800px){.canvas-wrap{height:260px}}

    /* Daha şık ve küçük cihaz filtreleri */
    .dev-filter{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      margin:6px 0 8px;
    }
    .dev-filter label{
      display:inline-flex;
      align-items:center;
      gap:8px;
      font-size:12px;
      color:#1f2937;
      background:#f8fafc;
      border:1px solid #e5e7eb;
      padding:6px 10px;
      border-radius:999px;
      cursor:pointer;
      line-height:1;
      transition:background-color .15s, border-color .15s, box-shadow .15s, color .15s;
      user-select:none;
    }
    .dev-filter label:hover{
      background:#f1f5f9;
      border-color:#cbd5e1;
      box-shadow:0 1px 0 rgba(0,0,0,0.03);
    }
    /* Seçili görünüm (JS ile .active sınıfı ekleniyor) */
    .dev-filter label.active{
      background:#e0f2fe;
      border-color:#7dd3fc;
      color:#0c4a6e;
    }
    .dev-filter input[type=checkbox]{
      width:14px;
      height:14px;
      accent-color:#0ea5e9; /* Edge/Chrome destekli */
      transform: translateY(0.5px);
    }
    .dev-filter .txt{
      max-width:260px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }

    /* OVERRIDE: Cihaz listesi görünümü — kutu yok, kompakt */
    .dev-filter { gap: 6px; }
    .dev-filter label{
      background: transparent !important;
      border: none !important;
      padding: 0 2px;
      border-radius: 6px;
      box-shadow: none !important;
      color:#334155;            /* slate-700 */
      gap:6px;
      line-height:1.1;
      font-size:12px;
      cursor: pointer;
    }
    .dev-filter label:hover{ text-decoration: underline; }
    .dev-filter label.active{
      color:#0c4a6e;            /* cyan-900 */
      font-weight:600;
    }

    /* OVERRIDE: Cihaz checkbox'ları — custom, küçük ve net */
    .dev-filter input[type=checkbox]{
      appearance: none;
      -webkit-appearance: none;
      width: 14px;
      height: 14px;
      margin: 0;
      display:inline-block;
      vertical-align:middle;
      border: 1.5px solid #94a3b8; /* slate-400 */
      border-radius: 3px;
      background:#ffffff;
      position: relative;
      transition: border-color .15s, background-color .15s, box-shadow .15s;
    }
    .dev-filter input[type=checkbox]:hover{
      border-color:#64748b;      /* slate-500 */
    }
    .dev-filter input[type=checkbox]:focus-visible{
      outline: 2px solid #38bdf8; /* sky-400 */
      outline-offset: 2px;
    }
    .dev-filter input[type=checkbox]:checked{
      background:#0ea5e9;        /* sky-500 */
      border-color:#0ea5e9;
    }
    .dev-filter input[type=checkbox]:checked::after{
      content:'';
      position:absolute;
      left:3px; top:1px;
      width:6px; height:10px;
      border:2px solid #fff;
      border-top:none; border-left:none;
      transform: rotate(45deg);
    }

    .dev-filter .txt{
      max-width:260px;
      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }

    /* Daraltılmış tek satır görünüm */
    .dev-filter.collapsed{ display:block; white-space:nowrap; overflow-x:auto; scrollbar-width: thin; padding-bottom:2px; }
    .dev-filter.collapsed label{ display:inline-flex; margin-right:10px; }
    .dev-filter.collapsed .txt{ max-width:none; white-space:nowrap; }

    /* Buton seti düzeni */
    .dev-filter-tools{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:6px; }

    /* Genel buton stilleri (kontrastı garanti et) */
    .btn{
      appearance:none;
      -webkit-appearance:none;
      font-size:12px;
      line-height:1;
      padding:6px 12px;
      border-radius:8px;
      border:1px solid #cbd5e1;   /* slate-300 */
      background:#f8fafc;          /* slate-50 */
      color:#0f172a;               /* slate-900 */
      cursor:pointer;
      transition: background-color .15s, border-color .15s, color .15s, box-shadow .15s;
    }
    .btn:hover{ background:#e2e8f0; border-color:#94a3b8; }

    .btn-primary{
      background:#0ea5e9; border-color:#0ea5e9; color:#ffffff;
    }
    .btn-primary:hover{ background:#0284c7; border-color:#0284c7; }

    .btn-outline{
      background:#ffffff; color:#0f172a; border-color:#cbd5e1;
    }
    .btn-outline:hover{ background:#f1f5f9; border-color:#94a3b8; }

    .btn-ghost{
      background:transparent; border-color:transparent; color:#334155;
    }
    .btn-ghost:hover{ background:#f8fafc; border-color:#e5e7eb; }

    .btn-xs{ font-size:11px; padding:4px 8px; border-radius:7px; }

    .quick-buttons .btn{ margin:2px 0; }
    .section-title{ font-size:12px; color:#334155; margin:6px 0 2px; }

    /* Üç kolonlu filtre ızgarası ve kutu stilleri */
    .filters-grid{
      display:grid;
      grid-template-columns: 2fr 3fr 3fr; /* 1/6, 1/4, 1/4 */
      gap:12px;
      align-items: stretch; /* DEĞİŞTİ: start -> stretch (yükseklik eşitle) */
      margin-bottom:12px;
    }
    @media (max-width:1100px){
      .filters-grid{ grid-template-columns: 1fr; }
    }
    .box{
      background: var(--panel-bg) !important;
      border-color: var(--panel-border) !important;
    }
    .device-list{
      background: var(--card-bg) !important;
      border-color: var(--panel-border) !important;
    }
    .box h3{
      /* başlık ile gövde arasına çok hafif ayrım */
      border-bottom: 1px solid rgba(0,0,0,0.04);
      padding-bottom: 6px;
      margin-bottom: 10px;
    }

    /* Listbox (çoklu seçim) */
    .device-select{
      width:100%;
      min-height: 260px; /* görünür satır sayısı */
      border:1px solid #cbd5e1;
      border-radius:6px;
      background:#fff;
      font-size:12px;
      padding:6px;
    }
    .device-select option{
      padding:4px 6px;
      border-bottom:1px solid #f1f5f9;
    }
    .device-select option:last-child{ border-bottom:none; }

    .stack{ display:flex; flex-direction:column; gap:8px; }
    .row{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .field{ display:flex; flex-direction:column; gap:6px; }
    .field input[type="datetime-local"]{
      width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px;
    }
  </style>
  <!-- parseTs helper script kaldırıldı; tek kopyası altta "Yardımcılar" bölümünde -->
</head>
<body>
  <?php require __DIR__.'/partials/topnav.php'; ?>
  <div class="container">
<h1>Ölçüm Grafikleri</h1>

  <?php if(!empty($errors)): ?>
      <div class="panel errors">
          <strong>Hatalar (işlem tamamlandı fakat sorunlar var):</strong>
          <ul>
              <?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
          </ul>
      </div>
  <?php endif; ?>

  <!-- Özet paneli buradan kaldırıldı -->

<form method="get" class="filter" novalidate>
  <?php
    $selectedIds = array_map('intval', $_GET['dev'] ?? []);
    $autoAll = empty($_GET['dev']);
  ?>
  <div class="filters-grid">
    <!-- SOL: Cihaz kutusu -->
    <div class="box">
      <h3>
        Cihazlar
        <span id="devSelCount" class="muted">Seçili: 0/0</span>
      </h3>
      <div class="device-list">
        <select id="deviceSelect" class="device-select" name="dev[]" multiple>
          <?php foreach($devicesOut as $d):
            $checked = $autoAll || in_array($d['cihaz_id'], $selectedIds, true);
            $label = (string)$d['cihaz_id'].' - '.mb_strimwidth($d['label'],0,60,'…','UTF-8');
          ?>
            <option value="<?= (int)$d['cihaz_id'] ?>" <?= $checked? 'selected':'' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row" style="margin-top:10px">
        <button type="button" class="btn btn-outline" onclick="selectAllDevices()">Tümünü Seç</button>
        <button type="button" class="btn btn-ghost" onclick="clearAllDevices()">Temizle</button>
        <div style="flex:1"></div>
        <button type="submit" class="btn btn-primary">Uygula</button>
      </div>
    </div>

    <!-- ORTA: Tarih alanları -->
    <div class="box">
      <h3>Tarih Aralığı</h3>
      <div class="stack">
        <div class="field">
          <label>Başlangıç</label>
          <input type="datetime-local" name="start" value="<?= $useDateFilter ? h($startDT->format('Y-m-d\TH:i')) : '' ?>">
        </div>
        <div class="field">
          <label>Bitiş</label>
          <input type="datetime-local" name="end" value="<?= $useDateFilter ? h($endDT->format('Y-m-d\TH:i')) : '' ?>">
        </div>
        <div class="row" style="margin-top:2px">
          <button type="submit" class="btn btn-primary">Filtrele</button>
          <button type="button" class="btn btn-outline" onclick="allData()" title="Tüm veriyi göster">Tüm Veri</button>
        </div>
      </div>
    </div>

    <!-- SAĞ: Hızlı aralık butonları -->
    <div class="box">
      <h3>Hızlı Aralıklar</h3>
      <div class="quick-buttons">
        <div class="section-title">Dakika</div>
        <div class="row">
          <button type="button" class="btn btn-outline btn-xs" onclick="lastMinutes(15)">Son 15 dk</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastMinutes(30)">Son 30 dk</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastMinutes(60)">Son 60 dk</button>
        </div>

        <div class="section-title">Saat</div>
        <div class="row">
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(1)">1s</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(6)">6s</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(12)">12s</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(24)">24s</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(48)">48s</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastHours(72)">72s</button>
        </div>

        <div class="section-title">Gün</div>
        <div class="row">
          <button type="button" class="btn btn-outline btn-xs" onclick="lastDays(7)">Son 7 gün</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastDays(30)">Son 30 gün</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastDays(90)">Son 90 gün</button>
        </div>

        <div class="section-title">Takvim</div>
        <div class="row">
          <button type="button" class="btn btn-outline btn-xs" onclick="today()">Bugün</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="yesterday()">Dün</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="thisWeek()">Bu Hafta</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastWeek()">Geçen Hafta</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="thisMonth()">Bu Ay</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastMonth()">Geçen Ay</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="thisQuarter()">Bu Çeyrek</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="ytd()">YBG</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="thisYear()">Bu Yıl</button>
          <button type="button" class="btn btn-outline btn-xs" onclick="lastYear()">Geçen Yıl</button>
        </div>

        <div class="section-title">Operasyon</div>
        <div class="row">
          <label class="tag" style="display:flex;align-items:center;gap:6px">
            <input type="checkbox" id="opsToggle" checked> Operasyonları göster
          </label>
          <span class="tag" id="opsCountTag" title="Seçili aralıktaki operasyon sayısı" style="margin-left:6px">
            Ops: <span id="opsCount">0</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</form>

    <!-- Charts konteyneri mutlaka olmalı -->
    <div id="charts"></div>

    <!-- Özet paneli buraya taşındı -->
    <div class="panel">
        <strong>Özet:</strong>
        <ul>
            <li>Config yolu: <?= h($configPathUsed ?? 'bulunamadı') ?></li>
            <li>Cihaz sayısı: <?= count($devicesOut) ?></li>
            <li>Üretim süresi: <?= $genMs ?> ms</li>
            <li>Filtre: <?= $useDateFilter ? (h($startDT->format('Y-m-d H:i')).' → '.h($endDT->format('Y-m-d H:i'))) : 'Uygulanmadı (tüm veri)' ?></li>
            <?php if($opsCount>0 || $openTotalAll>0 || $closedTotalAll>0): ?>
                <li>
                  Açık operasyonlar: <?= (int)$openTotalAll ?> adet — Açık süre (şu an): <?= fmt_hm($openSecNow) ?>
                  <?php if(($openSec??0)>0): ?> — Aralıkta: <?= fmt_hm($openSec) ?><?php endif; ?>
                </li>
                <li>Bitmiş operasyonlar: <?= (int)$closedTotalAll ?> adet — Aralıkta: <?= fmt_hm($closedSec) ?></li>
            <?php else: ?>
                <li>Operasyon: Bu aralıkta yok</li>
            <?php endif; ?>
            <li>Kapsama: Operasyonda <?= h($percentOpStr) ?>% — Boşta <?= h($percentIdleStr) ?>%</li>
            <?php foreach($notes as $n): ?><li>Not: <?= h($n) ?></li><?php endforeach; ?>
        </ul>
        <?php if(isset($_GET['debug']) && !empty($GLOBALS['__debugFirstRows'])): ?>
            <details style="margin-top:8px"><summary style="cursor:pointer">İlk ve Son 5 Kayıt (debug)</summary>
                <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:8px">
                    <div>
                        <div style="font-weight:bold;font-size:12px;margin-bottom:4px">İlk 5</div>
                        <table border="1" cellspacing="0" cellpadding="3" style="font-size:11px;border-collapse:collapse;background:#fff">
                            <tr><th>Zaman</th><th>Cihaz</th><th>Adres</th><th>Ad</th><th>Değer</th></tr>
                            <?php foreach($GLOBALS['__debugFirstRows'] as $r): ?>
                                <tr>
                                    <td><?= h($r[0]) ?></td><td><?= (int)$r[1] ?></td><td><?= (int)$r[2] ?></td><td><?= h($r[3]) ?></td><td><?= h((string)$r[4]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div>
                        <div style="font-weight:bold;font-size:12px;margin-bottom:4px">Son 5</div>
                        <table border="1" cellspacing="0" cellpadding="3" style="font-size:11px;border-collapse:collapse;background:#fff">
                            <tr><th>Zaman</th><th>Cihaz</th><th>Adres</th><th>Ad</th><th>Değer</th></tr>
                            <?php foreach($GLOBALS['__debugLastRows'] as $r): ?>
                                <tr>
                                    <td><?= h($r[0]) ?></td><td><?= (int)$r[1] ?></td><td><?= (int)$r[2] ?></td><td><?= h($r[3]) ?></td><td><?= h((string)$r[4]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </details>
        <?php endif; ?>
    </div>
  </div> <!-- .container kapanışı -->

  <script>
    // Veri köprüleri (PHP -> JS)
    const DEVICES = <?= json_encode($devicesOut, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    const USE_DATE_FILTER = <?= $useDateFilter ? 'true' : 'false' ?>;
    const RANGE_START_ISO = '<?= h($startDT->format('Y-m-d H:i:s')) ?>';
    const RANGE_END_ISO   = '<?= h($endDT->format('Y-m-d H:i:s')) ?>';
    const OPS_FROM_SERVER = <?= json_encode($opsOut, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    let chartInstances = {};
    let OPS_CACHE = [];
    let OPS_ENABLED = true;
    let DEV_FILTER_COLLAPSED = false; // kullanılmıyor ama kalsın

    // EKLENDİ: OPS_FROM_SERVER -> OPS_CACHE (ISO T ile)
    OPS_CACHE = (OPS_FROM_SERVER || []).map(o => ({
      id: o.id,
      name: o.ad,
      start: (o.baslangic || '').replace(' ','T'),
      end: (o.bitis ? o.bitis.replace(' ','T') : RANGE_END_ISO.replace(' ','T')),
      qty: o.miktar,
      unit: o.birim
    }));

    // Yardımcılar
    function parseTs(str){
      if(!str) return NaN;
      return Date.parse(String(str).replace(' ','T'));
    }
    function fmt(d){ const z=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`; }
    function selectedDeviceIds(){
      const sel = document.getElementById('deviceSelect');
      if (sel) {
        const all = [...sel.options].map(o=>parseInt(o.value,10)).filter(n=>!isNaN(n));
        const chosen = [...sel.options].filter(o=>o.selected).map(o=>parseInt(o.value,10)).filter(n=>!isNaN(n));
        return chosen.length ? chosen : all;
      }
      // Geriye dönük: checkbox varsa
      const box=document.getElementById('deviceFilter'); 
      if(!box) return (DEVICES||[]).map(d=>d.cihaz_id);
      const ids=[...box.querySelectorAll('input[type=checkbox]')].filter(i=>i.checked).map(i=>parseInt(i.value,10));
      return ids.length? ids : (DEVICES||[]).map(d=>d.cihaz_id);
    }

    // EKLENDİ: Cihaz seçimi yardımcıları
    function refreshSelectedCount(){
      const total = (DEVICES||[]).length;
      const selCount = selectedDeviceIds().length;
      const el = document.getElementById('devSelCount');
      if (el) el.textContent = `Seçili: ${selCount}/${total}`;
    }
    function initDeviceFilter(){
      const sel = document.getElementById('deviceSelect');
      if (sel) sel.addEventListener('change', refreshSelectedCount);
      refreshSelectedCount();
    }
    function selectAllDevices(){
      const sel=document.getElementById('deviceSelect'); if(!sel) return;
      [...sel.options].forEach(o=>o.selected=true);
      refreshSelectedCount();
    }
    function clearAllDevices(){
      const sel=document.getElementById('deviceSelect'); if(!sel) return;
      [...sel.options].forEach(o=>o.selected=false);
      refreshSelectedCount();
    }
    function refreshDeviceChips(){ /* listbox kullanılıyor; no-op */ }

    function buildUrl(start, end){
      const params=new URLSearchParams();
      if(start) params.set('start', start);
      if(end)   params.set('end',   end);
      const ids=selectedDeviceIds();
      ids.forEach(id=>params.append('dev[]', String(id)));
      const qs=params.toString();
      return 'grafikler1.php' + (qs? ('?'+qs):'');
    }
    function goRangeStartEnd(startDate, endDate){
      const s = fmt(startDate), e = fmt(endDate);
      window.location = buildUrl(s,e);
    }
    function allData(){
      // Start/end olmadan, dev[] korunarak
      window.location = buildUrl(null,null);
    }
    // Hızlı aralıklar
    function now(){ return new Date(); }
    function startOfDay(d){ const x=new Date(d); x.setHours(0,0,0,0); return x; }
    function endOfDay(d){ const x=new Date(d); x.setHours(23,59,0,0); return x; }
    function lastMinutes(m){ const e=now(); const s=new Date(e.getTime()-m*60000); goRangeStartEnd(s,e); }
    function lastHours(h){ const e=now(); const s=new Date(e.getTime()-h*3600*1000); goRangeStartEnd(s,e); }
    function lastDays(g){ const e=now(); const s=new Date(e.getTime()-g*24*3600*1000); goRangeStartEnd(s,e); }
    function today(){ const e=now(); goRangeStartEnd(startOfDay(e), endOfDay(e)); }
    function yesterday(){ const d=new Date(now().getTime()-24*3600*1000); goRangeStartEnd(startOfDay(d), endOfDay(d)); }
    // Haftanın ilk günü Pazartesi
    function getWeekRange(d){
      const x=new Date(d); const day=(x.getDay()+6)%7; // 0=Mon
      const s=new Date(x); s.setDate(x.getDate()-day); s.setHours(0,0,0,0);
      const e=new Date(s); e.setDate(s.getDate()+6); e.setHours(23,59,0,0);
      return [s,e];
    }
    function thisWeek(){ const [s,e]=getWeekRange(new Date()); goRangeStartEnd(s,e); }
    function lastWeek(){ const [s,e]=getWeekRange(new Date(new Date().getTime()-7*24*3600*1000)); goRangeStartEnd(s,e); }
    function thisMonth(){
      const n=now(); const s=new Date(n.getFullYear(), n.getMonth(), 1, 0,0,0,0);
      const e=new Date(n.getFullYear(), n.getMonth()+1, 0, 23,59,0,0);
      goRangeStartEnd(s,e);
    }
    function lastMonth(){
      const n=now(); const s=new Date(n.getFullYear(), n.getMonth()-1, 1, 0,0,0,0);
      const e=new Date(n.getFullYear(), n.getMonth(), 0, 23,59,0,0);
      goRangeStartEnd(s,e);
    }
    function thisQuarter(){
      const n=now(); const qStartMonth = Math.floor(n.getMonth()/3)*3;
      const s=new Date(n.getFullYear(), qStartMonth, 1, 0,0,0,0);
      const e=new Date(n.getFullYear(), qStartMonth+3, 0, 23,59,0,0);
      goRangeStartEnd(s,e);
    }
    function ytd(){
      const n=now(); const s=new Date(n.getFullYear(),0,1,0,0,0,0);
      const e=endOfDay(n);
      goRangeStartEnd(s,e);
    }
    function thisYear(){
      const n=now(); const s=new Date(n.getFullYear(),0,1,0,0,0,0);
      const e=new Date(n.getFullYear(),11,31,23,59,0,0);
      goRangeStartEnd(s,e);
    }
    function lastYear(){
      const n=now(); const y=n.getFullYear()-1;
      const s=new Date(y,0,1,0,0,0,0);
      const e=new Date(y,11,31,23,59,0,0);
      goRangeStartEnd(s,e);
    }

    // Renk yardımcı
    function hexToRgba(hex, a){
      if(!hex || !hex.startsWith('#')) return hex;
      const v = hex.length===4
        ? hex.slice(1).split('').map(x=>parseInt(x+x,16))
        : [hex.slice(1,3),hex.slice(3,5),hex.slice(5,7)].map(x=>parseInt(x,16));
      return `rgba(${v[0]},${v[1]},${v[2]},${a})`;
    }

    // Gece bantları (19:00-07:00) -> ECharts markArea
    function buildNightAreas(startIso, endIso){
      const items=[];
      const s = new Date(startIso.replace(' ','T'));
      const e = new Date(endIso.replace(' ','T'));
      if(!(s instanceof Date) || !(e instanceof Date) || isNaN(+s) || isNaN(+e)) return items;
      const d0 = new Date(s.getFullYear(), s.getMonth(), s.getDate());
      for(let d=new Date(d0); d<=e; d.setDate(d.getDate()+1)){
        const n1 = new Date(d.getFullYear(), d.getMonth(), d.getDate(), 19, 0, 0);
        const n2 = new Date(d.getFullYear(), d.getMonth(), d.getDate()+1, 7, 0, 0);
        const a = Math.max(+n1, +s), b = Math.min(+n2, +e);
        if(b > a) items.push([{ xAxis: new Date(a).toISOString().slice(0,19).replace('T','T') }, { xAxis: new Date(b).toISOString().slice(0,19).replace('T','T') }]);
      }
      return items;
    }

    // Operasyon alanları -> ECharts markArea + markLine
    function buildOpAreas(ops){
      const areas = [];
      const lines = [];
      (ops||[]).forEach(o=>{
        if(!o.start || !o.end) return;
        areas.push([{ xAxis:o.start }, { xAxis:o.end }]);
        lines.push({ xAxis:o.start }, { xAxis:o.end });
      });
      return { areas, lines };
    }

    // Chart.js eklentilerini kaydetme fonksiyonu artık kullanılmıyor
    function bootPlugins(){}

    // ECharts ile grafik kur
    function buildCharts(){
      const container=document.getElementById('charts'); if(!container) return;
      // Eski chartları temizle
      Object.values(chartInstances).forEach(({chart})=>{ try{ chart.dispose(); }catch(e){} });
      chartInstances={}; container.innerHTML='';

      const ids=new Set(selectedDeviceIds());
      const minIso = USE_DATE_FILTER ? RANGE_START_ISO.replace(' ','T') : null;
      const maxIso = USE_DATE_FILTER ? RANGE_END_ISO.replace(' ','T') : null;

      const palette=['#1d9bf0','#14c9b0','#ff8a00','#ff5d6c','#8b5cf6','#22c55e','#f59e0b'];

      (DEVICES||[]).forEach(dev=>{
        if(!ids.has(dev.cihaz_id)) return;

        const wrap=document.createElement('div'); wrap.className='device';
        const title=document.createElement('h2'); title.textContent=dev.label || ('Cihaz '+dev.cihaz_id);
        const cwrap=document.createElement('div'); cwrap.className='canvas-wrap';
        const div=document.createElement('div'); div.style.width='100%'; div.style.height='330px';
        cwrap.appendChild(div);
        wrap.appendChild(title); wrap.appendChild(cwrap); container.appendChild(wrap);

        // Veri serileri
        const series = (dev.series||[]).map((s,idx)=>{
          const col = palette[idx%palette.length];
          return {
            name: s.name || ('Seri '+(idx+1)),
            type: 'line',
            showSymbol: false,
            smooth: 0.25,
            sampling: 'lttb',
            progressive: 800,                // EKLENDİ: performans
            progressiveThreshold: 5000,      // EKLENDİ: performans
            lineStyle: { width: 1.6, color: col },
            areaStyle: { color: new echarts.graphic.LinearGradient(0,0,0,1,[
              {offset:0, color: hexToRgba(col, 0.28)},
              {offset:1, color: hexToRgba(col, 0.05)}
            ]) },
            // EKLENDİ: vurgulama/blur
            emphasis: { focus: 'series', lineStyle:{ width: 2.6 } },
            blur:     { lineStyle:{ opacity: 0.25 }, itemStyle:{ opacity: 0.25 }, areaStyle:{ opacity: 0.15 } },
            data: Array.isArray(s.data) ? s.data.map(p=>[p.x, p.y]) : []
          };
        });

        // Gece bantları
        const nightAreas = buildNightAreas(RANGE_START_ISO, RANGE_END_ISO);
        if(nightAreas.length){
          series.push({
            name:'Gece',
            type:'line',
            data:[],
            silent:true,
            markArea:{
              itemStyle:{ color:'rgba(45,62,80,0.16)' },
              data: nightAreas
            }
          });
        }

        // Operasyon overlay
        if(OPS_ENABLED && OPS_CACHE.length){
          const {areas, lines} = buildOpAreas(OPS_CACHE);
          series.push({
            name:'Operasyon',
            type:'line',
            data:[],
            silent:true,
            markArea:{ itemStyle:{ color:'rgba(20,201,176,0.16)' }, data: areas },
            markLine:{
              symbol:'none',
              lineStyle:{ color:'rgba(200,0,0,0.7)', width:1 },
              data: lines
            }
          });
        }

        const option = {
          color: palette,
          // Legend + selector için ekstra üst boşluk
          grid:{ left:48, right:18, top:72, bottom:44 },
          // Üstteki “tarih” etiketinin (axisPointer label) okunabilir olması için stil
          axisPointer:{
            link: [{ xAxisIndex: 'all' }],
            label: {
              show: true,
              backgroundColor: 'rgba(2,6,23,0.92)',
              color: '#e2e8f0',
              borderColor: 'rgba(148,163,184,0.35)',
              borderWidth: 1,
              padding: [4,6],
              shadowBlur: 0
            },
            lineStyle: { color:'#94a3b8', width:1, type:'dashed' },
            crossStyle:{ color:'#94a3b8' }
          },
           tooltip:{
             trigger:'axis',
             order: 'valueDesc',             // EKLENDİ: büyükten küçüğe sırala
            axisPointer:{ type:'cross' }    // cross davranışı aktif
           },
          // EKLENDİ: legend scroll + selector
          legend:{
            type:'scroll',
            top: 4,
            left: 8,
            padding: [0, 0, 8, 0],   // legend altına boşluk
            itemGap: 14,
            pageIconColor:'#0ea5e9',
            pageTextStyle:{ color:'#334155' },
            selector: [{type:'all', title:'Tümünü seç'}, {type:'inverse', title:'Tersini seç'}],
            // Tarayıcı/tema farklarında okunaklı metin
            textStyle:{ color:'#334155' }
          },
          toolbox:{ show:true, right:8, feature:{ saveAsImage:{}, dataZoom:{}, restore:{} } },
          xAxis:{
            type:'time',
            min: minIso || undefined,
            max: maxIso || undefined,
            axisLabel:{ color:'#334155' },
            axisLine:{ lineStyle:{ color:'#cbd5e1' } },
            splitLine:{ lineStyle:{ color:'rgba(148,163,184,0.25)' } }
          },
          yAxis:{
            type:'value',
            scale: true,                     // EKLENDİ: 0’a sabitleme yok, daha dengeli görünüm
            axisLabel:{ color:'#334155' },
            axisLine:{ lineStyle:{ color:'#cbd5e1' } },
            splitLine:{ lineStyle:{ color:'rgba(148,163,184,0.25)' } }
          },
          dataZoom:[
            { type:'inside', xAxisIndex:0 },
            { type:'slider', xAxisIndex:0, height:18 }
          ],
          animationDuration: 600,            // EKLENDİ: hafif animasyon
          stateAnimation: { duration: 200 }, // EKLENDİ: hover/legend geçişi akıcı
          series
        };

        const ec = echarts.init(div, null, {renderer:'canvas'});
        ec.setOption(option);
        window.addEventListener('resize', ()=> ec.resize());
        chartInstances[dev.cihaz_id]={ chart: ec, wrapper: wrap };
      });

      // EKLENDİ: Tüm grafiklerde senkron zoom/tooltip
      try{
        const toConnect = Object.values(chartInstances).map(o=>o.chart);
        if (toConnect.length > 1) echarts.connect(toConnect);
      }catch(_){}

      console.log('ECharts kurulum tamam. Cihaz:', Object.keys(chartInstances).length);
    }
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof echarts === 'undefined') {
        console.error('ECharts yüklenemedi');
        const c = document.getElementById('charts');
        if (c) c.innerHTML = '<div class="panel errors">ECharts yüklenemedi. İnternet bağlantısını veya CDN erişimini kontrol edin.</div>';
        return;
      }
      initDeviceFilter();
      refreshDeviceChips(); // no-op
      const chk=document.getElementById('opsToggle');
      if(chk){ OPS_ENABLED = chk.checked; chk.addEventListener('change', ()=>{ OPS_ENABLED = chk.checked; buildCharts(); }); }
      buildCharts();
    });
  </script>
  <script src="assets/app.js"></script>
</body>
</html>
