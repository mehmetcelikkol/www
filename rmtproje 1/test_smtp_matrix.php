<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/rmtproje/mailer.php';
$cfg = require __DIR__.'/rmtproje/mail_config.php';
$scenarios = [
  ['secure'=>'tls','port'=>587,'label'=>'tls-587'],
  ['secure'=>'none','port'=>587,'label'=>'plain-587'],
  ['secure'=>'none','port'=>25,'label'=>'plain-25'],
];
$results=[];
foreach($scenarios as $s){
    $ok = smtp_send($cfg['to_contact'],'Matrix Test '.$s['label'],'Deneme '.date('c'),null,[
        'secure'=>$s['secure'],
        'port'=>$s['port']
    ]);
    global $SMTP_LAST_ERROR,$SMTP_TRANSCRIPT; 
    $results[]=[
        'scenario'=>$s['label'],
        'success'=>$ok,
        'error'=>$ok?null:$SMTP_LAST_ERROR,
        'last_steps'=>array_slice($SMTP_TRANSCRIPT,-8)
    ];
}

echo json_encode(['host'=>$cfg['host'],'from'=>$cfg['from_email'],'results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
