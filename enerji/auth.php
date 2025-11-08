<?php
// auth.php - basit oturum & kimlik doğrulama yardımcıları
// Tek noktadan include edilmesi yeterli.

declare(strict_types=1);

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// "Beni hatırla" için cookie adı
const AUTH_REMEMBER_COOKIE = 'auth_remember';
const AUTH_REMEMBER_DAYS = 30; // süre

function auth_cookie_set(string $name,string $val,int $days): void {
    setcookie($name,$val,[
        'expires'=>time()+86400*$days,
        'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax'
    ]);
}
function auth_cookie_clear(string $name): void { setcookie($name,'',[ 'expires'=>time()-3600,'path'=>'/']); }

// DB'de remember_token alanı yoksa dinamik eklemek istenirse manuel yapılabilir.
// Burada tabloya field eklendiği varsayılır: ALTER TABLE kullanicilar ADD remember_token VARCHAR(255) NULL, ADD remember_expires DATETIME NULL;

function auth_get_user_table_columns(): array {
    static $cols=null; if($cols!==null) return $cols;
    $cols=[]; try { $db=auth_db(); if($res=$db->query('SHOW COLUMNS FROM kullanicilar')){ while($r=$res->fetch_assoc()){ $cols[$r['Field']]=true; } $res->close(); } } catch(Throwable $e){ }
    return $cols;
}

function auth_issue_remember_token(int $userId): void {
    $cols = auth_get_user_table_columns();
    if(!isset($cols['remember_token']) || !isset($cols['remember_expires'])){ return; }
    try {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time()+86400*AUTH_REMEMBER_DAYS);
        $db = auth_db();
        $st = $db->prepare('UPDATE kullanicilar SET remember_token=?, remember_expires=? WHERE id=?');
        if($st){ $st->bind_param('ssi',$token,$expires,$userId); $st->execute(); $st->close(); auth_cookie_set(AUTH_REMEMBER_COOKIE, $userId.':'.$token, AUTH_REMEMBER_DAYS); }
    } catch(Throwable $e){ /* kolon yoksa ya da hata varsa sessizce geç */ }
}

function auth_try_auto_login(): void {
    if(auth_is_logged()) return;
    if(empty($_COOKIE[AUTH_REMEMBER_COOKIE])) return;
    $cols = auth_get_user_table_columns();
    if(!isset($cols['remember_token']) || !isset($cols['remember_expires'])) return; // özellik yok
    $val = $_COOKIE[AUTH_REMEMBER_COOKIE];
    if(!preg_match('/^(\d+):([a-f0-9]{64})$/',$val,$m)) return;
    $uid = (int)$m[1]; $tok=$m[2];
    try {
        $db = auth_db();
        $st = $db->prepare('SELECT id, ad, soyad, email, rol, remember_token, remember_expires FROM kullanicilar WHERE id=? LIMIT 1');
        if(!$st) return; $st->bind_param('i',$uid); if(!$st->execute()) return; $st->bind_result($id,$ad,$soyad,$em,$rol,$rt,$re); if(!$st->fetch()){ $st->close(); return; } $st->close();
        if(!$rt || !$re) return; if(!hash_equals($rt,$tok)) return; if(strtotime($re) < time()) return; // expired
        $_SESSION['user']=[ 'id'=>$id,'ad'=>$ad,'soyad'=>$soyad,'email'=>$em,'rol'=>$rol,'login_time'=>time(),'auto'=>true ];
        auth_issue_remember_token($id); // rotate
    } catch(Throwable $e){ /* sessiz geç */ }
}

auth_try_auto_login();

// Mevcut config okuma fonksiyonunu tekrar yazmak yerine burada kopya / sade sürüm.
function auth_load_config(): ?array {
    $candidates = [
        'D:/rmt-drive/Has/un enerji analizi/1/Enerji izleme v1/bin/Debug/Enerji izleme v1.exe.config',
    ];
    foreach($candidates as $p){
        if(!is_file($p)) continue;
        $xml = @simplexml_load_file($p);
        if(!$xml) continue;
        if(!isset($xml->connectionStrings)) continue;
        foreach($xml->connectionStrings->add as $add){
            if((string)$add['name']==='MySqlConnection'){
                $connStr=(string)$add['connectionString']; $out=[];
                foreach(explode(';',$connStr) as $seg){ $kv=explode('=',$seg,2); if(count($kv)==2) $out[trim($kv[0])] = trim($kv[1]); }
                return $out;
            }
        }
    }
    return null;
}

