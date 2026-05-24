<?php





$host = "localhost";

$dbname = "rmtproje_crm";

$username = "rmtproje_crm";

$password = "0120+0120aA";



/*

$host = "localhost";

$dbname = "proje_crm";

$username = "root";

$password = "";

*/





try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Veritabanı bağlantısı sağlanamadı: " . $e->getMessage());

}

?>

