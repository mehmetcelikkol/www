<?php
require "db.php";

$selected_device = $_GET['cihaz'] ?? null;

if (!$selected_device) {
    die("Cihaz seçilmedi!");
}

// Dosya adını belirleyelim (Cihaz_ID_Tarih.csv)
$filename = "SiloSense_" . $selected_device . "_" . date('Y-m-d_H-i') . ".csv";

// Tarayıcıya dosyanın indirileceğini bildirelim
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Dosya çıktısını oluştur (PHP çıktı akışını kullanıyoruz)
$output = fopen('php://output', 'w');

// UTF-8 BOM ekleyelim (Excel'in Türkçe karakterleri doğru görmesi için şart)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Sütun başlıklarını yazalım
fputcsv($output, ['Tarih', 'Cihaz Kimliği', 'Ağırlık (kg)', 'Stabil Durumu', 'Paket No', 'RS485 Hata', 'Çalışma Süresi (sn)', 'Yazılım Sürümü']);

// Veritabanından TÜM verileri çekelim (Limit koymuyoruz)
$stmt = $db->prepare("
    SELECT 
        alinan_zaman, 
        cihaz_kimligi, 
        agirlik_degeri, 
        CASE WHEN stabil_mi = 1 THEN 'Evet' ELSE 'Hayır' END as stabil,
        paket_no, 
        rs485_hata_sayisi, 
        calisma_suresi_saniye, 
        yazilim_surumu 
    FROM cihaz_paketleri 
    WHERE cihaz_kimligi = ? 
    ORDER BY alinan_zaman DESC
");
$stmt->bind_param("s", $selected_device);
$stmt->execute();
$result = $stmt->get_result();

// Verileri satır satır dosyaya yazalım
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>