<?php
require __DIR__.'/auth.php'; auth_require_login();
header('Content-Type: text/plain; charset=utf-8');

echo "MySQL kontrolü:\n";
$envFile = __DIR__.'/.env';
$env = [];
if (is_file($envFile)) {
  foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0]==='#' || !str_contains($line,'=')) continue;
    [$k,$v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, "\"'");
  }
}
try {
  $mysqli = @new mysqli($env['DB_HOST']??'127.0.0.1', $env['DB_USER']??'root', $env['DB_PASS']??'', $env['DB_NAME']??'enerji');
  if($mysqli->connect_errno){ echo " MySQL bağlanamadı: {$mysqli->connect_error}\n"; }
  else {
    $mysqli->set_charset('utf8');
    $c = $mysqli->query("SELECT COUNT(*) c FROM cihazlar")->fetch_assoc()['c'] ?? 0;
    $o = $mysqli->query("SELECT COUNT(*) c FROM olcumler")->fetch_assoc()['c'] ?? 0;
    echo " cihazlar: $c\n olcumler: $o\n";
    $mysqli->close();
  }
} catch(Throwable $e){ echo " MySQL hata: ".$e->getMessage()."\n"; }

echo "\nSQLite kontrolü (energy.db):\n";
$sqlitePath = 'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/energy.db';
if(!is_file($sqlitePath)){ echo " Dosya yok: $sqlitePath\n"; exit; }
try{
  $pdo = new PDO('sqlite:'.$sqlitePath);
  $c = (int)$pdo->query("SELECT COUNT(*) FROM cihazlar")->fetchColumn();
  $o = (int)$pdo->query("SELECT COUNT(*) FROM olcumler")->fetchColumn();
  echo " cihazlar: $c\n olcumler: $o\n";
}catch(Throwable $e){ echo " SQLite hata: ".$e->getMessage()."\n"; }

// filepath: c:\wamp64\www\enerji\grafikler2.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Grafikler 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- grafikler.php’de ne link varsa birebir kopyalayın -->
    <link rel="stylesheet" href="assets/app.css">
    <!-- Eğer grafikler.php başka css dosyaları kullanıyorsa buraya ekleyin -->
    <!-- <link rel="stylesheet" href="assets/style.css"> -->

    <!-- NOT: Buradaki inline <style> bloklarını kaldırın -->
</head>
<body>
<?php require __DIR__.'/partials/topnav.php'; ?>
<div class="container">
  <div id="chartRegion"></div>
  <!-- ...existing code (özet/hata panelleri) ... -->
</div>

<script>
window.DEVICES_FROM_DB = <?= json_encode($chartDevices, $JSON_FLAGS) ?>;
window.DEVICES         = <?= json_encode($devicesOut,   $JSON_FLAGS) ?>;
window.OPS_FROM_SERVER = <?= json_encode($opsOut,       $JSON_FLAGS) ?>;
window.USE_DATE_FILTER = <?= $useDateFilter ? 'true' : 'false' ?>;
window.RANGE_START_ISO = '<?= h($startDT->format('Y-m-d H:i:s')) ?>';
window.RANGE_END_ISO   = '<?= h($endDT->format('Y-m-d H:i:s')) ?>';
</script>

<script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js" defer></script>
<script src="assets/grafikler2.js" defer></script>
</body>
</html>