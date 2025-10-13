<?php
// alarm_worker.php — Kuralları değerlendirir ve e-posta gönderir.
// Çalıştırma: PHP CLI ile periyodik (Windows Görev Zamanlayıcı). Örn: php -d detect_unicode=0 c:\wamp64\www\enerji\alarm_worker.php
declare(strict_types=1);
require __DIR__.'/auth.php';
require_once __DIR__.'/mail_util.php';

// Güvenlik: sadece CLI'dan çalıştırılsın (web'den çağrılmasın)
if(php_sapi_name() !== 'cli'){
    http_response_code(403); echo "CLI only"; exit;
}

$db = auth_db();
$db->set_charset('utf8');

function log_msg(string $m){
    $dir = __DIR__.'/logs'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    @file_put_contents($dir.'/alarm_worker.log', date('Y-m-d H:i:s').' '.$m."\n", FILE_APPEND);
}

// Şema notu: alarm_gecmisi tablosu yoksa önceden oluşturun (README-SQL'e bakın).

// 1) Son ölçümleri çek (api_latest benzeri, ama join ile tek sorgu)
$sqlLatest = "SELECT o.cihaz_id,o.cihaz_adres_id,o.deger,o.kayit_zamani,
   (SELECT o2.deger FROM olcumler o2 WHERE o2.cihaz_id=o.cihaz_id AND o2.cihaz_adres_id=o.cihaz_adres_id AND o2.kayit_zamani<o.kayit_zamani ORDER BY o2.kayit_zamani DESC LIMIT 1) AS prev_deger,
   TIMESTAMPDIFF(SECOND,o.kayit_zamani,NOW()) AS age_sec
FROM olcumler o
JOIN (SELECT cihaz_id,cihaz_adres_id,MAX(kayit_zamani) mx FROM olcumler GROUP BY cihaz_id,cihaz_adres_id) t
  ON t.cihaz_id=o.cihaz_id AND t.cihaz_adres_id=o.cihaz_adres_id AND t.mx=o.kayit_zamani";

$latest = [];
if($res=$db->query($sqlLatest)){
    while($r=$res->fetch_assoc()){
        $val=(float)$r['deger']; $prev = isset($r['prev_deger'])? (float)$r['prev_deger']:null;
        $diff = ($prev===null)? null : ($val-$prev);
        $diffPct = ($prev===null || abs($prev)<1e-9)? null : ($diff/$prev*100.0);
        $latest[$r['cihaz_id'].'|'.$r['cihaz_adres_id']] = [
            'cihaz_id'=>(int)$r['cihaz_id'],
            'cihaz_adres_id'=>(int)$r['cihaz_adres_id'],
            'deger'=>$val,
            'prev_deger'=>$prev,
            'diff'=>$diff,
            'diff_pct'=>$diffPct,
            'kayit_zamani'=>$r['kayit_zamani'],
            'age_sec'=>(int)$r['age_sec']
        ];
    }
    $res->close();
}

// 2) Aktif kuralları çek ve değerlendir
$sqlRules = "SELECT a.id,a.cihaz_id,a.cihaz_adres_id,a.kural_turu,a.esik_min,a.esik_max,a.oran_pct,a.yon,a.herkes_gorsun,a.olusturan_id,a.aciklama,
                    GROUP_CONCAT(e.eposta) AS emails,
                    ku.ad AS uad, ku.soyad AS usoyad,
                    c.konum, c.cihaz_adi,
                    ca.ad AS kanal_adi
             FROM alarmlar a
             LEFT JOIN alarm_epostalar e ON e.alarm_id=a.id
             LEFT JOIN kullanicilar ku ON ku.id=a.olusturan_id
             LEFT JOIN cihazlar c ON c.id=a.cihaz_id
             LEFT JOIN cihaz_adresleri ca ON ca.id=a.cihaz_adres_id
             WHERE a.aktif=1
             GROUP BY a.id";

$rules=[]; if($res=$db->query($sqlRules)){ while($r=$res->fetch_assoc()){ $r['emails']=$r['emails']? explode(',', $r['emails']):[]; $rules[]=$r; } $res->close(); }

// 3) Geçmişten açık/alındı durumlarını oku
$opened=[]; // key: alarm_id|cihaz|kanal -> row
if($res=$db->query("SELECT * FROM alarm_gecmisi WHERE kapandi IS NULL")){
    while($r=$res->fetch_assoc()){ $opened[$r['alarm_id'].'|'.$r['cihaz_id'].'|'.$r['cihaz_adres_id']] = $r; }
    $res->close();
}

