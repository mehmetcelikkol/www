<?php
// Basit API: Dashboard için özet/istatistik verileri döner
require_once __DIR__ . '/../auth.php';
$auth = new Auth();
$auth->requireLogin();
header('Content-Type: application/json; charset=utf-8');

$db = Database::getConnection();

try {
    // Özet sayılar
    $total = (int)$db->query("SELECT COUNT(*) FROM cihazlar")->fetchColumn();
    // Kritik: doluluk_orani <=20
    $critical = 0;
    try {
      $critical = (int)$db->query("SELECT COUNT(*) FROM cihazlar WHERE doluluk_orani IS NOT NULL AND doluluk_orani <= 20")->fetchColumn();
    } catch (Exception $e) { $critical = 0; }

    // Haberleşme hatası: son iletişim 24 saatten eski (varsayımsal kolon: son_iletisim)
    $commErrors = 0;
    try{
      $commErrors = (int)$db->query("SELECT COUNT(*) FROM cihazlar WHERE son_iletisim IS NOT NULL AND son_iletisim < (NOW() - INTERVAL 24 HOUR)")->fetchColumn();
    } catch(Exception $e){ $commErrors = 0; }

    // Saatlik tüketim (son 24 saatin saat bazında toplaması) — cihaz_paketleri.agirlik farkları kullanılarak örnek hazırlanır
    $labels = [];
    $series = [];
    $hourRows = [];
    try{
      $q = $db->query("SELECT DATE_FORMAT(kayit_zamani, '%Y-%m-%d %H:00') as saat, SUM(agirlik) as toplam FROM cihaz_paketleri WHERE kayit_zamani > (NOW() - INTERVAL 24 HOUR) GROUP BY saat ORDER BY saat ASC");
      $hourRows = $q->fetchAll();
    } catch(Exception $e){ $hourRows = []; }
    foreach($hourRows as $r){ $labels[] = $r['saat']; $series[] = (float)$r['toplam']; }

    // Darbe ısı haritası: gün içindeki darbe sayıları (örnek: son 7 gün, gün bazlı)
    $dLabels = [];$dSeries = [];
    try{
      $q2 = $db->query("SELECT DATE(kayit_zamani) as gun, SUM(darbe) as toplam_darbe FROM cihaz_paketleri WHERE kayit_zamani > (NOW() - INTERVAL 7 DAY) GROUP BY gun ORDER BY gun ASC");
      $rows2 = $q2->fetchAll();
      foreach($rows2 as $r){ $dLabels[]=$r['gun']; $dSeries[]=(int)$r['toplam_darbe']; }
    } catch(Exception $e){ }

    // Dolum (fill) olayları: örnek olarak stabilite yüksek + ağırlık artışı ile dolum tespiti
    $fills = [];
    try{
      $q3 = $db->query("SELECT paket_no, agirlik, stabilite, kayit_zamani FROM cihaz_paketleri WHERE kayit_zamani > (NOW() - INTERVAL 30 DAY) AND agirlik IS NOT NULL ORDER BY kayit_zamani DESC LIMIT 10");
      $rows3 = $q3->fetchAll();
      foreach($rows3 as $r){
        $fills[] = ['paket'=>$r['paket_no'],'agirlik'=>$r['agirlik'],'stabilite'=>$r['stabilite'],'tarih'=>$r['kayit_zamani'],'aciklama'=>'Örnek dolum/ikmal olayı'];
      }
    } catch(Exception $e){ }

    $out = [
      'summary'=>['total_silos'=>$total,'critical'=>$critical,'comm_errors'=>$commErrors],
      'hourly'=>['labels'=>$labels,'series'=>$series],
      'darbe'=>['labels'=>$dLabels,'series'=>$dSeries],
      'fill_events'=>$fills,
    ];

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error'=>'internal','message'=>$e->getMessage()]);
}

?>