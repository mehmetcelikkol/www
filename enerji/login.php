<?php
declare(strict_types=1);
require __DIR__.'/auth.php';

if(auth_is_logged()){
  header('Location: index.php');
    exit;
}

$err=null; $info=null; $users=[]; $sel=null;
// Kullanıcı listesini çek
try {
  $db = auth_db();
  $qr = $db->query('SELECT id, ad, soyad, email FROM kullanicilar ORDER BY ad, soyad');
  if($qr){ while($row=$qr->fetch_assoc()){ $users[]=$row; } $qr->close(); }
} catch(Throwable $e){ $err='Kullanıcı listesi alınamadı'; }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $uid = isset($_POST['uid'])? (int)$_POST['uid'] : 0;
  $sel=$uid;
  $pass=$_POST['sifre']??'';
  $remember = !empty($_POST['remember']);
  $res = auth_login_by_id($uid,$pass);
  if($res['ok']){
    if($remember && isset($_SESSION['user']['id'])){
      auth_issue_remember_token($_SESSION['user']['id']);
    }
    header('Location: index.php'); exit;
  } else $err=$res['msg'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<title>Giriş</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<style>
*{box-sizing:border-box;-webkit-font-smoothing:antialiased}
body{--bg1:#0f172a;--bg2:#031f3a;--grad1:#0a5ad9;--grad2:#4dd2ff;--accent:#1d9bf0;--danger:#d91d3f;--glass:rgba(255,255,255,0.10);--glass-border:rgba(255,255,255,0.25);--text:#e9f1ff;--text-soft:#b9c6d8;--input-bg:rgba(255,255,255,0.12);--input-border:rgba(255,255,255,0.28);--shadow:0 18px 40px -18px rgba(0,0,0,.55);font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:radial-gradient(circle at 25% 20%,#12345b 0%,#061425 55%,#020c15 90%);min-height:100vh;margin:0;color:var(--text);display:flex;align-items:center;justify-content:center;padding:40px;position:relative;overflow:hidden}
/* Light tema (tüm sayfalarda appTheme='light' ile eşleşir) */
body.light{--bg1:#fafafa;--bg2:#f0f5ff;--grad1:#0072ff;--grad2:#00ffc6;--accent:#0066ff;--danger:#d91d3f;--glass:rgba(0,0,0,0.25);--glass-border:rgba(255,255,255,0.35);--text:#1f2933;--text-soft:#4d5b68;--input-bg:rgba(255,255,255,.65);--input-border:rgba(0,0,0,.2);background:linear-gradient(140deg,#e6f4ff,#ffffff 45%,#e8fff7);color:var(--text)}
body::before,body::after{content:"";position:absolute;inset:0;pointer-events:none}
body::before{background:linear-gradient(120deg,rgba(32,119,255,.20),rgba(0,255,188,.12),rgba(255,255,255,0));filter:blur(60px);mix-blend-mode:overlay;animation:flow 14s linear infinite alternate}
body::after{background-image:repeating-linear-gradient(-45deg,rgba(255,255,255,0.03) 0 2px,transparent 2px 6px);opacity:.35}
@keyframes flow{0%{transform:translateY(-12%)}100%{transform:translateY(12%)}}

.energy-line{position:absolute;left:0;right:0;top:50%;height:2px;background:linear-gradient(90deg,transparent,rgba(0,255,170,.9),transparent);filter:drop-shadow(0 0 6px rgba(0,255,180,.9));animation:pulse 4s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:translateY(-50%) scaleX(.4);opacity:.25}50%{transform:translateY(-50%) scaleX(1);opacity:.9}}

.box{width:360px;position:relative;padding:34px 34px 30px;border-radius:26px;background:linear-gradient(145deg,rgba(255,255,255,0.18),rgba(255,255,255,0.05));backdrop-filter:blur(22px) saturate(1.4);-webkit-backdrop-filter:blur(22px) saturate(1.4);border:1px solid var(--glass-border);box-shadow:var(--shadow)}
.box::before{content:"";position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(160deg,rgba(255,255,255,.55),rgba(255,255,255,.05));-webkit-mask:linear-gradient(#000,#000) content-box,linear-gradient(#000,#000);mask:linear-gradient(#000,#000) content-box,linear-gradient(#000,#000);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none;opacity:.55}
.brand{display:flex;align-items:center;gap:10px;margin:0 0 22px}
.brand-large{display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 0 26px;text-align:center}
/* Logo alanı sadeleştirildi: efektler, blur, animasyon kaldırıldı */
.logo-aura{position:relative;width:170px;height:170px;border-radius:32px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0f2538;border:1px solid rgba(255,255,255,0.12)}
.logo-aura::before,.logo-aura::after{display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.logo-aura img{width:80%;height:80%;object-fit:contain;filter:none}
.brand-title{margin:20px 0 6px;font-size:32px;letter-spacing:.7px;font-weight:600;line-height:1.12;background:linear-gradient(90deg,#e6f7ff,#9cdeff,#53ffc8);-webkit-background-clip:text;background-clip:text;color:transparent}
.brand-tag{font-size:12px;letter-spacing:2.5px;text-transform:uppercase;color:var(--text-soft);opacity:.75;font-weight:500}
body.dark .brand-title{background:linear-gradient(90deg,#005596,#00a0c7,#00cfa5);-webkit-background-clip:text;background-clip:text;color:transparent}
@media (max-width:520px){.logo-aura{width:130px;height:130px;border-radius:42px}.brand-title{font-size:26px;margin-top:16px}}
.logo-wrap{width:54px;height:54px;border-radius:18px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;background:radial-gradient(circle at 30% 30%,rgba(255,255,255,0.55),rgba(255,255,255,0.15));backdrop-filter:blur(8px) saturate(1.4);-webkit-backdrop-filter:blur(8px) saturate(1.4);border:1px solid rgba(255,255,255,0.35);box-shadow:0 10px 22px -10px rgba(0,0,0,.55),0 4px 12px -6px rgba(0,0,0,.5)}
body.dark .logo-wrap{background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.95),rgba(255,255,255,.35));border-color:rgba(0,0,0,.15)}
.logo-wrap img{width:100%;height:100%;object-fit:contain;mix-blend-mode:normal;filter:drop-shadow(0 2px 4px rgba(0,0,0,.35))}
.logo-circle{width:44px;height:44px;border-radius:16px;background:linear-gradient(135deg,#1e90ff,#14d4b9);display:grid;place-items:center;font-weight:600;font-size:18px;color:#fff;letter-spacing:.5px;box-shadow:0 6px 18px -6px rgba(20,180,255,.6),0 6px 14px -6px rgba(0,0,0,.6)}
.brand h1{flex:1;font-size:19px;line-height:1.2;margin:0;font-weight:600;letter-spacing:.5px;background:linear-gradient(90deg,#f2fbff,#8ddcfe);-webkit-background-clip:text;background-clip:text;color:transparent}
label{display:block;font-size:12px;font-weight:500;letter-spacing:.5px;margin:0 0 14px;text-transform:uppercase;color:var(--text-soft)}
.field{margin-bottom:18px}
select,input[type=password]{width:100%;background:var(--input-bg);border:1px solid var(--input-border);border-radius:14px;padding:12px 16px 12px 14px;font-size:14px;color:var(--text);outline:none;transition:.25s;border-right:36px solid transparent;position:relative}
select:focus,input[type=password]:focus{border-color:#3ab8ff;box-shadow:0 0 0 3px rgba(0,140,255,.35)}
select{appearance:none;-moz-appearance:none;-webkit-appearance:none;background-image:linear-gradient(45deg,transparent 50%,#8fb9ff 50%),linear-gradient(135deg,#8fb9ff 50%,transparent 50%),radial-gradient(circle at 50% 50%,rgba(255,255,255,.12),rgba(255,255,255,0));background-position:calc(100% - 26px) 52%,calc(100% - 20px) 52%,center;background-size:6px 6px,6px 6px,100% 100%;background-repeat:no-repeat;cursor:pointer}
/* Açılır liste (native) okunabilirlik düzeltmeleri */
body:not(.dark) select{color:#0e2a40}
body:not(.dark) select option{color:#0e2a40;background:#ffffff}
body.dark select option{color:#e9f1ff;background:#1b2735}
/* Hover ve seçili (çoğu tarayıcı destekler) */
select option:hover,select option:focus{background:#0a64c2;color:#fff}
select option:checked{background:#0a64c2 linear-gradient(#0a64c2,#0a64c2);color:#fff}
.user-preview{display:flex;align-items:center;gap:10px;margin:-4px 0 14px;font-size:13px;color:var(--text-soft);min-height:22px}
.avatar{width:28px;height:28px;border-radius:10px;background:linear-gradient(135deg,#3aa2ff,#14cba5);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#fff}
button{width:100%;padding:14px 18px;border:0;border-radius:16px;background:linear-gradient(90deg,#1d8bff,#15d1b4);color:#fff;font-weight:600;letter-spacing:.6px;font-size:14px;cursor:pointer;box-shadow:0 10px 24px -10px rgba(0,180,255,.55),0 4px 14px -8px rgba(0,0,0,.6);transition:.3s}
button:hover{filter:brightness(1.08)}
button:active{transform:translateY(1px)}
.msg{margin:0 0 16px;font-size:12.5px;padding:10px 12px 11px;border-radius:14px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.err{background:linear-gradient(145deg,rgba(255,43,72,.22),rgba(255,43,72,.08));border:1px solid rgba(255,100,120,.55);color:#ffc9d1}
.info{background:linear-gradient(145deg,rgba(15,150,255,.22),rgba(15,150,255,.08));border:1px solid rgba(120,190,255,.55);color:#d0efff}
small{display:block;margin-top:22px;color:var(--text-soft);font-size:10.5px;text-align:center;letter-spacing:.6px}
.top-actions{position:absolute;top:10px;right:12px;display:flex;gap:6px}
.toggle{width:36px;height:36px;border-radius:14px;background:rgba(255,255,255,0.14);display:grid;place-items:center;color:#fff;font-size:16px;cursor:pointer;user-select:none;transition:.3s}
.toggle:hover{background:rgba(255,255,255,0.24)}
/* Eski body.dark desteği (geriye dönük) */
body.dark .box, body.light .box{background:linear-gradient(150deg,rgba(255,255,255,.9),rgba(255,255,255,.75))}
body.dark .brand h1, body.light .brand h1{background:linear-gradient(90deg,#004a96,#0094c7);-webkit-background-clip:text;background-clip:text;color:transparent}
@media (max-width:520px){body{padding:16px}.box{width:100%;padding:30px 26px;border-radius:24px}}
::-webkit-scrollbar{width:10px}::-webkit-scrollbar-track{background:rgba(255,255,255,0.04)}::-webkit-scrollbar-thumb{background:linear-gradient(#1574ff,#13c7b7);border-radius:20px}
</style>
</head>
<body>
<div class="energy-line"></div>
<div class="box" id="loginBox">
  <div class="top-actions">
    <div class="toggle" id="themeT" title="Tema Değiştir">🌗</div>
  </div>
  <div class="brand-large">
    <div class="logo-aura">
      <img src="img/logo.png" alt="Şirket Logosu" onerror="this.style.opacity=0;this.parentElement.style.background='linear-gradient(135deg,#0b65d4,#14c9b0)';">
    </div>
    <div class="brand-title">Enerji İzleme</div>
    <div class="brand-tag">KONTROL • ANALİZ • VERİ</div>
  </div>
  <?php if($err): ?><div class="msg err"><?= htmlspecialchars($err,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
  <?php if($info): ?><div class="msg info"><?= htmlspecialchars($info,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
  <form method="post" autocomplete="off" id="loginForm">
    <div class="field">
      <label style="margin-bottom:6px">Kullanıcı</label>
      <div style="position:relative">
        <select name="uid" id="userSelect" required>
          <option value="">— Seçiniz —</option>
          <?php foreach($users as $u): $id=(int)$u['id']; $nm=trim(($u['ad']??'').' '.($u['soyad']??'')); ?>
            <option value="<?= $id ?>" <?= ($sel===$id?'selected':'') ?>><?= htmlspecialchars($nm,ENT_QUOTES,'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="user-preview" id="userPreview"></div>
    </div>
    <div class="field">
      <label style="margin-bottom:6px">Şifre</label>
      <input type="password" name="sifre" id="pwd" required autocomplete="current-password" placeholder="••••••••">
    </div>
    <div class="field" style="margin-top:-4px;display:flex;align-items:center;justify-content:space-between;gap:10px">
      <label style="margin:0;display:flex;align-items:center;gap:8px;font-size:11px;letter-spacing:.5px;text-transform:none;color:var(--text-soft)">
        <input type="checkbox" name="remember" value="1" style="width:16px;height:16px;margin:0;accent-color:#1580ff;cursor:pointer"> Beni hatırla
      </label>
      <span style="font-size:11px;color:var(--text-soft);opacity:.7" id="autoInfo"></span>
    </div>
    <button type="submit">Giriş</button>
  </form>
  <small>© <?= date('Y') ?> Enerji İzleme • v1</small>
</div>
<script>
// Genel tema anahtarı: appTheme (light | dark)
const t=document.getElementById('themeT');
const migrateOld=()=>{
  // Eski anahtarları yeni yapıya taşı (loginTheme)
  const old=localStorage.getItem('loginTheme');
  if(old){
    if(old==='light'){localStorage.setItem('appTheme','light');}
    localStorage.removeItem('loginTheme');
  }
  const charts=localStorage.getItem('chartsTheme');
  if(charts){
    if(charts==='light'){localStorage.setItem('appTheme','light');}
    localStorage.removeItem('chartsTheme');
  }
};
migrateOld();
const pref=localStorage.getItem('appTheme');
if(pref==='light') document.body.classList.add('light');
t&&t.addEventListener('click',()=>{
  document.body.classList.toggle('light');
  localStorage.setItem('appTheme',document.body.classList.contains('light')?'light':'dark');
});

// Kullanıcı seçimi önizleme (avatar + ad soyad)
const sel=document.getElementById('userSelect');
const prev=document.getElementById('userPreview');
function upd(){
  const opt=sel.options[sel.selectedIndex];
  if(!opt||!opt.value){prev.innerHTML='';return;}
  const name=opt.text.trim();
  const initials=name.split(/\s+/).filter(Boolean).slice(0,2).map(p=>p[0].toUpperCase()).join('');
  prev.innerHTML='<div class="avatar">'+initials+'</div><span>'+name+'</span>';
}
sel&&sel.addEventListener('change',upd); upd();

// Enter basınca boş kullanıcı seçiliyse selecte fokus
document.getElementById('loginForm').addEventListener('submit',e=>{if(!sel.value){sel.focus();e.preventDefault();}});
</script>
</body>
</html>
