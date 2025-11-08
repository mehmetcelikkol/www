<?php
declare(strict_types=1);

// Basit TXT mail gönderimi. WAMP'ta mail() yapılandırılmamış olabilir; bu durumda logs/email.log'a yazar.
function send_txt_mail(array $toList, string $subject, string $body): array {
    $results = [];
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n".
               "MIME-Version: 1.0\r\n";
    foreach($toList as $to){
        $to = trim((string)$to);
        if($to==='') continue;
        $ok = @mail($to, $subject, $body, $headers);
        $results[$to] = (bool)$ok;
    }
    if(in_array(false, $results, true)){
        // Fallback log
        $dir = __DIR__.'/logs';
        if(!is_dir($dir)) @mkdir($dir, 0777, true);
        $log = $dir.'/email.log';
        $msg = date('Y-m-d H:i:s')." MAIL Fallback\nTO: ".implode(', ', array_keys($results))."\nSUBJECT: $subject\nBODY:\n$body\n\n";
        @file_put_contents($log, $msg, FILE_APPEND);
    }
    return $results;
}

?>
