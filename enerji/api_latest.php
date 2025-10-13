<?php
declare(strict_types=1);
// Son değer API (index canlı trend için)
// Çıktı: [ { cihaz_id, cihaz_adres_id, deger, prev_deger, diff, diff_pct, kayit_zamani, age_sec } ]
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
error_reporting(E_ALL); ini_set('display_errors','0');

function load_config(string $path): ?array {
    if(!is_file($path)) return null;
    $xml = @simplexml_load_file($path); if(!$xml || !isset($xml->connectionStrings)) return null;
    foreach($xml->connectionStrings->add as $add){
        if((string)$add['name']==='MySqlConnection'){
            $connStr=(string)$add['connectionString']; $out=[]; foreach(explode(';',$connStr) as $p){ $kv=explode('=',$p,2); if(count($kv)==2) $out[trim($kv[0])] = trim($kv[1]); }
            return $out;
        }
    }
    return null;
}

$CONFIG_PATH_CANDIDATES=[
    'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config'
];
$config=null; foreach($CONFIG_PATH_CANDIDATES as $p){ if($c=load_config($p)){ $config=$c; break; } }
if(!$config){ http_response_code(500); echo json_encode(['error'=>'config bulunamadı']); exit; }
foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($config[$k])){ http_response_code(500); echo json_encode(['error'=>'eksik config anahtarı: '.$k]); exit; } }

$db=@new mysqli($config['Server'],$config['Uid'],$config['Pwd'],$config['Database']);
if($db->connect_error){ http_response_code(500); echo json_encode(['error'=>'db bağlanamadı']); exit; }
$db->set_charset('utf8');

// Son kayıt ve bir önceki kayıt (aynı cihaz/adres) + yaş (saniye)
$sql = "SELECT o.cihaz_id,o.cihaz_adres_id,o.deger,o.kayit_zamani,
   (SELECT o2.deger FROM olcumler o2 WHERE o2.cihaz_id=o.cihaz_id AND o2.cihaz_adres_id=o.cihaz_adres_id AND o2.kayit_zamani<o.kayit_zamani ORDER BY o2.kayit_zamani DESC LIMIT 1) AS prev_deger,
   TIMESTAMPDIFF(SECOND,o.kayit_zamani,NOW()) AS age_sec
FROM olcumler o
JOIN (SELECT cihaz_id,cihaz_adres_id,MAX(kayit_zamani) mx FROM olcumler GROUP BY cihaz_id,cihaz_adres_id) t
  ON t.cihaz_id=o.cihaz_id AND t.cihaz_adres_id=o.cihaz_adres_id AND t.mx=o.kayit_zamani";

$out=[];
if($res=$db->query($sql)){
    while($r=$res->fetch_assoc()){
        $val = (float)$r['deger'];
        $prev = isset($r['prev_deger']) ? (float)$r['prev_deger'] : null;
        $diff = ($prev===null)? null : ($val - $prev);
        $diffPct = ($prev===null || abs($prev)<1e-9)? null : ($diff / $prev * 100.0);
        $out[]=[
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
} else {
    http_response_code(500); echo json_encode(['error'=>'sorgu hatası']); $db->close(); exit; }
$db->close();
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);