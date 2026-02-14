<?php
$dosya = "baglanti_noktasi.txt";

echo 'x';

// SERVER (Sen) IP'sini güncellemek için: ?islem=kaydet
if(isset($_GET['islem']) && $_GET['islem'] == 'kaydet') {
    $ip = $_SERVER['REMOTE_ADDR'];
    file_put_contents($dosya, $ip);
    echo "IP Guncellendi: " . $ip;
} 

// CLIENT (Müşteri) IP'yi çekmek için: ?islem=oku
else if(isset($_GET['islem']) && $_GET['islem'] == 'oku') {
    if(file_exists($dosya)) {
        echo file_get_contents($dosya);
    } else {
        echo "IP_YOK";
    }
}
?>