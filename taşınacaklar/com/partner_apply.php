<?php
// Partner application handler
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/mailer.php';
$cfg = require __DIR__.'/mail_config.php';
$TARGET_EMAIL = $cfg['to_partner'];
$FROM_EMAIL   = $cfg['from_email'];

function respond($ok,$msg){ echo json_encode(['success'=>$ok,'message'=>$msg]); exit; }

if($_SERVER['REQUEST_METHOD']!=='POST'){
    respond(false,'Geçersiz istek');
}

if(!empty($_POST['website'])){ // honeypot
    respond(false,'Spam şüphesi');
}

$ts = isset($_POST['form_ts']) ? (int)$_POST['form_ts'] : 0;
if($ts && (time()*1000 - $ts) < 3000){
    respond(false,'Çok hızlı gönderim');
}

$firma   = trim($_POST['firma'] ?? '');
$yetkili = trim($_POST['yetkili'] ?? '');
$email   = trim($_POST['email'] ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$web     = trim($_POST['web'] ?? '');
$tur     = trim($_POST['tur'] ?? '');
$aciklama= trim($_POST['aciklama'] ?? '');

if($firma===''||$yetkili===''||$email===''||$tur===''||$aciklama===''){
    respond(false,'Zorunlu alan eksik');
}
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
    respond(false,'E-posta geçersiz');
}
if(strlen($aciklama) > 8000){
    respond(false,'Açıklama çok uzun');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$subject = 'Yeni İş Ortaklığı Başvurusu: '.$firma;
$body = "Firma: $firma\nYetkili: $yetkili\nE-posta: $email\nTelefon: $telefon\nWeb: $web\nİş Modeli: $tur\nIP: $ip\nUA: $ua\n---\nAçıklama:\n$aciklama\n";

$sent = smtp_send($TARGET_EMAIL,$subject,$body,$email);
global $SMTP_LAST_ERROR;
if(!$sent){
    $fallback = @mail($TARGET_EMAIL,$subject,$body,'From: '.$FROM_EMAIL.'\r\nReply-To: '.$email);
    if(!$fallback){
        @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | PARTNER_FAIL | ".$SMTP_LAST_ERROR."\n",FILE_APPEND);
        respond(false,'Mail gönderilemedi: '.($SMTP_LAST_ERROR?:'SMTP hata'));
    }
    @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | PARTNER_FALLBACK_OK\n",FILE_APPEND);
} else {
    @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | PARTNER_OK\n",FILE_APPEND);
}

$logLine = date('c')." | PARTNER | $ip | $firma | $email | ".str_replace(["\r","\n"],' ', substr($aciklama,0,120))."\n";
@file_put_contents(__DIR__.'/logs/form_submissions.log',$logLine,FILE_APPEND|LOCK_EX);

respond(true,'Başvurunuz alındı. Teşekkürler.');
