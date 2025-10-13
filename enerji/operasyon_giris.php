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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        $ad = trim($_POST['ad'] ?? '');
        $baslangic = dt_from_post($_POST['start_now'] ?? '0', 'baslangic');
        if ($ad === '') {
            flash('error', 'Operasyon adı gereklidir.');
        } elseif (!$baslangic) {
            flash('error', 'Başlangıç tarih-saat geçersiz.');
        } else {
            $stmt = $db->prepare("INSERT INTO enerji_operasyonlar (ad, baslangic) VALUES (?, ?)");
            if (!$stmt) {
                flash('error', 'Sorgu hazırlanamadı.');
            } else {
                $stmt->bind_param('ss', $ad, $baslangic);
                if ($stmt->execute()) {
                    flash('ok', 'Operasyon başlatıldı. ID: ' . $stmt->insert_id);
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
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Operasyon Girişi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/app.css">
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
              <thead><tr><th style="width:70px">ID</th><th>Ad</th><th style="width:190px">Başlangıç</th><th>Bitir</th></tr></thead>
              <tbody>
              <?php foreach ($acik as $op): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$op['id']) ?></td>
                  <td><?= htmlspecialchars($op['ad']) ?></td>
                  <td><span class="kbd"><?= htmlspecialchars($op['baslangic']) ?></span></td>
                  <td>
                    <form method="post" class="op-form" onsubmit="return confirm('Operasyonu bitirmek istediğinize emin misiniz?');" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center">
                      <input type="hidden" name="action" value="end">
                      <input type="hidden" name="id" value="<?= htmlspecialchars((string)$op['id']) ?>">
                      <div class="field">
                        <label class="muted">Bitiş</label>
                        <input id="bitis_<?= (int)$op['id'] ?>" type="datetime-local" name="bitis">
                        <label class="kbd" style="margin-left:6px"><input type="checkbox" name="end_now" value="1" onchange="(function(cb,id){const el=document.getElementById(id); if(!el) return; el.disabled=cb.checked; if(cb.checked) el.value='';})(this,'bitis_<?= (int)$op['id'] ?>')"> Şimdi bitir</label>
                      </div>
                      <div class="field">
                        <label class="muted">Miktar (opsiyonel)</label>
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
            <table>
              <thead><tr><th style="width:70px">ID</th><th>Ad</th><th style="width:190px">Başlangıç</th><th style="width:190px">Bitiş</th><th style="width:120px">Miktar</th><th style="width:120px">Birim</th></tr></thead>
              <tbody>
              <?php foreach ($tamamlanan as $op): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$op['id']) ?></td>
                  <td><?= htmlspecialchars($op['ad']) ?></td>
                  <td><span class="kbd"><?= htmlspecialchars($op['baslangic']) ?></span></td>
                  <td><span class="kbd"><?= htmlspecialchars($op['bitis']) ?></span></td>
                  <td><?= htmlspecialchars((string)$op['miktar']) ?></td>
                  <td><?= htmlspecialchars((string)$op['birim']) ?></td>
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
</body>
</html>