<?php
// Root kopya SMTP teşhis. Test sonrası silin.
header('Content-Type: text/plain; charset=utf-8');
$base = __DIR__; // root
// rmtproje klasörü varsa ona göre config yolu belirle
$cfgPath = is_dir($base.'/rmtproje') && file_exists($base.'/rmtproje/mail_config.php')
    ? $base.'/rmtproje/mail_config.php'
    : $base.'/mail_config.php';
if(!file_exists($cfgPath)){
    echo "mail_config.php bulunamadı: $cfgPath\n"; exit;
}
$cfg = require $cfgPath;
$host = $cfg['host'];
$ports = [$cfg['port'],587,465,25];
$ports = array_values(array_unique($ports));

echo "SMTP Teşhis (root) ".date('c')."\n";
echo "Config yol: $cfgPath\n";
echo "Host: $host\n";

$records = @dns_get_record($host, DNS_A + DNS_AAAA);
if($records){
    echo "DNS:\n"; foreach($records as $r){ if(isset($r['ip'])) echo " A: {$r['ip']}\n"; if(isset($r['ipv6'])) echo " AAAA: {$r['ipv6']}\n"; }
}else{ echo "DNS kaydı alınamadı.\n"; }

foreach($ports as $p){
    $t0=microtime(true); $errno=0;$err=''; $transport = ($p==465?'ssl://':'');
    $s=@fsockopen($transport.$host,$p,$errno,$err,8);
    $ms=round((microtime(true)-$t0)*1000);
    if($s){ $banner=trim(fgets($s,515)); echo "Port $p AÇIK ($ms ms) Banner: $banner\n"; fclose($s);} else { echo "Port $p KAPALI: $err ($errno) ($ms ms)\n";}
}

// Basit 587 STARTTLS
if(in_array(587,$ports,true)){
    echo "\nSTARTTLS(587) test:\n";
    $e=0;$er=''; $s=@fsockopen($host,587,$e,$er,8);
    if(!$s){ echo "Bağlanamadı: $er ($e)\n"; }
    else {
        echo trim(fgets($s,515))."\n";
        fputs($s,"EHLO root-test\r\n");
        $caps=''; $hasTls=false; $i=0;
        while($line=fgets($s,515)){ $i++; $caps.=$line; if(stripos($line,'STARTTLS')!==false) $hasTls=true; if(!preg_match('/^250[ -]/',$line)) break; if($i>40) break; }
        echo ($hasTls?"STARTTLS ilan ediyor\n":"STARTTLS yok\n");
        if($hasTls){
            fputs($s,"STARTTLS\r\n"); $resp=fgets($s,515); echo 'Yanıt: '.trim($resp)."\n"; if(str_starts_with($resp,'220')){ if(@stream_socket_enable_crypto($s,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) echo "TLS handshake OK\n"; else echo "TLS handshake FAIL\n"; }
        }
        fclose($s);
    }
}

echo "\nBitti.\n";
