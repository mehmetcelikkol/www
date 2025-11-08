<?php
if (!function_exists('h')) { function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); } }
$user = function_exists('auth_user') ? auth_user() : null;
$me = basename($_SERVER['PHP_SELF']);
function act(string $f): string { return basename($_SERVER['PHP_SELF']) === $f ? 'active' : ''; }
?>
<header class="topbar">
  <div class="inner">
    <div class="brand">Enerji İzleme</div>
    <nav class="nav">
      <a class="<?= act('index.php') ?>" href="index.php">Özet</a>
      <a class="<?= act('grafikler.php') ?>" href="grafikler.php">Grafikler</a>
      <a class="<?= act('operasyon_giris.php') ?>" href="operasyon_giris.php">Operasyonlar</a>
      <a class="<?= act('rapor.php') ?>" href="rapor.php">Rapor</a>
    </nav>
    <div class="top-actions" style="margin-left:auto;display:flex;align-items:center;gap:10px">
      <span class="muted" style="font-size:12px"><?= h(trim(($user['ad']??'').' '.($user['soyad']??''))) ?></span>
      <button id="themeToggle" class="theme-toggle" title="Tema değiştir">Tema</button>
      <a href="logout.php" onclick="return confirm('Çıkış yapılsın mı?')" class="btn btn-ghost" style="text-decoration:none">Çıkış</a>
    </div>
  </div>
</header>