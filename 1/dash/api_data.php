<?php
require_once 'conn.php';

// API Key doğrulama
if (!isset($_GET['api_key'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "API Key gerekli."]);
    exit();
}

$api_key = $_GET['api_key'];

// API Key'e göre kullanıcıyı bul
$sql = "
SELECT cari.id AS user_id, cari.mail 
FROM api_keys 
JOIN cari ON api_keys.user_id = cari.id 
WHERE api_keys.api_key = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
    exit();
}

$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "message" => "Geçersiz API Key."]);
    exit();
}

$user = $result->fetch_assoc();
$user_id = $user['user_id']; // Kullanıcı kimliği
$stmt->close();

// Cihaz bilgilerini ve son veri kayıtlarını almak için sorgu
$sql = "
SELECT c.id, c.serino, c.konum, 
COALESCE(v.kayit_tarihi, 'Veri Yok') AS kayit_tarihi,
COALESCE(v.temp, 'Veri Yok') AS temp,
COALESCE(v.hum, 'Veri Yok') AS hum
FROM cihazlar c
LEFT JOIN (
    SELECT serino, MAX(kayit_tarihi) AS max_kayit_tarihi
    FROM veriler
    GROUP BY serino
) AS latest_ver ON c.serino = latest_ver.serino
LEFT JOIN veriler v ON latest_ver.serino = v.serino AND latest_ver.max_kayit_tarihi = v.kayit_tarihi
WHERE c.firmaid = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Veri sorgusu hazırlanamadı."]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $record = null;
    if ($row['kayit_tarihi'] !== 'Veri Yok') {
        $record = [
            "timestamp" => date('c', strtotime($row['kayit_tarihi'])),
            "temperature" => [
                "value" => (float) $row['temp'],
                "unit" => "°C"
            ],
            "humidity" => [
                "value" => (float) $row['hum'],
                "unit" => "%"
            ]
        ];
    }

    $data[] = [
        "device_id" => (int) $row['id'],
        "serial_number" => $row['serino'],
        "location" => $row['konum'],
        "last_record" => $record
    ];
}

$response = [
    "success" => true,
    "metadata" => [
        "generated_at" => date('c'),
        "device_count" => count($data)
    ],
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

?>
