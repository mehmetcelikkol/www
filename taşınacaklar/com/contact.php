<?php
// Basit iletişim sayfası (form yok)
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>İletişim | RMT Proje</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; color: #222;}
    .wrap { max-width: 820px; margin: 0 auto; padding: 24px; }
    .card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
    h1 { margin: 0 0 12px; font-size: 28px; }
    p { margin: 8px 0; line-height: 1.6; }
    .cta { margin-top: 12px; display: inline-block; background: #c8102e; color: #fff; padding: 12px 16px; border-radius: 8px; text-decoration: none; }
    .tel { font-size: 22px; font-weight: 600; color: #c8102e; text-decoration: none; }
    .muted { color: #666; font-size: 14px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>İletişim</h1>
      <p>Bize hızlıca ulaşmak için telefon numaramıza tıklayabilirsiniz:</p>

      <!-- LÜTFEN gerçek telefon numaranızı aşağıya yerleştirin -->
      <p><a class="tel" href="tel:+902666060132" aria-label="Telefonla ara">+90 (266) 606 01 32</a></p>

      <p class="muted">Masaüstünde tıklama, varsayılan arama uygulamanızı açar.</p>
      <hr style="border:none;border-top:1px solid #eee;margin:16px 0" />
      <p>E‑posta ile iletişim: <a href="mailto:rmt@rmtproje.com">rmt@rmtproje.com</a></p>
      <p class="muted">Çalışma saatleri: Hafta içi 09:00–18:00</p>
    </div>
  </div>
</body>
</html>
