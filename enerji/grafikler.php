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
  </style>
  <script>
    // Zaman parse helper (min/max ve opsOverlay kullanıyor)
    function parseTs(str){
      if(!str) return NaN;
      return Date.parse(String(str).replace(' ','T'));
    }
  </script>
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
    <label>Başlangıç
    <input type="datetime-local" name="start" value="<?= $useDateFilter ? h($startDT->format('Y-m-d\TH:i')) : '' ?>">
    </label>
    <label>Bitiş
    <input type="datetime-local" name="end" value="<?= $useDateFilter ? h($endDT->format('Y-m-d\TH:i')) : '' ?>">
    </label>
    <button type="submit" class="btn btn-primary">Filtrele</button>
    <button type="button" class="btn btn-outline" onclick="window.location='grafikler.php'" title="Tüm veriyi göster">Tüm Veri</button>
    <span class="range-btns">
        <button type="button" class="btn btn-xs btn-outline" onclick="qr(1)">1s</button>
        <button type="button" class="btn btn-xs btn-outline" onclick="qr(6)">6s</button>
        <button type="button" class="btn btn-xs btn-outline" onclick="qr(24)">24s</button>
        <button type="button" class="btn btn-xs btn-outline" onclick="qr(72)">72s</button>
    </span>
    <!-- EKLENDİ: Operasyon görünürlüğü ve sayaç -->
    <label class="tag" style="margin-left:8px">
      <input type="checkbox" id="opsToggle" checked style="margin-right:6px"> Operasyonları göster
    </label>
    <span class="tag" id="opsCountTag" title="Seçili aralıktaki operasyon sayısı" style="margin-left:6px">
      Ops: <span id="opsCount">0</span>
    </span>
    <?php
        $selectedIds = array_map('intval', $_GET['dev'] ?? []);
        $autoAll = empty($_GET['dev']);
    ?>
    <?php if(!empty($devicesOut)): ?>
        <div style="flex-basis:100%;height:0"></div>
        <div style="flex-basis:100%">
            <div style="font-size:12px;margin:4px 0 4px 2px;font-weight:bold">Cihazlar:</div>
            <div class="dev-filter" id="deviceFilter">
                <?php foreach($devicesOut as $d):
                    $checked = $autoAll || in_array($d['cihaz_id'], $selectedIds, true);
                ?>
                    <label title="<?= h($d['label']) ?>">
                        <input type="checkbox" name="dev[]" value="<?= (int)$d['cihaz_id'] ?>" <?= $checked? 'checked':'' ?>>
                        <span class="txt"><?= h((string)$d['cihaz_id']) ?> - <?= h((string)mb_strimwidth($d['label'],0,40,'…','UTF-8')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="dev-filter-tools">
                <button type="button" class="btn btn-outline" onclick="selectAllDevices()">Tümünü Seç</button>
                <button type="button" class="btn btn-ghost" onclick="clearAllDevices()">Temizle</button>
                <button type="button" class="btn btn-outline" id="btnToggleDevFilter" onclick="toggleDeviceFilterMode()" title="Cihaz listesini tek satıra daralt/geri al">Daralt</button>
                <span style="font-size:11px;color:#555">(Seçimler otomatik uygulanır)</span>
                <span id="devSelCount" class="tag" style="margin-left:8px">Seçili: 0/0</span>
            </div>
        </div>
    <?php endif; ?>
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
    let DEV_FILTER_COLLAPSED = false; // EKLENDİ: daraltılmış görünüm durumu

    // Operasyon cache ve sayaç
    (function(){
      const endFallback = parseTs(RANGE_END_ISO);
      OPS_CACHE = (Array.isArray(OPS_FROM_SERVER)? OPS_FROM_SERVER: []).map(op=>{
        const s = parseTs(op.baslangic);
        let e = op.bitis ? parseTs(op.bitis) : endFallback;
        if(!isFinite(e) || e < s) e = s;
        // id eklendi
        return { id: (op.id!=null? Number(op.id): null), start:s, end:e, name: op.ad||'', qty: (op.miktar==null? null:Number(op.miktar)), unit: op.birim||'' };
      });
      const cnt = document.getElementById('opsCount'); if (cnt) cnt.textContent = String(OPS_CACHE.length);
      const chk = document.getElementById('opsToggle'); if (chk) OPS_ENABLED = chk.checked;
    })();

    // Gece bantları (opsiyonel)
    const NightBandPlugin = {
      id: 'nightBands',
      beforeDraw(chart){
        const x = chart.scales?.x, area=chart.chartArea;
        if(!x||!area||!isFinite(x.min)||!isFinite(x.max)) return;
        const hour=3600000; let t=x.min-(x.min%hour);
        const ctx=chart.ctx; ctx.save(); ctx.globalAlpha=.12; ctx.fillStyle='#2d3e50';
        while(t<x.max){
          const h=new Date(t).getHours(), night=(h>=19||h<7);
          const t2=t+hour;
          if(night){
            const x1=x.getPixelForValue(t), x2=x.getPixelForValue(t2);
            const L=Math.max(x1,area.left), R=Math.min(x2,area.right);
            if(R>L) ctx.fillRect(L,area.top,R-L,area.bottom-area.top);
          }
          t=t2;
        }
        ctx.restore();
      }
    };

    // Operasyon overlay
    const OperationsOverlayPlugin = {
      id:'opsOverlay',
      afterDatasetsDraw(chart,args,opts){
        if(!opts || !opts.enabled || !Array.isArray(opts.items) || !opts.items.length) return;
        const x=chart.scales?.x, area=chart.chartArea; if(!x||!area) return;
        const ctx=chart.ctx; ctx.save();
        const fills=['rgba(30,144,255,0.16)','rgba(20,201,176,0.16)','rgba(255,165,0,0.14)','rgba(220,20,60,0.12)'];
        // Bantlar
        opts.items.forEach((op,i)=>{
          const x1=x.getPixelForValue(op.start), x2=x.getPixelForValue(op.end);
          if(!isFinite(x1)||!isFinite(x2)) return;
          const L=Math.max(Math.min(x1,x2),area.left), R=Math.min(Math.max(x1,x2),area.right);
          if(R>L) { ctx.fillStyle=fills[i%fills.length]; ctx.fillRect(L,area.top,R-L,area.bottom-area.top); }
        });
        // Başlangıç/bitis çizgileri
        ctx.lineWidth=1;
        opts.items.forEach(op=>{
          const s=x.getPixelForValue(op.start), e=x.getPixelForValue(op.end);
          if(isFinite(s)&&s>=area.left&&s<=area.right){ ctx.strokeStyle='rgba(0,160,0,1)'; ctx.beginPath(); ctx.moveTo(s,area.top); ctx.lineTo(s,area.bottom); ctx.stroke(); }
          if(isFinite(e)&&e>=area.left&&e<=area.right){ ctx.strokeStyle='rgba(200,0,0,1)'; ctx.beginPath(); ctx.moveTo(e,area.top); ctx.lineTo(e,area.bottom); ctx.stroke(); }
        });

        // Etiket yazıları (ad/ID)
        if (opts.showLabels) {
          ctx.font = '11px Segoe UI, Arial, sans-serif';
          opts.items.forEach((op,i)=>{
            const x1=x.getPixelForValue(op.start), x2=x.getPixelForValue(op.end);
            if(!isFinite(x1)||!isFinite(x2)) return;
            const L=Math.max(Math.min(x1,x2),area.left), R=Math.min(Math.max(x1,x2),area.right);
            if(R<=L) return;
            // SADECE İSİM
            const text = (op.name && op.name.trim().length) ? op.name : 'Operasyon';
            const w = Math.ceil(ctx.measureText(text).width) + 10;
            const h = 16;
            let cx = (L + R) / 2 - w/2;
            if (cx < area.left) cx = area.left;
            if (cx + w > area.right) cx = area.right - w;
            const cy = area.top + 6;
            ctx.fillStyle = 'rgba(0,0,0,0.45)';
            ctx.fillRect(cx, cy, w, h);
            ctx.fillStyle = '#ffffff';
            ctx.fillText(text, cx + 5, cy + 12);
          });
        }
        ctx.restore();
      }
    };

    function bootPlugins(){
      if(typeof Chart==='undefined') return;
      try{
        if(!Chart._nightReg){ Chart.register(NightBandPlugin); Chart._nightReg=true; }
        if(!Chart._opsReg){ Chart.register(OperationsOverlayPlugin); Chart._opsReg=true; }
      }catch(e){ console.warn('Plugin register hata:', e); }
    }

    // Seçim yardımcıları
    function selectedDeviceIds(){
      const box=document.getElementById('deviceFilter'); if(!box) return (DEVICES||[]).map(d=>d.cihaz_id);
      const ids=[...box.querySelectorAll('input[type=checkbox]')].filter(i=>i.checked).map(i=>parseInt(i.value,10));
      return ids.length? ids : (DEVICES||[]).map(d=>d.cihaz_id);
    }

    // Etiket görünümlerini (active) güncelle
    function refreshDeviceChips(){
      const box=document.getElementById('deviceFilter'); if(!box) return;
      box.querySelectorAll('label').forEach(l=>{
        const inp=l.querySelector('input[type=checkbox]');
        l.classList.toggle('active', !!(inp && inp.checked));
      });
    }

    // EKLENDİ: Seçili sayacı
    function refreshSelectedCount(){
      const total=(DEVICES||[]).length;
      const sel=selectedDeviceIds().length;
      const el=document.getElementById('devSelCount');
      if(el) el.textContent=`Seçili: ${sel}/${total}`;
    }

    // EKLENDİ: Daralt/Genişlet
    function applyDeviceFilterMode(){
      const box=document.getElementById('deviceFilter'); if(!box) return;
      box.classList.toggle('collapsed', DEV_FILTER_COLLAPSED);
      const btn=document.getElementById('btnToggleDevFilter');
      if(btn) btn.textContent = DEV_FILTER_COLLAPSED ? 'Genişlet' : 'Daralt';
    }
    function toggleDeviceFilterMode(){
      DEV_FILTER_COLLAPSED = !DEV_FILTER_COLLAPSED;
      localStorage.setItem('devFilterCollapsed', DEV_FILTER_COLLAPSED ? '1' : '0');
      applyDeviceFilterMode();
    }

    function initDeviceFilter(){
      const box=document.getElementById('deviceFilter'); if(!box) return;
      // LocalStorage'dan daraltma durumunu yükle
      DEV_FILTER_COLLAPSED = localStorage.getItem('devFilterCollapsed') === '1';
      applyDeviceFilterMode();
      refreshDeviceChips();
      refreshSelectedCount();
      box.addEventListener('change', ()=>{ refreshDeviceChips(); refreshSelectedCount(); buildCharts(); });
    }
    function selectAllDevices(){
      const box=document.getElementById('deviceFilter'); if(!box) return;
      box.querySelectorAll('input[type=checkbox]').forEach(i=>i.checked=true);
      refreshDeviceChips();
      refreshSelectedCount();
      buildCharts();
    }
    function clearAllDevices(){
      const box=document.getElementById('deviceFilter'); if(!box) return;
      box.querySelectorAll('input[type=checkbox]').forEach(i=>i.checked=false);
      refreshDeviceChips();
      refreshSelectedCount();
      buildCharts();
    }
    function qr(h){
      function fmt(d){ const z=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`; }
      const now=new Date(); const start=new Date(now.getTime()-h*3600*1000);
      window.location=`grafikler.php?start=${encodeURIComponent(fmt(start))}&end=${encodeURIComponent(fmt(now))}`;
    }

    function buildCharts(){
      const container=document.getElementById('charts'); if(!container) return;
      // Eski chartları temizle
      Object.values(chartInstances).forEach(({chart})=>{ try{ chart.destroy(); }catch(e){} });
      chartInstances={}; container.innerHTML='';

      const ids=new Set(selectedDeviceIds());
      const min=parseTs(RANGE_START_ISO), max=parseTs(RANGE_END_ISO);

      (DEVICES||[]).forEach(dev=>{
        if(!ids.has(dev.cihaz_id)) return;
        const wrap=document.createElement('div'); wrap.className='device';

        // Operasyon etiketi (.tag) – üstte göster
        if (OPS_ENABLED && OPS_CACHE.length > 0) {
          const tag = document.createElement('div');
          tag.className = 'tag op-tag';
          let txt;
          if (OPS_CACHE.length === 1) {
            const o = OPS_CACHE[0];
            txt = `Operasyon: ${o.name || (o.id!=null? 'ID: '+o.id : '-')}${o.id!=null? ` (ID: ${o.id})` : ''}`;
          } else {
            txt = `Operasyonlar: ${OPS_CACHE.length} adet`;
          }
          tag.textContent = txt;
          tag.title = OPS_CACHE.map(o => (o.name? o.name : (o.id!=null? 'ID: '+o.id : '-')) + (o.id!=null? ` [${o.id}]`:'')).join(', ');
          wrap.appendChild(tag);
        }

        const title=document.createElement('h2'); title.textContent=dev.label || ('Cihaz '+dev.cihaz_id);
        const cwrap=document.createElement('div'); cwrap.className='canvas-wrap';
        const canv=document.createElement('canvas');
        cwrap.appendChild(canv);
        wrap.appendChild(title); wrap.appendChild(cwrap); container.appendChild(wrap);

        const palette=['#1d9bf0','#14c9b0','#ff8a00','#ff5d6c','#8b5cf6','#22c55e','#f59e0b'];
        const datasets=(dev.series||[]).map((s,idx)=>({
          label: s.name || ('Seri '+(idx+1)),
          data: Array.isArray(s.data)? s.data : [],
          parsing: { xAxisKey:'x', yAxisKey:'y' },
          borderColor: palette[idx%palette.length],
          backgroundColor: palette[idx%palette.length]+'33',
          borderWidth: 1,
          pointRadius: 0,
          tension: 0.15
        }));

        const cfg={
          type:'line',
          data:{ datasets },
          options:{
            responsive:true, maintainAspectRatio:false,
            scales:{
              x:{
                type:'time',
                time:{ tooltipFormat:'dd.MM.yyyy HH:mm', displayFormats:{ minute:'dd MMM HH:mm', hour:'dd MMM HH:mm', day:'dd MMM' } },
                min: USE_DATE_FILTER && isFinite(min)? min: undefined,
                max: USE_DATE_FILTER && isFinite(max)? max: undefined,
                ticks:{ autoSkip:true, maxTicksLimit:10 }
              },
              y:{ beginAtZero:false, ticks:{ precision:3 } }
            },
            plugins:{
              legend:{ display:true, position:'top' },
              tooltip:{ intersect:false, mode:'index' },
              nightBands:{},
              // opsOverlay: etiketleri de aktif et
              opsOverlay: (OPS_ENABLED && OPS_CACHE.length>0)
                ? { enabled:true, showLabels:true, items: OPS_CACHE.map(o=>({start:o.start,end:o.end,name:o.name,id:o.id,qty:o.qty,unit:o.unit})) }
                : { enabled:false, items: [] }
            },
            interaction:{ intersect:false, mode:'nearest' },
            elements:{ point:{ radius:0 } }
          }
        };

        const chart=new Chart(canv.getContext('2d'), cfg);
        chartInstances[dev.cihaz_id]={ chart, wrapper:wrap };
      });
      console.log('Grafik kurulum tamam. Cihaz:', Object.keys(chartInstances).length);
    }
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof Chart === 'undefined') { console.error('Chart.js yüklenemedi'); return; }
      bootPlugins();
      initDeviceFilter();
      refreshDeviceChips();
      const chk=document.getElementById('opsToggle');
      if(chk){ OPS_ENABLED = chk.checked; chk.addEventListener('change', ()=>{ OPS_ENABLED = chk.checked; buildCharts(); }); }
      buildCharts();
    });
  </script>
  <script src="assets/app.js"></script>
</body>
</html>
