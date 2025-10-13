<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
require_once __DIR__.'/mail_util.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function db(): mysqli { return auth_db(); }

// Basit log
function alarm_api_log(string $msg): void {
    $dir = __DIR__.'/logs'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    @file_put_contents($dir.'/api_alarm.log', date('Y-m-d H:i:s').' '.$msg."\n", FILE_APPEND);
}

// Basit JSON gövde oku
function read_json(): array {
    $raw = file_get_contents('php://input');
    if(!$raw) return [];
    $d = json_decode($raw, true);
    return is_array($d)? $d : [];
}

// Güvenli string
function s($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$user = auth_user();
if(!$user){ http_response_code(401); echo json_encode(['error'=>'auth']); exit; }

// Şema beklentisi (öneri - Türkçe):
// TABLE alarmlar (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   cihaz_id INT NOT NULL,
//   cihaz_adres_id INT NOT NULL,
//   kural_turu ENUM('esik','oran') NOT NULL,
//   esik_min DOUBLE NULL,
//   esik_max DOUBLE NULL,
//   oran_pct DOUBLE NULL,
//   yon ENUM('herikisi','yukari','asagi') NOT NULL DEFAULT 'herikisi',
//   herkes_gorsun TINYINT(1) NOT NULL DEFAULT 1,
//   olusturan_id INT NOT NULL,
//   olusturma_zamani DATETIME NOT NULL,
//   aktif TINYINT(1) NOT NULL DEFAULT 1,
//   aciklama VARCHAR(255) NULL
// )
// TABLE alarm_epostalar (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   alarm_id INT NOT NULL,
//   eposta VARCHAR(255) NOT NULL
// )
// TABLE eposta_rehberi (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   eposta VARCHAR(255) UNIQUE NOT NULL,
//   etiket VARCHAR(255) NULL,
//   olusturan_id INT NULL,
//   olusturma_zamani DATETIME NOT NULL
// )

try {
    if($act==='save'){
        $data = read_json();
        $cid = (int)($data['cihaz_id'] ?? 0);
        $aid = (int)($data['cihaz_adres_id'] ?? 0);
        $rule = (string)($data['rule_type'] ?? 'esik');
        $tmin = isset($data['threshold_min']) && $data['threshold_min']!=='' ? (float)$data['threshold_min'] : null;
        $tmax = isset($data['threshold_max']) && $data['threshold_max']!=='' ? (float)$data['threshold_max'] : null;
        $rate = isset($data['rate_pct']) && $data['rate_pct']!=='' ? (float)$data['rate_pct'] : null;
        $dir  = (string)($data['direction'] ?? 'herikisi');
        $enabled = (int)($data['enabled'] ?? 1);
        $notify_all = (int)($data['notify_all'] ?? 1);
        $note = (string)($data['note'] ?? '');
        $emails = is_array($data['emails'] ?? null) ? $data['emails'] : [];
    if($cid<=0 || $aid<=0) throw new Exception('cihaz/adres');
        if(!in_array($rule, ['esik','oran'], true)) throw new Exception('kural');
        if($rule==='esik' && $tmin===null && $tmax===null) throw new Exception('esik');
        if($rule==='oran' && $rate===null) throw new Exception('oran');
        if(!in_array($dir,['herikisi','yukari','asagi'],true)) $dir='herikisi';
        $db = db();
        $now = date('Y-m-d H:i:s');
        alarm_api_log('SAVE cid='.$cid.' aid='.$aid.' rule='.$rule.' emails='.json_encode($emails,JSON_UNESCAPED_UNICODE));
    $st = $db->prepare('INSERT INTO alarmlar(cihaz_id,cihaz_adres_id,kural_turu,esik_min,esik_max,oran_pct,yon,herkes_gorsun,olusturan_id,olusturma_zamani,aktif,aciklama) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        if(!$st) throw new Exception('prepare');
    // Tipler: i i s d d d s i i s i s
    $st->bind_param('iisdddsiisis', $cid,$aid,$rule,$tmin,$tmax,$rate,$dir,$notify_all,$user['id'],$now,$enabled,$note);
        if(!$st->execute()) throw new Exception('insert');
        $alarmId = $st->insert_id; $st->close();
    $emailsSaved = 0;
    if($emails){
            $st = $db->prepare('INSERT INTO alarm_epostalar(alarm_id,eposta) VALUES(?,?)');
            if(!$st){ alarm_api_log('ERR prepare alarm_epostalar: '.$db->error); }
            foreach($emails as $em){
                $em=trim((string)$em); if($em==='') continue;
        if($st){ $st->bind_param('is',$alarmId,$em); if(!$st->execute()){ alarm_api_log('ERR insert alarm_epostalar: '.$db->error.' em='.$em); } else { $emailsSaved++; } }
            }
            if($st) $st->close();
            // E-posta rehberi
            $insB = $db->prepare('INSERT IGNORE INTO eposta_rehberi(eposta,etiket,olusturan_id,olusturma_zamani) VALUES(?,?,?,?)');
            if(!$insB){ alarm_api_log('ERR prepare eposta_rehberi: '.$db->error); }
            foreach($emails as $em){ $label=$em; if($insB){ $insB->bind_param('ssis',$em,$label,$user['id'],$now); if(!$insB->execute()){ alarm_api_log('ERR insert eposta_rehberi: '.$db->error.' em='.$em); } } }
            if($insB) $insB->close();
        }
    echo json_encode(['ok'=>true,'id'=>$alarmId,'emails_saved'=>$emailsSaved]);
        exit;
    }
    if($act==='list'){
        $db=db();
        // Görünürlük filtresi: herkes_gorsun=1 olanlar ya da olusturan_id = kullanıcı
        $uid=(int)$user['id'];
        $sql = 'SELECT 
                    a.id,
                    a.cihaz_id,
                    a.cihaz_adres_id,
                    a.kural_turu   AS rule_type,
                    a.esik_min     AS threshold_min,
                    a.esik_max     AS threshold_max,
                    a.oran_pct     AS rate_pct,
                    a.yon          AS direction,
                    a.herkes_gorsun AS notify_all,
                    a.olusturan_id AS created_by,
                    a.olusturma_zamani AS created_at,
                    a.aktif        AS enabled,
                    a.aciklama     AS note,
                    GROUP_CONCAT(e.eposta) AS emails
                FROM alarmlar a 
                LEFT JOIN alarm_epostalar e ON e.alarm_id=a.id
                WHERE a.herkes_gorsun=1 OR a.olusturan_id=?
                GROUP BY a.id
                ORDER BY a.olusturma_zamani DESC';
        $st=$db->prepare($sql); $st->bind_param('i',$uid); $st->execute(); $res=$st->get_result();
        $out=[]; while($r=$res->fetch_assoc()){ $r['emails']=$r['emails']? explode(',', $r['emails']):[]; $out[]=$r; }
        echo json_encode(['ok'=>true,'items'=>$out]); exit;
    }
    if($act==='delete'){
        $data=read_json(); $id=(int)($data['id']??0); if($id<=0) throw new Exception('id');
        $db=db(); $uid=(int)$user['id'];
        // sadece sahibi silebilir
        $st=$db->prepare('DELETE FROM alarm_epostalar WHERE alarm_id=?'); $st->bind_param('i',$id); $st->execute(); $st->close();
        $st=$db->prepare('DELETE FROM alarmlar WHERE id=? AND olusturan_id=?'); $st->bind_param('ii',$id,$uid); $st->execute(); $aff=$st->affected_rows; $st->close();
        echo json_encode(['ok'=>$aff>0]); exit;
    }
    if($act==='emails'){ // akıllı arama için öneriler
        $q = (string)($_GET['q'] ?? ''); $qLike = '%'.$q.'%'; $db=db();
        if($q===''){
            $res=$db->query('SELECT eposta AS email, etiket AS label FROM eposta_rehberi ORDER BY id DESC LIMIT 20');
        } else {
            $st=$db->prepare('SELECT eposta AS email, etiket AS label FROM eposta_rehberi WHERE eposta LIKE ? OR etiket LIKE ? ORDER BY id DESC LIMIT 20');
            $st->bind_param('ss',$qLike,$qLike); $st->execute(); $res=$st->get_result();
        }
        $out=[]; while($r=$res->fetch_assoc()) $out[]=$r; echo json_encode(['ok'=>true,'items'=>$out]); exit;
    }

    http_response_code(400); echo json_encode(['error'=>'unknown act']);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }

?>
