<?php
// v5 Mimarisi için Uyarlanmış Gelen Veri Yakalayıcı (olcum_post.php)
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Europe/Istanbul');

// Kurduğumuz yeni V5 veritabanı bağlantısı
require_once __DIR__ . '/sistem/baglanti.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["durum" => "HATA", "mesaj" => "JSON decode hatası"]);
    exit;
}

// ESP32 tarafındaki gömülü yazılım (C/C++) kodunda "sıfır" değişiklikle 
// çalışsın diye orijinalden gelen JSON iskeletini aynen alıyoruz.
$cihaz_kimligi         = $data['cihaz_kimligi'] ?? null;
$paket_no              = $data['paket_no'] ?? null;
$agirlik_degeri        = $data['agirlik_degeri'] ?? 0.0;

/* V5 Mimarisi İçin Zaten Bunlara Şimdilik İhtiyaç Yok (V5'te arayüzü çizilmedi):
$darbeSayisi = $data['darbeSayisi'] ?? 0;
$stabil_mi = $data['stabil_mi'] ?? 170; // Eskiden versiyon tutuluyordu
$calisma_suresi_saniye = $data['calisma_suresi_saniye'] ?? 0;
$rs485_hata_sayisi = $data['rs485_hata_sayisi'] ?? 0;
$yazilim_surumu = $data['yazilim_surumu'] ?? '1.0.0';
*/

if (!$cihaz_kimligi || $paket_no === null) {
    echo json_encode(["durum" => "HATA", "mesaj" => "Eksik alan (cihaz_kimligi veya paket_no)"]);
    exit;
}

try {
    // 1. Adım: Cihaz sisteme zaten kayıtlı mı kontrol edelim
    $q_cihaz = $db->prepare("SELECT id FROM cihazlar WHERE cihaz_kodu = ? LIMIT 1");
    $q_cihaz->execute([$cihaz_kimligi]);
    $kayitli_cihaz = $q_cihaz->fetch();

    if (!$kayitli_cihaz) {
        // Eğer kayıtlı değilse, kimliği belirsiz (Bağımsız) yeni bir cihaz olarak listeye ekleyelim.
        // İsmi ve Kapasitesi NULL (Boş) kalacak. Cihazlar panelindeki (??) Null eklentimiz sayesinde 
        // ekranda '0 kg' ve 'İsimsiz Cihaz' olarak görünecek. Sonra Yöneticiler atama yapabilecek.
        $q_cihaz_ekle = $db->prepare("INSERT INTO cihazlar (cihaz_kodu) VALUES (?)");
        $q_cihaz_ekle->execute([$cihaz_kimligi]);
        
        // Log tablosuna ilk kez gelen bu cihazın uyarısını sessizce düşelim
        sistem_log_yaz($db, 'Otomatik Silo Keşfi', "Sahadaki donanımdan ilk kez veri gönderen yeni cihaz ($cihaz_kimligi) yakalandı ve sisteme (Bağımsız olarak) kaydedildi.");
    }

    // 2. Adım: Paketi direkt arşiv (cihaz_paketleri) tablosuna kaydet.
    // Eski mimaride var olan 'cihaz_son_durum' tablosuna V5'te BİLİNÇLİ OLARAK yer vermedik/kaldırdık. 
    // Çünkü artık tüm sistem (Tüketim Tahmin Algoritması, ApexChart Grafikleri, Dashboard Listesi dahil) 
    // veriyi bu arşiv tablosundan "ORDER BY alinan_zaman DESC" ile anlık, ultra-hızlı biçimde üretiyor. 
    $q_paket = $db->prepare("
        INSERT INTO cihaz_paketleri 
        (cihaz_kodu, agirlik_degeri, alinan_zaman)
        VALUES (?, ?, NOW())
    ");
    $q_paket->execute([$cihaz_kimligi, $agirlik_degeri]);

    // Donanım paketi sorunsuz aldığını anlasın diye OK döndürelim
    echo json_encode([
        "durum" => "OK",
        "mesaj" => "V5 Sistemine veriler başarıyla yüklendi",
        "paket_no" => $paket_no
    ]);

} catch (PDOException $e) {
    echo json_encode(["durum" => "HATA", "mesaj" => "Sunucu / VT Hatası"]);
}
