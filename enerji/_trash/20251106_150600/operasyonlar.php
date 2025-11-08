<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/../auth.php';

function bad($msg, $code=400){ http_response_code($code); echo json_encode(['error'=>$msg]); exit; }

$start = $_GET['start'] ?? null; // ISO veya Y-m-d H:i:s
$end   = $_GET['end'] ?? null;

if (!$start || !$end) bad('start ve end zorunlu. Örn: ?start=2025-09-29%2000:00:00&end=2025-09-29%2023:59:59');

$startDt = strtotime($start); $endDt = strtotime($end);
if (!$startDt || !$endDt) bad('Tarih formatı geçersiz.');
if ($endDt < $startDt) bad('end, starttan küçük olamaz.');

// DB bağlantısı
$db = auth_db();

$stmt = $db->prepare("
  SELECT id, ad, baslangic, bitis, miktar, birim
  FROM enerji_operasyonlar
  WHERE baslangic <= ? AND (bitis IS NULL OR bitis >= ?)
  ORDER BY baslangic ASC
");
$endStr = date('Y-m-d H:i:s', $endDt);
$startStr = date('Y-m-d H:i:s', $startDt);
$stmt->bind_param('ss', $endStr, $startStr);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
  $rows[] = [
    'id' => (int)$r['id'],
    'ad' => $r['ad'],
    'baslangic' => $r['baslangic'],
    'bitis' => $r['bitis'],
    'miktar' => is_null($r['miktar']) ? null : (float)$r['miktar'],
    'birim' => $r['birim'],
  ];
}
echo json_encode(['items'=>$rows], JSON_UNESCAPED_UNICODE);