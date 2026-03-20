<?php
// MySQL veritabanı bağlantı ayarları
$servername = "localhost";
$username = "proje_silosense";
$password = "0120a0120A";
$dbname = "proje_silosense";

// Bağlantı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// POST isteğini kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Rastgele deger (int) oluştur (1-1000 arasında)
    $deger = rand(1, 1000);
    
    // Rastgele yedek (varchar(3)) oluştur (3 karakterlik)
    $karakterler = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $yedek = substr(str_shuffle($karakterler), 0, 3);
    
    // SQL sorgusu hazırla
    $sql = "INSERT INTO test (deger, yedek) VALUES (?, ?)";
    
    // Prepared statement kullan
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $deger, $yedek);
    
    if ($stmt->execute()) {
        $response = array(
            "status" => "başarılı",
            "message" => "Veri başarıyla eklendi",
            "deger" => $deger,
            "yedek" => $yedek
        );
    } else {
        $response = array(
            "status" => "hata",
            "message" => "Veri eklenirken hata oluştu: " . $stmt->error
        );
    }
    
    $stmt->close();
    
    // JSON olarak cevap dön
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Verileri Ekleme</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Test Tablosuna Rastgele Veri Ekle</h2>
        <p>POST isteği gönder:</p>
        <button onclick="sendData()">Veri Ekle</button>
        
        <div id="result" class="result"></div>
    </div>

    <script>
        function sendData() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('result');
                if (data.status === 'başarılı') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✓ ${data.message}</strong><br>
                        Deger: ${data.deger}<br>
                        Yedek: ${data.yedek}
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `<strong>✗ ${data.message}</strong>`;
                }
                resultDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Hata oluştu: ' + error);
            });
        }
    </script>

    


</body>
</html>