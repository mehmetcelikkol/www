<?php
// Basit manuel test: /test_smtp_send.php?to=you@example.com
// 500 hatası için try/catch ve basic error reporting ekledik.
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',0);
set_error_handler(function($no,$str,$file,$line){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'PHP ERROR: '.$str,'file'=>$file,'line'=>$line]);
  exit;
});
set_exception_handler(function($ex){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'EXCEPTION: '.$ex->getMessage(),'trace'=>$ex->getTraceAsString()]);
  exit;
});

$mailerPath = __DIR__.'/rmtproje/mailer.php';
$cfgPath    = __DIR__.'/rmtproje/mail_config.php';
if(!file_exists($mailerPath) || !file_exists($cfgPath)){
  echo json_encode(['success'=>false,'error'=>'Dosyalar bulunamadı','mailer_exists'=>file_exists($mailerPath),'config_exists'=>file_exists($cfgPath),'cwd'=>__DIR__]);
  exit;
}
require $mailerPath;
$cfg = require $cfgPath;
$to = isset($_GET['to']) && filter_var($_GET['to'],FILTER_VALIDATE_EMAIL) ? $_GET['to'] : $cfg['to_contact'];
$ok = smtp_send($to,'SMTP Test Mesajı','Bu bir testtir '.date('c'));
global $SMTP_LAST_ERROR, $SMTP_TRANSCRIPT; 
echo json_encode([
  'success'=>$ok,
  'error'=>$ok?null:$SMTP_LAST_ERROR,
  'transcript'=>$SMTP_TRANSCRIPT,
  'env'=>[
   'php_version'=>PHP_VERSION,
   'loaded_extensions'=>get_loaded_extensions(),
   'working_dir'=>__DIR__
  ]
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