// Yardımcı: eşik kontrol
function rule_triggered(array $rule, ?array $row): array {
    // Döndür: [bool triggered, string reason]
    if(!$row) return [false,'Veri yok'];
    $type = $rule['kural_turu'];
    if($type==='esik'){
        $min = ($rule['esik_min']!==null)? (float)$rule['esik_min'] : null;
        $max = ($rule['esik_max']!==null)? (float)$rule['esik_max'] : null;
        $v = (float)$row['deger'];
        $okMin = ($min===null) ? true : ($v >= $min);
        $okMax = ($max===null) ? true : ($v <= $max);
        $hit = !($okMin && $okMax);
    $msg = 'Değer aralık dışı: ';
    if($min!==null) $msg .= 'min='.$min.' ';
    if($max!==null) $msg .= 'max='.$max;
    $msg .= ' v='.$v;
    return [$hit, $hit ? $msg : ''];
    } else { // oran
        $pct = ($row['diff_pct']===null)? null : (float)$row['diff_pct'];
        if($pct===null) return [false,'Yüzde hesaplanamadı'];
        $th = (float)$rule['oran_pct']; $dir = $rule['yon'];
        $hit = false; if($dir==='herikisi') $hit = abs($pct) >= $th; else if($dir==='yukari') $hit = $pct >= $th; else if($dir==='asagi') $hit = $pct <= -$th;
        return [$hit, $hit ? ('Değişim %: '.round($pct,1).' ≥ '.round($th,1).' (' . $dir . ')') : ''];
    }
}

$now = date('Y-m-d H:i:s');
$openedCount=0; $closedCount=0; $mailCount=0;

foreach($rules as $rule){
    $key = $rule['cihaz_id'].'|'.$rule['cihaz_adres_id'];
    $row = $latest[$key] ?? null;
    [$hit, $reason] = rule_triggered($rule, $row);
    $openKey = $rule['id'].'|'.$rule['cihaz_id'].'|'.$rule['cihaz_adres_id'];
    $isOpen = isset($opened[$openKey]);

    // Ayrıntılı log (teşhis için)
    $valLog = $row ? (string)$row['deger'] : '-';
    $diffPctLog = ($row && $row['diff_pct']!==null) ? (string)round((float)$row['diff_pct'],1) : 'null';
    log_msg('RULE id='.$rule['id'].' cid='.$rule['cihaz_id'].' aid='.$rule['cihaz_adres_id'].' type='.$rule['kural_turu'].' hit='.(int)$hit.' val='.$valLog.' diff_pct='.$diffPctLog.' reason='.(string)$reason);

    if($hit && !$isOpen){
        // aç
        $st=$db->prepare('INSERT INTO alarm_gecmisi(alarm_id,cihaz_id,cihaz_adres_id,acildi,neden,son_deger) VALUES(?,?,?,?,?,?)');
        $val = $row? (float)$row['deger'] : null; $nd = $reason;
        $st->bind_param('iiissd', $rule['id'],$rule['cihaz_id'],$rule['cihaz_adres_id'],$now,$nd,$val);
        $st->execute(); $st->close(); $openedCount++;
        // e-posta
        $to = $rule['emails'];
        if(!empty($to)){
            $devLabel = trim(($rule['konum']??'').' - '.($rule['cihaz_adi']??''));
            $subject = 'Alarm: '.$devLabel.' / '.($rule['kanal_adi']??'Kanal');
            $creator = trim(($rule['uad']??'').' '.($rule['usoyad']??''));
            $body = "Merhaba,\n\nAşağıdaki alarm TETİKLENDİ:\n\n".
                    "Analizör: $devLabel\n".
                    "Kanal: ".$rule['kanal_adi']."\n".
                    "Oluşturan: $creator (ID ".$rule['olusturan_id'].")\n".
                    "Zaman: $now\n".
                    "Son Değer: ".($row? $row['deger']:'-')."\n".
                    "Neden: $reason\n\n".
                    "Not: ".($rule['aciklama']??'')."\n\n".
                    "Bu e-posta otomatik gönderilmiştir.";
            send_txt_mail($to, $subject, $body); $mailCount++;
        }
    }
    if(!$hit && $isOpen){
        // kapa
        $r = $opened[$openKey];
        $st=$db->prepare('UPDATE alarm_gecmisi SET kapandi=?, kapanis_nedeni=? WHERE id=?');
        $rn = 'Koşul normale döndü';
        $st->bind_param('ssi',$now,$rn,$r['id']);
        $st->execute(); $st->close(); $closedCount++;
    }
}

log_msg("Done: open=$openedCount, close=$closedCount, mail=$mailCount");
echo "OK open=$openedCount close=$closedCount mail=$mailCount\n";

?>
