<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medya Görüntüleyici</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        .media-container {
            width: 300px;
            margin: 10px;
            text-align: center;
        }
        img, video {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<?php
// Hata raporlama (geliştirme sürecinde varsa hataları görmek için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Desteklenen fotoğraf ve video dosya uzantıları
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
$videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];

// Geçerli klasördeki tüm dosyaları tarama
$files = glob("*.*");

// Dosya MIME türünü tanımlamak için finfo kullanıyoruz
$finfo = finfo_open(FILEINFO_MIME_TYPE);

// Dosyaların bulunup bulunmadığını kontrol et
if (empty($files)) {
    echo "<p>Bu klasörde görüntülenecek medya dosyası bulunamadı.</p>";
}

// Her dosyayı döngüyle işleyip görüntüleyelim
foreach ($files as $file) {
    $mimeType = finfo_file($finfo, $file);
    $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if (in_array($fileExtension, $imageExtensions)) {
        // Fotoğraf dosyasını görüntüle
        echo '<div class="media-container">';
        echo "<img src='$file' alt='$file'>";
        echo "<p>$file</p>";
        echo '</div>';
    } elseif (in_array($fileExtension, $videoExtensions)) {
        // Video dosyasını görüntüle
        echo '<div class="media-container">';
        echo "<video controls><source src='$file' type='$mimeType'>Tarayıcınız video etiketini desteklemiyor.</video>";
        echo "<p>$file</p>";
        echo '</div>';
    }
}

// MIME bilgi kaynağını kapatma
finfo_close($finfo);
?>

</body>
</html>
