<?php
// Basic contact form handler
// Security: simple rate limit via timestamp diff, honeypot, basic sanitization

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/mailer.php';
$cfg = require __DIR__.'/mail_config.php';
$TARGET_EMAIL = $cfg['to_contact'];
$FROM_EMAIL   = $cfg['from_email'];

function respond($ok, $msg){
    echo json_encode(['success'=>$ok,'message'=>$msg]);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    respond(false,'Geçersiz istek.');
}

// Honeypot
if(!empty($_POST['website'])){
    respond(false,'Spam reddedildi.');
}

// Timestamp basic check
$ts = isset($_POST['form_ts']) ? (int)$_POST['form_ts'] : 0;
if($ts && (time()*1000 - $ts) < 2500){
    respond(false,'Çok hızlı gönderim.');
}

// Required fields
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if($name==='' || $email==='' || $service==='' || $message===''){
    respond(false,'Lütfen zorunlu alanları doldurun.');
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    respond(false,'E-posta geçersiz.');
}

// Basic length guard
if(strlen($message) > 5000){
    respond(false,'Mesaj çok uzun.');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$subject = 'Yeni İletişim Formu: ' . $name;
$body = "İsim: $name\nE-posta: $email\nTelefon: $phone\nHizmet: $service\nIP: $ip\nUA: $ua\n---\nMesaj:\n$message\n";

$sent = smtp_send($TARGET_EMAIL,$subject,$body,$email);
global $SMTP_LAST_ERROR;
if(!$sent){
    $fallback = @mail($TARGET_EMAIL,$subject,$body,'From: '.$FROM_EMAIL.'\r\nReply-To: '.$email);
    if(!$fallback){
        @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | CONTACT_FAIL | ".$SMTP_LAST_ERROR."\n",FILE_APPEND);
        respond(false,'Mail gönderilemedi: '.($SMTP_LAST_ERROR?:'SMTP hata'));
    }
    @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | CONTACT_FALLBACK_OK\n",FILE_APPEND);
} else {
    @file_put_contents(__DIR__.'/logs/smtp_debug.log',date('c')." | CONTACT_OK\n",FILE_APPEND);
}

// Log
$logLine = date('c').' | CONTACT | '.$ip.' | '.$name.' | '.$email.' | '.str_replace(["\r","\n"],' ', substr($message,0,120))."\n";
@file_put_contents(__DIR__.'/logs/form_submissions.log',$logLine,FILE_APPEND|LOCK_EX);

respond(true,'Mesajınız alındı. Teşekkürler.');
