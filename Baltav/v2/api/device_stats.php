<?php
// api/device_stats.php
// Belirli bir cihaz için dashboard verileri
require_once __DIR__ . '/../auth.php';
$auth = new Auth();
$auth->requireLogin();
header('Content-Type: application/json; charset=utf-8');

$db = Database::getConnection();
// Destek: ya numeric `id` ya da `kimlik` parametresi ile çağrılabilir
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$kimlik = isset($_GET['kimlik']) ? trim($_GET['kimlik']) : null;
if (!$id && !$kimlik) {
  http_response_code(400);
  echo json_encode(['error'=>'missing_id_or_kimlik']);
  exit;
}

// Eğer kimlik verilmişse, mümkünse cihazlar tablosundan numeric id'yi bulmaya çalış
if (!$id && $kimlik) {
  try {
    $stmt = $db->prepare("SELECT id FROM cihazlar WHERE cihaz_kimligi = :k LIMIT 1");
    $stmt->execute([':k'=>$kimlik]);
    $row = $stmt->fetch();
    if ($row && isset($row['id'])) {
      $id = (int)$row['id'];
    }
  } catch (Exception $e) {
    // ignore - ileride paket sorgularında alternatif olarak kimlik kullanılacak
  }
}

try {
    // Cihaz bilgisi: öncelikle numeric id varsa cihazlar tablosundan al
    $dev = null;
    if ($id) {
      $stmt = $db->prepare("SELECT id, cihaz_adi, doluluk_orani, son_iletisim FROM cihazlar WHERE id = :id LIMIT 1");
      $stmt->execute([':id'=>$id]);
      $dev = $stmt->fetch();
    }
    // Eğer hala cihaz bilgisi yok ama kimlik varsa, cihaz_son_durum'dan al
    if (!$dev && $kimlik) {
      try {
        $stmt = $db->prepare("SELECT cihaz_kimligi AS kimlik, cihaz_adi AS cihaz_adi, doluluk_orani, son_iletisim FROM cihaz_son_durum WHERE cihaz_kimligi = :k LIMIT 1");
        $stmt->execute([':k'=>$kimlik]);
        $dev = $stmt->fetch();
      } catch (Exception $e) { }
    }

    // Saatlik tüketim (son 24 saat) cihaz bazlı
    $labels = [];
    $series = [];
    try{
      // Paketleri sorgulama: önce numeric cihaz_id varsa ona göre, yoksa cihaz_kimligi sütunu ile dene
      if ($id) {
        $q = $db->prepare("SELECT DATE_FORMAT(kayit_zamani, '%Y-%m-%d %H:00') as saat, SUM(agirlik) as toplam FROM cihaz_paketleri WHERE cihaz_id = :id AND kayit_zamani > (NOW() - INTERVAL 24 HOUR) GROUP BY saat ORDER BY saat ASC");
        $q->execute([':id'=>$id]);
      } else {
        $q = $db->prepare("SELECT DATE_FORMAT(kayit_zamani, '%Y-%m-%d %H:00') as saat, SUM(agirlik) as toplam FROM cihaz_paketleri WHERE cihaz_kimligi = :k AND kayit_zamani > (NOW() - INTERVAL 24 HOUR) GROUP BY saat ORDER BY saat ASC");
        $q->execute([':k'=>$kimlik]);
      }
      $hourRows = $q->fetchAll();
      foreach($hourRows as $r){ $labels[] = $r['saat']; $series[] = (float)$r['toplam']; }
    } catch(Exception $e){ }

    // Darbe (7 gün)
    $dLabels = [];$dSeries = [];
    try{
      if ($id) {
        $q2 = $db->prepare("SELECT DATE(kayit_zamani) as gun, SUM(darbe) as toplam_darbe FROM cihaz_paketleri WHERE cihaz_id = :id AND kayit_zamani > (NOW() - INTERVAL 7 DAY) GROUP BY gun ORDER BY gun ASC");
        $q2->execute([':id'=>$id]);
      } else {
        $q2 = $db->prepare("SELECT DATE(kayit_zamani) as gun, SUM(darbe) as toplam_darbe FROM cihaz_paketleri WHERE cihaz_kimligi = :k AND kayit_zamani > (NOW() - INTERVAL 7 DAY) GROUP BY gun ORDER BY gun ASC");
        $q2->execute([':k'=>$kimlik]);
      }
      $rows2 = $q2->fetchAll();
      foreach($rows2 as $r){ $dLabels[]=$r['gun']; $dSeries[]=(int)$r['toplam_darbe']; }
    } catch(Exception $e){ }

    // Dolum olayları (son 30)
    $fills = [];
    try{
      if ($id) {
        $q3 = $db->prepare("SELECT paket_no, agirlik, stabilite, kayit_zamani FROM cihaz_paketleri WHERE cihaz_id = :id AND kayit_zamani > (NOW() - INTERVAL 30 DAY) ORDER BY kayit_zamani DESC LIMIT 10");
        $q3->execute([':id'=>$id]);
      } else {
        $q3 = $db->prepare("SELECT paket_no, agirlik, stabilite, kayit_zamani FROM cihaz_paketleri WHERE cihaz_kimligi = :k AND kayit_zamani > (NOW() - INTERVAL 30 DAY) ORDER BY kayit_zamani DESC LIMIT 10");
        $q3->execute([':k'=>$kimlik]);
      }
      $rows3 = $q3->fetchAll();
      foreach($rows3 as $r){
        $fills[] = ['paket'=>$r['paket_no'],'agirlik'=>$r['agirlik'],'stabilite'=>$r['stabilite'],'tarih'=>$r['kayit_zamani'],'aciklama'=>'Örnek dolum/ikmal olayı'];
      }
    } catch(Exception $e){ }

    $out = [
      'device' => $dev,
      'hourly'=>['labels'=>$labels,'series'=>$series],
      'darbe'=>['labels'=>$dLabels,'series'=>$dSeries],
      'fill_events'=>$fills,
    ];

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error'=>'internal','message'=>$e->getMessage()]);
}

?>