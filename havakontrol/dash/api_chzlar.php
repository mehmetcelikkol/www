<?php
require_once 'conn.php'; // Veritabanı bağlantısını dahil et

session_start();
header('Content-Type: application/json');

// Kullanıcının giriş yapıp yapmadığını kontrol et
if (!isset($_SESSION['mail'])) {
    http_response_code(401); // Yetkisiz erişim
    echo json_encode([
        'success' => false,
        'message' => 'Yetkisiz erişim. Lütfen giriş yapın.'
    ]);
    exit;
} 


// API isteği kontrolü
if (isset($_GET['api']) && $_GET['api'] == 'true') {
    header('Content-Type: application/json');

    // Örnek bir kullanıcı e-posta adresi (test için hardcoded)
    $email = $_SESSION['mail'];

    // E-posta adresine göre cari_id'yi almak için sorgu
    $sql = "SELECT id FROM cari WHERE mail = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Cari ID sorgusu hazırlanamadı."]);
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $cari_id = null;
    if ($row = $result->fetch_assoc()) {
        $cari_id = $row['id'];
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Cari ID bulunamadı."]);
        exit();
    }
    $stmt->close();

    // Cihaz bilgilerini ve son veri kayıtlarını almak için sorgu
    $sql = "
    SELECT c.id, c.serino, c.konum, 
    COALESCE(v.kayit_tarihi, 'Veri Yok') as kayit_tarihi,
    COALESCE(v.temp, 'Veri Yok') as temp,
    COALESCE(v.hum, 'Veri Yok') as hum
    FROM cihazlar c
    LEFT JOIN (
        SELECT serino, MAX(kayit_tarihi) as max_kayit_tarihi
        FROM veriler
        GROUP BY serino
    ) AS latest_ver ON c.serino = latest_ver.serino
    LEFT JOIN veriler v ON latest_ver.serino = v.serino AND latest_ver.max_kayit_tarihi = v.kayit_tarihi
    WHERE c.firmaid = ?
    ";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Veri sorgusu hazırlanamadı."]);
        exit();
    }

    $stmt->bind_param("i", $cari_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    echo json_encode(["success" => true, "data" => $data]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cihazlar ve Son Veriler</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Cihazlar ve Son Veriler</h1>
    <table id="cihazlarTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Serino</th>
                <th>Konum</th>
                <th>Kayıt Tarihi</th>
                <th>Sıcaklık</th>
                <th>Nem</th>
            </tr>
        </thead>
        <tbody>
            <!-- Veriler buraya yüklenecek -->
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            // API'den verileri çek
            $.ajax({
                url: 'chzlar_api.php?api=true', // API endpoint
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const tbody = $('#cihazlarTable tbody');
                        tbody.empty(); // Tablonun içeriğini temizle

                        // Gelen verileri tabloya ekle
                        data.forEach(function(cihaz) {
                            const row = `
                                <tr>
                                    <td>${cihaz.id}</td>
                                    <td>${cihaz.serino}</td>
                                    <td>${cihaz.konum}</td>
                                    <td>${cihaz.kayit_tarihi}</td>
                                    <td>${cihaz.temp}</td>
                                    <td>${cihaz.hum}</td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    } else {
                        alert('Veri alınamadı: ' + response.message);
                    }
                },
                error: function() {
                    alert('API ile bağlantı kurulamadı.');
                }
            });
        });
    </script>
</body>
</html>
