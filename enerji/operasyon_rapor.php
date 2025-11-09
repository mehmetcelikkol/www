<?php
declare(strict_types=1);
require __DIR__.'/auth.php';
auth_require_login();
header('Content-Type: application/json; charset=utf-8');

$db = auth_db();
$id = (int)($_POST['operasyon_id'] ?? $_GET['operasyon_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Geçersiz ID']); exit;
}

$stmt = $db->prepare("SELECT id, ad, baslangic, bitis, miktar, birim FROM enerji_operasyonlar WHERE id=?");
if(!$stmt){ echo json_encode(['success'=>false,'error'=>'Sorgu hazırlanamadı']); exit; }
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$op = $res->fetch_assoc();
$stmt->close();

if(!$op){ echo json_encode(['success'=>false,'error'=>'Operasyon bulunamadı']); exit; }

$startTs = strtotime($op['baslangic']);
$endTs   = $op['bitis'] ? strtotime($op['bitis']) : time();
$sure_dk = max(0, ($endTs - $startTs)/60);
$sure_saat = $sure_dk/60;

// Konumlar
$konumlar = [];
$kq = $db->query("SELECT konum FROM enerji_operasyon_konum WHERE operasyon_id=".$id);
if($kq){
  while($r=$kq->fetch_assoc()){ $konumlar[] = $r['konum']; }
  $kq->close();
}
$konumlar = array_values(array_unique(array_filter(array_map('trim',$konumlar))));

// ÖLÇÜM VERİLERİ (TODO: tablo ve alan adlarını uyarlayın)
$toplam_kwh = 0.0;
$ort_guc = 0.0;
$max_guc = 0.0;
$veri_sayisi = 0;
$analizor_sayisi = 0;

// Örnek varsayım: enerji_veri (analizor_id, ts, aktif_guc_kw)
// TODO: Tablo adı / alanlar / filtreler
$measStmt = $db->prepare("
  SELECT analizor_id,
         COUNT(*) AS c,
         AVG(aktif_guc_kw) AS avg_kw,
         MAX(aktif_guc_kw) AS max_kw,
         SUM(aktif_guc_kw)/12 AS kwh_est  -- Örnek: 5 dakikalık kayıt olduğunda bölümü değiştir
  FROM enerji_veri
  WHERE ts BETWEEN ? AND ?
  GROUP BY analizor_id
");
$startIso = date('Y-m-d H:i:s', $startTs);
$endIso   = date('Y-m-d H:i:s', $endTs);
if($measStmt){
  $measStmt->bind_param('ss',$startIso,$endIso);
  if($measStmt->execute()){
    $r2 = $measStmt->get_result();
    while($row=$r2->fetch_assoc()){
      $veri_sayisi += (int)$row['c'];
      $toplam_kwh  += (float)$row['kwh_est'];
      $ort_guc     += (float)$row['avg_kw']; // sonra analizör sayısına böleceğiz
      $max_guc      = max($max_guc, (float)$row['max_kw']);
      $analizor_sayisi++;
    }
    if($analizor_sayisi>0){
      $ort_guc = $ort_guc / $analizor_sayisi;
    }
  }
  $measStmt->close();
}

// Verimlilik
$verimlilik = null;
if (!empty($op['miktar']) && (float)$op['miktar'] > 0) {
  $verimlilik = $toplam_kwh / (float)$op['miktar'];
}

echo json_encode([
  'success'=>true,
  'operasyon'=>$op,
  'konumlar'=>$konumlar,
  'metrikler'=>[
    'sure_dk'=> round($sure_dk,2),
    'sure_saat'=> round($sure_saat,2),
    'toplam_kwh'=> round($toplam_kwh,2),
    'ort_guc'=> round($ort_guc,2),
    'max_guc'=> round($max_guc,2),
    'veri_sayisi'=> $veri_sayisi,
    'analizor_sayisi'=> $analizor_sayisi,
    'verimlilik'=> $verimlilik !== null ? round($verimlilik,4) : null
  ]
], JSON_UNESCAPED_UNICODE);