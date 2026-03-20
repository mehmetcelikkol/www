<?php
// SiloSense V3 - Loglama Fonksiyonu

function log_action($level, $action, $details = []) {
    $log_file = __DIR__ . '/../logs/activity.log';
    
    // Kullanıcı bilgileri (eğer oturum açıksa)
    $kullanici_id = $_SESSION['kullanici_id'] ?? 'Guest';
    $kullanici_ad = $_SESSION['ad_soyad'] ?? 'Misafir';
    $kullanici_rol = $_SESSION['rol'] ?? 'Yok';

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = sprintf("[%s] [%s] [KID:%s | KADI:%s | ROL:%s] %s %s\n",
        $timestamp,
        strtoupper($level),
        $kullanici_id,
        $kullanici_ad,
        $kullanici_rol,
        $action,
        json_encode($details, JSON_UNESCAPED_UNICODE)
    );

    // Log dosyasının varlığını kontrol et ve gerekirse oluştur
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "", FILE_APPEND | LOCK_EX);
    }

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Örnek Kullanım:
// log_action('INFO', 'Kullanıcı anasayfayı görüntüledi', ['sayfa' => 'index.php']);
// log_action('USER_ACTION', 'Cihaz eklendi', ['cihaz_kodu' => 'SILO-XYZ', 'admin_id' => 1]);
// log_action('ERROR', 'Veritabanı bağlantı hatası', ['hata_mesaji' => $e->getMessage()]);

?>