<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
auth_require_login();

error_reporting(E_ALL);
ini_set('display_errors','1');
date_default_timezone_set('Europe/Istanbul');

function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function flashes(): void {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="flashes">';
        foreach ($_SESSION['flash'] as $f) {
            $cls = $f['type'] === 'error' ? 'error' : 'ok';
            echo '<div class="flash '.$cls.'">'. htmlspecialchars($f['msg'], ENT_QUOTES, 'UTF-8') .'</div>';
        }
        echo '</div>';
        unset($_SESSION['flash']);
    }
}
function dt_from_post(?string $nowFlag, string $fieldName): ?string {
    if ($nowFlag === '1') return (new DateTime())->format('Y-m-d H:i:s');
    $raw = $_POST[$fieldName] ?? '';
    if ($raw === '') return null;
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
    if (!$dt) return null;
    return $dt->format('Y-m-d H:i:s');
}

$db = auth_db();

$konumList = [];
$konumColumn = null;

// EKLENDİ / TAŞINDI: db_column_exists önceye alındı
function db_column_exists(mysqli $db, string $table, string $column): bool {
    $schemaRes = $db->query("SELECT DATABASE() AS db");
    $schema = $schemaRes ? ($schemaRes->fetch_assoc()['db'] ?? '') : '';
    if ($schemaRes) $schemaRes->close();
    if ($schema === '') return false;
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
    if (!$stmt = $db->prepare($sql)) return false;
    $stmt->bind_param('sss', $schema, $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok;
}

// EKLENDİ: tablo var mı kontrolü
function db_table_exists(mysqli $db, string $table): bool {
    $schemaRes = $db->query("SELECT DATABASE() AS db");
    $schema = $schemaRes ? ($schemaRes->fetch_assoc()['db'] ?? '') : '';
    if ($schemaRes) $schemaRes->close();
    if ($schema === '') return false;
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1";
    if (!$stmt = $db->prepare($sql)) return false;
    $stmt->bind_param('ss', $schema, $table);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok;
}

$konumList = [];
$konumColumn = null;
if ($cRes = $db->query("SHOW COLUMNS FROM cihazlar")) {
    $cols = [];
    while($c = $cRes->fetch_assoc()){ $cols[] = strtolower($c['Field']); }
    $cRes->close();
    foreach (['konum','lokasyon','location','yer','istasyon'] as $cand){
        if (in_array($cand, $cols, true)) { $konumColumn = $cand; break; }
    }
    if ($konumColumn){
        $sql = "SELECT DISTINCT $konumColumn AS konum FROM cihazlar WHERE $konumColumn IS NOT NULL AND $konumColumn<>'' ORDER BY $konumColumn ASC";
        if ($r = $db->query($sql)){
            while($row = $r->fetch_assoc()){ $konumList[] = $row['konum']; }
            $r->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        $ad = trim($_POST['ad'] ?? '');
        $baslangic = dt_from_post($_POST['start_now'] ?? '0', 'baslangic');

        // Konum(lar) - sadece ilişki tablosu kullanılacak
        $konumArr = array_values(array_filter(array_map('trim', (array)($_POST['konum'] ?? [])), fn($s)=>$s!==''));

        if ($ad === '') {
            flash('error', 'Operasyon adı gereklidir.');
        } elseif (!$baslangic) {
            flash('error', 'Başlangıç tarih-saat geçersiz.');
        } else {
            // JSON kolonu YOK: sadece ad, baslangic ekle
            $stmt = $db->prepare("INSERT INTO enerji_operasyonlar (ad, baslangic) VALUES (?, ?)");
            if ($stmt) $stmt->bind_param('ss', $ad, $baslangic);

            if (!$stmt) {
                flash('error', 'Sorgu hazırlanamadı.');
            } else {
                if ($stmt->execute()) {
                    $opId = (int)$stmt->insert_id;

                    // Seçilen konumları ilişkilendirme tablosuna yaz
                    if (!empty($konumArr)) {
                        if ($ins = $db->prepare("INSERT INTO enerji_operasyon_konum (operasyon_id, konum) VALUES (?, ?)")) {
                            foreach ($konumArr as $k) {
                                $ins->bind_param('is', $opId, $k);
                                $ins->execute();
                            }
                            $ins->close();
                        }
                    }

                    flash('ok', 'Operasyon başlatıldı. ID: ' . $opId . (!empty($konumArr) ? ' • Konum(lar) kaydedildi' : ''));
                } else {
                    flash('error', 'Kayıt sırasında hata: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    } elseif ($action === 'end') {
        $id = intval($_POST['id'] ?? 0);
        $bitis = dt_from_post($_POST['end_now'] ?? '0', 'bitis');
        $miktarRaw = $_POST['miktar'] ?? '';
        $miktar = ($miktarRaw === '' ? null : (float)$miktarRaw);
        $birim = trim($_POST['birim'] ?? '');

        if ($id <= 0) {
            flash('error', 'Geçersiz ID.');
        } elseif (!$bitis) {
            flash('error', 'Bitiş tarih-saat geçersiz.');
        } else {
            $stmt = $db->prepare("SELECT baslangic FROM enerji_operasyonlar WHERE id=? AND bitis IS NULL");
            if (!$stmt) {
                flash('error', 'Sorgu hazırlanamadı.');
            } else {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();

                if (!$row) {
                    flash('error', 'Açık operasyon bulunamadı veya zaten bitmiş.');
                } elseif (strtotime($bitis) < strtotime($row['baslangic'])) {
                    flash('error', 'Bitiş, başlangıçtan önce olamaz.');
                } else {
                    if ($miktar === null) {
                        $stmt2 = $db->prepare("UPDATE enerji_operasyonlar SET bitis=?, miktar=NULL, birim=? WHERE id=? AND bitis IS NULL");
                        if ($stmt2) { $stmt2->bind_param('ssi', $bitis, $birim, $id); }
                    } else {
                        $stmt2 = $db->prepare("UPDATE enerji_operasyonlar SET bitis=?, miktar=?, birim=? WHERE id=? AND bitis IS NULL");
                        if ($stmt2) { $stmt2->bind_param('sdsi', $bitis, $miktar, $birim, $id); }
                    }
                    if (!$stmt2) {
                        flash('error', 'Güncelleme sorgusu hazırlanamadı.');
                    } else {
                        if ($stmt2->execute()) {
                            if ($stmt2->affected_rows > 0) {
                                flash('ok', 'Operasyon sonlandırıldı.');
                            } else {
                                flash('error', 'Güncellenecek kayıt bulunamadı.');
                            }
                        } else {
                            flash('error', 'Güncelleme hatası: ' . $stmt2->error);
                        }
                        $stmt2->close();
                    }
                }
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
}

// Listelemeler
$acik = $tamamlanan = [];
if ($q = $db->query("SELECT * FROM enerji_operasyonlar WHERE bitis IS NULL ORDER BY baslangic DESC")) {
    $acik = $q->fetch_all(MYSQLI_ASSOC);
    $q->close();
}
if ($q = $db->query("SELECT * FROM enerji_operasyonlar WHERE bitis IS NOT NULL ORDER BY bitis DESC LIMIT 20")) {
    $tamamlanan = $q->fetch_all(MYSQLI_ASSOC);
    $q->close();
}

// EKLENDİ: Konumları haritala (sadece ilişki tablosu)
$acikKonum = []; $tamKonum = [];
$idsAll = [];
foreach ($acik as $r) { $idsAll[] = (int)$r['id']; }
foreach ($tamamlanan as $r) { $idsAll[] = (int)$r['id']; }
$idsAll = array_values(array_unique(array_filter($idsAll)));

$linkMap = []; // operasyon_id => [konum...]
if ($idsAll) {
    // İlişki tablosundan oku (JSON fallback kaldırıldı)
    $idList = implode(',', array_map('intval', $idsAll));
    if ($res = $db->query("SELECT operasyon_id, konum FROM enerji_operasyon_konum WHERE operasyon_id IN ($idList) ORDER BY konum")) {
        while ($row = $res->fetch_assoc()) {
            $oid = (int)$row['operasyon_id'];
            $k = trim((string)$row['konum']);
            if ($k !== '') { $linkMap[$oid][] = $k; }
        }
        $res->close();
    }
}

$buildKonumFor = function(array $rows) use ($linkMap): array {
    $out = [];
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $merged = $linkMap[$id] ?? [];
        if ($merged) {
            $merged = array_values(array_unique($merged));
            sort($merged, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $out[$id] = $merged;
    }
    return $out;
};
$acikKonum = $buildKonumFor($acik);
$tamKonum  = $buildKonumFor($tamamlanan);

// --- ölçüm verisi özetleme fonksiyonu eklendi ---
define('AKTIF_GUC_KANAL_IDS', [1]); // TODO: gerçek aktif güç kanal_id listesi
// AKTIF_GUC_KANAL_IDS boş veya yanlışsa sonuç hep 0 olur.

function op_measure_summary(mysqli $db, string $start, string $end): array {
    $kanallar = AKTIF_GUC_KANAL_IDS;
    if (!$kanallar) {
        return ['ok'=>false,'kwh'=>0,'avg'=>0,'max'=>0,'ts_count'=>0,'row_count'=>0,'sure_saat'=>0];
    }
    // Dinamik IN yer tutucuları
    $in = implode(',', array_fill(0, count($kanallar), '?'));
    $sql = "SELECT kayit_zamani, SUM(deger) AS toplam_kw
            FROM olcumler
            WHERE kayit_zamani BETWEEN ? AND ?
              AND kanal_id IN ($in)
            GROUP BY kayit_zamani
            ORDER BY kayit_zamani ASC";
    $types = 'ss' . str_repeat('i', count($kanallar));
    if(!$stmt = $db->prepare($sql)){
        return ['ok'=>false,'kwh'=>0,'avg'=>0,'max'=>0,'ts_count'=>0,'row_count'=>0,'sure_saat'=>0];
    }
    $params = [$start,$end];
    foreach($kanallar as $kid){ $params[] = $kid; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while($r = $res->fetch_assoc()){
        $ts = strtotime($r['kayit_zamani']);
        if($ts === false) continue;
        $rows[] = ['ts'=>$ts,'p'=>(float)$r['toplam_kw']];
    }
    $stmt->close();
    $n = count($rows);
    if($n < 1){
        return ['ok'=>true,'kwh'=>0,'avg'=>0,'max'=>0,'ts_count'=>0,'row_count'=>0,'sure_saat'=>0];
    }
    $ilk = $rows[0]['ts'];
    $son = $rows[$n-1]['ts'];
    $sure_saat = max(0, ($son - $ilk)/3600);

    // Trapezoid integrasyonu
    $energy = 0.0;
    for($i=1;$i<$n;$i++){
        $dtSaat = ($rows[$i]['ts'] - $rows[$i-1]['ts'])/3600;
        if($dtSaat <= 0) continue;
        $pA = $rows[$i-1]['p'];
        $pB = $rows[$i]['p'];
        $energy += ($pA + $pB)/2 * $dtSaat;
    }
    $maxP = 0.0;
    $sumP = 0.0;
    foreach($rows as $r){
        $sumP += $r['p'];
        if($r['p'] > $maxP) $maxP = $r['p'];
    }
    $avgP = $sure_saat > 0 ? ($energy / $sure_saat) : 0;

    return [
        'ok'=>true,
        'kwh'=> $energy,
        'avg'=> $avgP,
        'max'=> $maxP,
        'ts_count'=> $n,
        'row_count'=> $n, // (gruplandı, satır = zaman)
        'sure_saat'=> $sure_saat
    ];
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Operasyon Girişi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/app.css">
  <style>
    /* EKLENDİ: tags input stilleri */
    .tags-input{
      display:flex; flex-wrap:wrap; gap:6px; min-height:38px; padding:6px 8px;
      border:1px solid #e5e7eb; border-radius:6px; background:#fff;
    }
    .tags-input input{
      border:none; outline:none; min-width:160px; flex:1; font:inherit;
    }
    .tags-input .tag{
      display:inline-flex; align-items:center; gap:6px;
      background:#e2e8f0; color:#0f172a; padding:3px 6px; border-radius:12px; cursor:pointer;
      user-select:none; font-size:12px;
    }
    .tags-input .tag:hover{ background:#cbd5e1; }
    .tags-input .tag .x{ font-weight:700; }

    /* EKLENDİ: Konum seçimi stilleri (güncellendi) */
    .konum-box{display:flex; flex-direction:column; gap:8px}
    .konum-scroll{
      border:1px solid rgba(226,232,240,0.35);
      border-radius:8px; padding:8px;
      background:transparent;
      display:grid; gap:8px;
      grid-template-columns: repeat(2, minmax(140px, 1fr));
    }
    @media (max-width: 640px){
      .konum-scroll{ grid-template-columns: 1fr; }
    }
    .konum-item{
      display:flex; align-items:center; gap:8px; font-size:12px; cursor:pointer; user-select:none;
      padding:6px 8px; border:1px solid rgba(226,232,240,0.25);
      border-radius:8px; background:rgba(255,255,255,0.05);
      transition: background-color .15s ease, border-color .15s ease;
    }
    .konum-item:hover{ border-color:rgba(226,232,240,0.55); background:rgba(255,255,255,0.12); }
     .konum-item input{margin:0; accent-color:#0ea5e9}
    .konum-item span{white-space:nowrap; color:#f1f5f9;}
    .konum-item input:checked + span{ font-weight:600; text-decoration:underline; }

    /* Açık operasyonlar: kompakt form düzeni */
    .op-form{
      display:grid;
      grid-template-columns: 220px 140px 120px auto;
      align-items:end;
      column-gap:12px;
      row-gap:8px;
    }
    .op-form .field{ display:flex; flex-direction:column; gap:4px; min-width:0; }
    .op-form .field label.muted{ font-size:11px; color:var(--text-soft); }
    .op-form .inline{ display:flex; align-items:center; gap:8px; }
    .op-form input[type="datetime-local"],
    .op-form input[type="number"],
    .op-form input[type="text"]{
      height:30px; font-size:12px; padding:4px 8px;
    }
    .op-form .now{ display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-soft); }
    .op-form .actions{ justify-self:end; }
    .op-form .btn{ height:30px; padding:0 12px; }

    /* İşlemler sütunu: tek satır ve yeterli genişlik */
    table th.col-actions,
    table td.col-actions {
      width: 190px;            /* gerekirse 180-210px arası oynat */
      white-space: nowrap;     /* buton metni tek satır kalsın */
    }
    table td.col-actions .btn {
      white-space: nowrap;     /* güvence */
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    @media (max-width:1024px){
      .op-form{ grid-template-columns: 1fr 1fr; }
      .op-form .actions{ grid-column: 1 / -1; justify-self:start; }
    }
    @media (max-width:560px){
      .op-form{ grid-template-columns: 1fr; }
    }

    /* Diğer stiller... */
  </style>
</head>
<body>
  <?php require __DIR__.'/partials/topnav.php'; ?>
  <main class="container">
    <div class="top-actions">
      <h1>Operasyon Girişi</h1>
      <div class="muted">Açık: <span class="kbd"><?php echo count($acik); ?></span> • Tamamlanan (son 20): <span class="kbd"><?php echo count($tamamlanan); ?></span></div>
    </div>

    <?php flashes(); ?>

    <section class="cards">
      <div class="card">
        <h2>Yeni operasyon başlat</h2>
        <form method="post">
          <input type="hidden" name="action" value="start">
          <div class="row">
            <div class="col">
              <label>Operasyon adı</label>
              <input type="text" name="ad" placeholder="Örn: 102 nolu iş emri üretimi" required>
            </div>

            <!-- EKLENDİ: Konum(lar) -->
            <div class="col">
              <label>Konum(lar)</label>
              <?php if ($konumColumn && $konumList): ?>
                <div class="konum-box">
                  <div class="konum-scroll">
                    <?php foreach ($konumList as $k): ?>
                      <label class="konum-item">
                        <input type="checkbox" name="konum[]" value="<?= htmlspecialchars($k, ENT_QUOTES,'UTF-8') ?>">
                        <span><?= htmlspecialchars($k, ENT_QUOTES,'UTF-8') ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                  <small class="muted">Birden fazla konum seçebilirsiniz.</small>
                </div>
              <?php else: ?>
                <div id="konumTags" class="tags-input" data-name="konum[]">
                  <input type="text" placeholder="Konum ekle ve Enter’a bas">
                </div>
                <small class="muted">Veritabanında konum sütunu bulunamadı veya değer yok. Elle girin.</small>
              <?php endif; ?>
            </div>

            <div class="col">
              <label>Başlangıç</label>
              <div class="actions">
                <input id="baslangic" type="datetime-local" name="baslangic" class="field">
                <label class="kbd" style="margin-left:6px"><input type="checkbox" name="start_now" value="1" onchange="(function(cb){const el=document.getElementById('baslangic'); if(!el) return; el.disabled=cb.checked; if(cb.checked) el.value='';})(this)"> Şimdi başlat</label>
              </div>
            </div>
          </div>
          <div style="margin-top:12px" class="actions">
            <button type="submit" class="btn btn-primary">Başlat</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h2>Açık operasyonlar</h2>
        <?php if (empty($acik)): ?>
          <div class="muted">Şu anda açık operasyon yok.</div>
        <?php else: ?>
          <div style="overflow:auto">
            <table>
              <thead><tr><th style="width:70px">ID</th><th>Ad</th><th>Konumlar</th><th style="width:190px">Başlangıç</th><th>Bitir</th></tr></thead>
              <tbody>
              <?php foreach ($acik as $op): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$op['id']) ?></td>
                  <td><?= htmlspecialchars($op['ad']) ?></td>
                  <td>
                    <?php
                      $kl = $acikKonum[(int)$op['id']] ?? [];
                      $kstr = $kl ? implode(', ', $kl) : '';
                      echo $kstr !== '' ? htmlspecialchars($kstr, ENT_QUOTES, 'UTF-8') : '-';
                    ?>
                  </td>
                  <td><span class="kbd"><?= htmlspecialchars($op['baslangic']) ?></span></td>
                  <td>
                    <form method="post" class="op-form" onsubmit="return confirm('Operasyonu bitirmek istediğinize emin misiniz?');">
                      <input type="hidden" name="action" value="end">
                      <input type="hidden" name="id" value="<?= htmlspecialchars((string)$op['id']) ?>">

                      <div class="field">
                        <label class="muted">Bitiş</label>
                        <div class="inline">
                          <input id="bitis_<?= (int)$op['id'] ?>" type="datetime-local" name="bitis">
                          <label class="now">
                            <input type="checkbox" name="end_now" value="1"
                              onchange="(function(cb,id){const el=document.getElementById(id); if(!el) return; el.disabled=cb.checked; if(cb.checked) el.value='';})(this,'bitis_<?= (int)$op['id'] ?>')">
                            Şimdi
                          </label>
                        </div>
                      </div>

                      <div class="field">
                        <label class="muted">Miktar (ops.)</label>
                        <input type="number" name="miktar" step="0.001" placeholder="Örn: 120.000">
                      </div>

                      <div class="field">
                        <label class="muted">Birim</label>
                        <input type="text" name="birim" placeholder="adet, kg, ton">
                      </div>

                      <div class="actions">
                        <button type="submit" class="btn btn-danger">Bitir</button>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2>Son tamamlanan 20 operasyon</h2>
        <?php if (empty($tamamlanan)): ?>
          <div class="muted">Kayıt yok.</div>
        <?php else: ?>
          <div style="overflow:auto">
            <table class="table table-striped">
              <thead>
                  <tr>
                      <th>Operasyon Adı</th>
                      <th>Konumlar</th>
                      <th>Başlangıç</th>
                      <th>Bitiş</th>
                      <th>Süre</th>
                      <th>Miktar</th>
                      <th>Birim</th>
                      <th>Toplam Enerji (kWh)</th>
                      <th>Enerji Verimliliği</th>
                      <th class="col-actions">İşlemler</th>
                  </tr>
              </thead>
              <tbody>
                  <?php
                  foreach ($tamamlanan as $row):
                      $baslangic_ts = strtotime($row['baslangic']);
                      $bitis_ts = strtotime($row['bitis']);
                      $sure_dk = ($bitis_ts - $baslangic_ts) / 60;
                  ?>
                  <tr>
                      <td><?= htmlspecialchars($row['ad']) ?></td>
                      <td>
                        <?php
                          $kl = $tamKonum[(int)$row['id']] ?? [];
                          $kstr = $kl ? implode(', ', $kl) : '';
                          echo $kstr !== '' ? htmlspecialchars($kstr, ENT_QUOTES, 'UTF-8') : '-';
                        ?>
                      </td>
                      <td><?= date('d.m.Y H:i', $baslangic_ts) ?></td>
                      <td><?= date('d.m.Y H:i', $bitis_ts) ?></td>
                      <td><?= number_format($sure_dk, 0) ?> dk</td>
                      <td><?= $row['miktar'] ? number_format((float)$row['miktar'], 2) : '-' ?></td>
                      <td><?= $row['birim'] ?: '-' ?></td>
                      <td>
                          <?php if(isset($row['enerji_hesaplandi']) && $row['enerji_hesaplandi'] == 1): ?>
                              <span class="badge badge-success">
                                  <?= number_format((float)($row['toplam_aktif_guc_kwh'] ?? 0), 2) ?> kWh
                              </span>
                              <br><small class="text-muted"><?= (int)($row['analizor_sayisi'] ?? 0) ?> analizör</small>
                          <?php else: ?>
                              <button class="btn btn-sm btn-outline-primary" onclick="hesaplaEnerji(<?= $row['id'] ?>)">
                                  <i class="fas fa-calculator"></i> Hesapla
                              </button>
                          <?php endif; ?>
                      </td>
                      <td>
                        <?php
                        if(!empty($row['_m']['ok']) && $row['miktar'] > 0){
                            $verimlilik = $row['_m']['kwh'] / (float)$row['miktar'];
                            $renk = $verimlilik < 1 ? 'success' : ($verimlilik < 2 ? 'warning' : 'danger');
                        ?>
                          <span class="badge badge-<?= $renk ?>">
                            <?= number_format($verimlilik,3) ?> kWh/<?= htmlspecialchars($row['birim'] ?? '') ?>
                          </span>
                        <?php } else { ?>
                          <span class="text-muted">-</span>
                        <?php } ?>
                      </td>
                      <td class="col-actions">
                          <?php
                            $sIso = date('Y-m-d\TH:i', $baslangic_ts);
                            $eIso = date('Y-m-d\TH:i', $bitis_ts);
                          ?>
                          <a class="btn btn-sm btn-outline-primary"
                             href="grafikler.php?start=<?= urlencode($sIso) ?>&end=<?= urlencode($eIso) ?>"
                             title="Bu operasyon zaman aralığı için grafiklere git">
                              <i class="fas fa-chart-line"></i> Grafiklere Git
                          </a>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <footer style="margin:18px 0; color:var(--text-soft); font-size:12px">
      Klavyeden hızlı giriş: Tab ile alanlar arası geçiş yapabilirsiniz.
    </footer>
  </main>
  <script src="assets/app.js"></script>
  <script>
  // EKLENDİ: tags input davranışı
  (function(){
    function initTagsInput(el){
      const name = el.getAttribute('data-name') || 'konum[]';
      const input = el.querySelector('input');
      if(!input) return;

      function addTag(label){
        label = (label||'').trim();
        if(!label) return;
        // Aynı tag var mı?
        const exists = Array.from(el.querySelectorAll('input[type=hidden]')).some(h=>h.value.toLowerCase()===label.toLowerCase());
        if(exists) return;

        const tag = document.createElement('span');
        tag.className='tag';
        tag.title='Kaldır';
        tag.innerHTML = `<span class="t"></span> <span class="x">×</span>`;
        tag.querySelector('.t').textContent = label;
        tag.addEventListener('click', ()=>{
          tag.remove();
          hidden.remove();
        });
        const hidden = document.createElement('input');
        hidden.type='hidden'; hidden.name = name; hidden.value = label;

        el.insertBefore(tag, input);
        el.appendChild(hidden);
      }

      input.addEventListener('keydown', (e)=>{
        if(e.key==='Enter' || e.key===','){
          e.preventDefault();
          const val = input.value;
          input.value='';
          // virgülle çoklu giriş ayrıştır
          val.split(',').map(s=>s.trim()).filter(Boolean).forEach(addTag);
        } else if(e.key==='Backspace' && input.value===''){
          const lastTag = el.querySelector('.tag:last-of-type');
          const lastHidden = Array.from(el.querySelectorAll('input[type=hidden]')).pop();
          if(lastTag && lastHidden){
            lastTag.remove(); lastHidden.remove();
          }
        }
      });

      el.addEventListener('click', ()=> input.focus());
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      const konum = document.getElementById('konumTags');
      if(konum) initTagsInput(konum);
    });
  })();

  <?php
  // ...$tamamlanan fetch edildikten hemen sonra...
  foreach ($tamamlanan as &$row) {
      if ($row['baslangic'] && $row['bitis']) {
          $m = op_measure_summary($db, $row['baslangic'], $row['bitis']);
          $row['_m'] = $m;
      }
  }
  unset($row);
  // ...existing code...
  ?>
  </script>
</body>
</html>