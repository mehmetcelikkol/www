<?php
// Doğru MIME türünü ayarlayarak doğrudan dosyayı okutma
$file = '01.mp4';

// Dosya mevcut değilse hata döndür
if (!file_exists($file)) {
    die("Dosya bulunamadı.");
}

// MIME türünü manuel olarak belirt
header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($file));

// Video dosyasını doğrudan çıktı olarak gönder
readfile($file);
exit;
?>