function auth_db(): mysqli {
    static $db=null; if($db) return $db;
    $cfg = auth_load_config();
    if(!$cfg) die('Config yok (auth)');
    foreach(['Server','Uid','Pwd','Database'] as $k){ if(!isset($cfg[$k])) die('Eksik config anahtarı: '.$k); }
    $db = @new mysqli($cfg['Server'],$cfg['Uid'],$cfg['Pwd'],$cfg['Database']);
    if($db->connect_error) die('DB bağlanamadı: '.$db->connect_error);
    $db->set_charset('utf8');
    return $db;
}

function auth_is_logged(): bool { return isset($_SESSION['user']); }
function auth_user(){ return $_SESSION['user'] ?? null; }
function auth_require_login(): void { if(!auth_is_logged()){ header('Location: login.php'); exit; } }
function auth_logout(): void { $_SESSION=[]; if(session_id()) session_destroy(); }

function auth_login(string $email, string $password): array {
    $email = trim(mb_strtolower($email));
    if($email==='') return ['ok'=>false,'msg'=>'Email boş'];
    $db = auth_db();
    $sql = 'SELECT id, ad, soyad, email, sifre, rol FROM kullanicilar WHERE email=? LIMIT 1';
    $st = $db->prepare($sql);
    if(!$st) return ['ok'=>false,'msg'=>'Sorgu hatası'];
    $st->bind_param('s',$email);
    if(!$st->execute()) return ['ok'=>false,'msg'=>'Sorgu çalışmadı'];
    $st->bind_result($id,$ad,$soyad,$em,$hash,$rol);
    if(!$st->fetch()) return ['ok'=>false,'msg'=>'Kullanıcı bulunamadı'];
    $st->close();
    $valid=false;
    if(strpos($hash,'$2y$')===0 || strpos($hash,'$argon2')===0){
        $valid = password_verify($password,$hash);
    } else {
        // Düz metin ise geçici olarak karşılaştır
        $valid = hash_equals($hash,$password);
    }
    if(!$valid) return ['ok'=>false,'msg'=>'Şifre hatalı'];
    $_SESSION['user'] = [
        'id'=>$id,
        'ad'=>$ad,
        'soyad'=>$soyad,
        'email'=>$em,
        'rol'=>$rol,
        'login_time'=>time()
    ];
    return ['ok'=>true,'msg'=>'Giriş başarılı'];
}

// ID ile giriş (ekran seçimi için)
function auth_login_by_id(int $id, string $password): array {
    if($id<=0) return ['ok'=>false,'msg'=>'Geçersiz kullanıcı'];
    $db = auth_db();
    $sql = 'SELECT id, ad, soyad, email, sifre, rol FROM kullanicilar WHERE id=? LIMIT 1';
    $st = $db->prepare($sql);
    if(!$st) return ['ok'=>false,'msg'=>'Sorgu hatası'];
    $st->bind_param('i',$id);
    if(!$st->execute()) return ['ok'=>false,'msg'=>'Sorgu çalışmadı'];
    $st->bind_result($rid,$ad,$soyad,$em,$hash,$rol);
    if(!$st->fetch()) return ['ok'=>false,'msg'=>'Kullanıcı bulunamadı'];
    $st->close();
    $valid=false;
    if(strpos($hash,'$2y$')===0 || strpos($hash,'$argon2')===0){
        $valid = password_verify($password,$hash);
    } else {
        $valid = hash_equals($hash,$password);
    }
    if(!$valid) return ['ok'=>false,'msg'=>'Şifre hatalı'];
    $_SESSION['user'] = [
        'id'=>$rid,
        'ad'=>$ad,
        'soyad'=>$soyad,
        'email'=>$em,
        'rol'=>$rol,
        'login_time'=>time()
    ];
    return ['ok'=>true,'msg'=>'Giriş başarılı'];
}

function auth_password_hash(string $plain): string { return password_hash($plain, PASSWORD_BCRYPT); }

?>
