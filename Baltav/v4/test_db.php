<?php
$denemeler = ['silosense', 'proje_silosense', 'silo', 'test', 'mysql', 'information_schema'];
$bulunan = null;

echo "<h1>🔍 Veritabanı Tarayıcı</h1>";

foreach ($denemeler as $db_name) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=$db_name", 'root', '');
        echo "<p style='color:green'>✅ <b>$db_name</b>: BAĞLANDI!</p>";
        $bulunan = $db_name;
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ <b>$db_name</b>: " . $e->getMessage() . "</p>";
    }
}

if ($bulunan) {
    echo "<h3>🎉 SONUÇ: Doğru Veritabanı Adı muhtemelen: <b>$bulunan</b> (veya 'mysql' gibi sistem tabloları)</h3>";
} else {
    echo "<h3>😞 Hiçbirine bağlanılamadı. Kullanıcı/Şifre hatası olabilir.</h3>";
}
?>
