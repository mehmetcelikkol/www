<?php
/** Basit SMTP mailer (PHPMailer yerine hafif) - geliştirilmiş teşhis **/
// Dışarıdan en son hata ve transcript'e erişim için global değişkenler
global $SMTP_LAST_ERROR, $SMTP_TRANSCRIPT;

function smtp_send($to,$subject,$body,$reply=null,$opts=[]){
    global $SMTP_LAST_ERROR, $SMTP_TRANSCRIPT;
    $SMTP_LAST_ERROR = null;
    $SMTP_TRANSCRIPT = [];
    $cfg = require __DIR__.'/mail_config.php';

    $host = $opts['host']   ?? $cfg['host'];
    $port = $opts['port']   ?? $cfg['port'];
    $secure = strtolower($opts['secure'] ?? $cfg['secure']); // tls | ssl | none
    $username = $opts['username'] ?? $cfg['username'];
    $password = $opts['password'] ?? $cfg['password'];
    $from = $opts['from_email'] ?? $cfg['from_email'];
    $fromName = $opts['from_name'] ?? $cfg['from_name'];
    $timeout = $opts['timeout'] ?? 12;
    $tryPlainFirstOnTls = true; // TLS için önce plain bağlan, STARTTLS yap

    $newline = "\r\n";   

    // Bağlantı oluşturma (port fallback sırası: config -> 587 -> 465 -> 25)
    $candidatePorts = [$port];
    foreach([587,465,25] as $p){ if(!in_array($p,$candidatePorts,true)) $candidatePorts[]=$p; }

    $socket = null; $usedPort = null; $usedSecure = $secure; $connErr='';
    foreach($candidatePorts as $p){
        $transport = ($p==465 || ($usedSecure==='ssl')) ? 'ssl://' : '';
        $start = microtime(true);
        $sock = @fsockopen($transport.$host, $p, $errno, $errstr, $timeout);
        $elapsed = round((microtime(true)-$start)*1000); // ms
        if($sock){
            $socket = $sock; $usedPort = $p; $usedSecure = ($p==465?'ssl':$secure); 
            _smtp_note("CONNECT $host:$p ($elapsed ms) OK");
            break;
        } else {
            _smtp_note("CONNECT FAIL $host:$p -> $errstr ($errno)");
            $connErr .= "$p:$errstr($errno); ";
        }
    }
    if(!$socket){
        $SMTP_LAST_ERROR = 'Bağlantı kurulamadı: '.$connErr;
        return false;
    }

    if(!smtp_expect($socket,[220])){ $SMTP_LAST_ERROR = '220 banner alınamadı'; return false; }

    // EHLO hostname: mümkünse gönderen domain (reverse DNS yoksa da sorun olmaz)
    $fromDomain = substr(strrchr($from,'@'),1) ?: 'localhost';
    $localHost = $fromDomain;
    if(!smtp_cmd($socket,"EHLO $localHost")){ $SMTP_LAST_ERROR=_smtp_err('EHLO başarısız'); return false; }

    if($usedSecure === 'tls'){
        if(!smtp_cmd($socket,'STARTTLS')){ $SMTP_LAST_ERROR=_smtp_err('STARTTLS reddedildi'); return false; }
        if(!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)){
            $meta = stream_get_meta_data($socket);
            $SMTP_LAST_ERROR='TLS kurulamadı '.json_encode($meta);
            return false;
        }
        if(!smtp_cmd($socket,"EHLO $localHost")){ $SMTP_LAST_ERROR=_smtp_err('TLS sonrası EHLO başarısız'); return false; }
    }

    // AUTH LOGIN
    if(!smtp_cmd($socket,'AUTH LOGIN')){ $SMTP_LAST_ERROR=_smtp_err('AUTH LOGIN reddedildi'); return false; }
    if(!smtp_cmd($socket, base64_encode($username))){ $SMTP_LAST_ERROR=_smtp_err('Kullanıcı adı reddedildi'); return false; }
    if(!smtp_cmd($socket, base64_encode($password))){ $SMTP_LAST_ERROR=_smtp_err('Parola reddedildi'); return false; }

    if(!smtp_cmd($socket, "MAIL FROM:<$from>")){ $SMTP_LAST_ERROR=_smtp_err('MAIL FROM reddedildi'); return false; }
    if(!smtp_cmd($socket, "RCPT TO:<$to>")){ $SMTP_LAST_ERROR=_smtp_err('RCPT TO reddedildi'); return false; }
    if(!smtp_cmd($socket, 'DATA')){ $SMTP_LAST_ERROR=_smtp_err('DATA reddedildi'); return false; }

    $headers = [];
    $headers[] = "From: $fromName <$from>";
    if($reply){ $headers[] = "Reply-To: $reply"; }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'X-Mailer: SimpleSMTP';

    if(!function_exists('mb_encode_mimeheader')){
        // basit fallback (utf-8 base64)
        $encoded = '=?UTF-8?B?'.base64_encode($subject).'?=';
    } else {
        $encoded = mb_encode_mimeheader($subject,'UTF-8');
    }
    $msg = 'Subject: '.$encoded.$newline;
    foreach($headers as $h){ $msg .= $h.$newline; }
    $msg .= $newline.$body.$newline.'.'.$newline;
    _smtp_note('C: (DATA BODY)');
    fputs($socket,$msg);

    if(!smtp_expect($socket,[250])){ $SMTP_LAST_ERROR=_smtp_err('DATA sonu 250 gelmedi'); return false; }
    smtp_cmd($socket,'QUIT');
    fclose($socket);
    return true;
}

function _smtp_note($line){
    global $SMTP_TRANSCRIPT; 
    $SMTP_TRANSCRIPT[] = $line; 
    $logDir = __DIR__.'/logs';
    if(!is_dir($logDir)) @mkdir($logDir,0775,true);
    @file_put_contents($logDir.'/smtp_debug.log',date('c')." | $line\n",FILE_APPEND);
}

function _smtp_err($msg){
    global $SMTP_LAST_SERVER_LINE, $SMTP_TRANSCRIPT; 
    if($SMTP_LAST_SERVER_LINE){ $msg .= ' | Son: '.$SMTP_LAST_SERVER_LINE; }
    _smtp_note('ERROR: '.$msg);
    return $msg;
}

function smtp_cmd($socket,$cmd){
    _smtp_note('C: '.$cmd);
    fputs($socket,$cmd."\r\n");
    return smtp_expect($socket); 
}

function smtp_expect($socket,$codes=[250,251,354,220,235,334]){
    global $SMTP_LAST_SERVER_LINE;
    $buffer='';
    $attempts=0;
    while($line = fgets($socket,515)){
        $buffer .= $line;
        $attempts++;
        if($attempts>50) break; // safety
        if(preg_match('/^(\d{3})([ -])(.*)$/',$line,$m)){
            $code = (int)$m[1];
            $sep  = $m[2];
            $SMTP_LAST_SERVER_LINE = trim($line);
            _smtp_note('S: '.trim($line));
            if($sep==='-'){
                continue; // multi-line devam
            } else {
                return in_array($code,$codes, true);
            }
        } else {
            _smtp_note('S?: '.trim($line));
        }
    }
    _smtp_note('FAIL RESP: '.str_replace("\n"," ",$buffer));
    return false;
}
