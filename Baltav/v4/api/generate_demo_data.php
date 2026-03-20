<?php
// generate_demo_data.php - Demo Veritabanına Sahte Veri Girişi

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/logger.php';

// Bu script sadece komut satırından çalıştırılmalı veya yetkili bir arayüzden çağrılmalı.
// Doğrudan tarayıcıdan erişimi engellemek için basic bir kontrol.
if (php_sapi_name() !== 'cli' && !isset($_GET['debug_mode'])) {
    die('Erişim reddedildi.');
}

// Hedef veritabanı (Demo için) - Normalde db.php'den gelmeli ama override edelim
$demo_db_name = 'demo_silosense'; 

try {
    // Bağlantı nesnesi oluştururken demo veritabanını belirt
    $db = new PDO("mysql:host=localhost;dbname={$demo_db_name};charset=utf8mb4", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "{$demo_db_name} veritabanına bağlanıldı. Demo verileri oluşturuluyor...\n";
    log_action('INFO', 'Demo veri oluşturma scripti başlatıldı.', ['veritabani' => $demo_db_name]);

    // Önceki tüm demo verilerini temizle (isteğe bağlı, tekrar çalıştırılabilir olması için)
    $db->exec("DELETE FROM `ariza_gecmisi`;");
    $db->exec("DELETE FROM `cihaz_paketleri`;");
    $db->exec("DELETE FROM `cihaz_limitleri`;");
    $db->exec("DELETE FROM `cihaz_ayar`;");
    $db->exec("DELETE FROM `cihazlar`;");
    $db->exec("DELETE FROM `bayi_entegre_iliski`;");
    $db->exec("DELETE FROM `entegreler`;");
    $db->exec("DELETE FROM `bayiler`;");
    $db->exec("DELETE FROM `kullanicilar` WHERE id < 9999;"); // Mehmet'i silme

    // 1. Bayi: Baltav
    $db->exec("INSERT INTO `bayiler` (`id`, `unvan`, `yetkili`, `tel`, `created_at`, `updated_at`) VALUES (1, 'Baltav A.Ş.', 'Baltav Yetkilisi', '5551112233', NOW(), NOW());");
    echo "Baltav bayisi eklendi.\n";

    // 2. Entegre: Bupiliç
    $db->exec("INSERT INTO `entegreler` (`id`, `unvan`, `yetkili`, `tel`, `email`, `created_at`, `updated_at`) VALUES (1, 'Bupiliç A.Ş.', 'Bupiliç Yetkilisi', '5554445566', 'bupilic@example.com', NOW(), NOW());");
    echo "Bupiliç entegresi eklendi.\n";

    // Baltav ile Bupiliç ilişkisi
    $db->exec("INSERT INTO `bayi_entegre_iliski` (`bayi_id`, `entegre_id`) VALUES (1, 1);");
    echo "Baltav-Bupiliç ilişkisi kuruldu.\n";

    // Kullanıcılar (Şifreler düz metin olarak, daha sonra hashlenecek)
    // Mehmet zaten login.php'de özel olarak yönetiliyor.
    $db->exec("INSERT INTO `kullanicilar` (`id`, `ad_soyad`, `eposta`, `sifre_hash`, `rol`, `bagli_id`) VALUES
        (1, 'Baltav Admin', 'baltav@test.com', '123456', 'baltav', 1),
        (2, 'Bupiliç Temsilci', 'bupilic@test.com', '123456', 'entegre', 1),
        (3, 'Kümes 1 Sahibi', 'kumes1@test.com', '123456', 'kumes', NULL),
        (4, 'Kümes 2 Sahibi', 'kumes2@test.com', '123456', 'kumes', NULL);");
    echo "Demo kullanıcıları eklendi.\n";

    // Cihazlar
    $cihazlar_data = [
        ['SILO-XYZ001', 'Kümes 1 - Silo A', 1, 1, 39.65, 27.88, 3], // Kümes 1 - Bupiliç
        ['SILO-XYZ002', 'Kümes 1 - Silo B', 1, 1, 39.651, 27.881, 3], // Kümes 1 - Bupiliç
        ['SILO-XYZ003', 'Kümes 2 - Silo A', 1, 1, 39.645, 27.885, 4], // Kümes 2 - Bupiliç
        ['SILO-XYZ004', 'Kümes 2 - Silo B', 1, 1, 39.646, 27.886, 4]  // Kümes 2 - Bupiliç
    ];
    foreach ($cihazlar_data as $c) {
        $db->exec("INSERT INTO `cihazlar` (`cihaz_kodu`, `cihaz_adi`, `sahip_bayi_id`, `aktif_entegre_id`, `lat`, `lon`, `bagli_kullanici_id`) VALUES ('{$c[0]}', '{$c[1]}', 1, {$c[2]}, {$c[3]}, {$c[4]}, {$c[5]});");
    }
    echo "Demo cihazlar eklendi.\n";
    
    // `bagli_kullanici_id` sütununu `cihazlar` tablosuna ekledik. `kullanicilar` tablosundaki `id` ile eşleşecek.
    // `cihazlar` tablosuna `bagli_kullanici_id` sütunu ekliyoruz.
    // Bu sütun, bir cihazın doğrudan hangi kümes kullanıcısına ait olduğunu belirtecek.
    $db->exec("ALTER TABLE `cihazlar` ADD COLUMN `bagli_kullanici_id` INT(11) DEFAULT NULL AFTER `aktif_entegre_id`;");
    $db->exec("UPDATE `cihazlar` SET `bagli_kullanici_id` = 3 WHERE `cihaz_kodu` IN ('SILO-XYZ001', 'SILO-XYZ002');");
    $db->exec("UPDATE `cihazlar` SET `bagli_kullanici_id` = 4 WHERE `cihaz_kodu` IN ('SILO-XYZ003', 'SILO-XYZ004');");
    
    // Cihaz Ayarları
    $db->exec("INSERT INTO `cihaz_ayar` (`cihaz_kimligi`, `bdrate`, `stopbit`, `slave_id1`, `slave_id2`, `silosayısı`, `ayar_ok`, `bit`, `abcd`, `aciklama`) VALUES
        ('SILO-XYZ001', 9600, 8, 1, 0, 1, 1, 1, 0, ''),
        ('SILO-XYZ002', 9600, 8, 1, 0, 1, 1, 1, 0, ''),
        ('SILO-XYZ003', 9600, 8, 1, 0, 1, 1, 1, 0, ''),
        ('SILO-XYZ004', 9600, 8, 1, 0, 1, 1, 1, 0, '');");
    echo "Demo cihaz ayarları eklendi.\n";

    // Cihaz Limitleri
    $db->exec("INSERT INTO `cihaz_limitleri` (`cihaz_kimligi`, `min_agirlik`, `max_agirlik`, `alarm_aktif`, `guncelleme_zamani`) VALUES
        ('SILO-XYZ001', 1000, 10000, 1, NOW()),
        ('SILO-XYZ002', 500, 8000, 1, NOW()),
        ('SILO-XYZ003', 1200, 12000, 1, NOW()),
        ('SILO-XYZ004', 700, 9000, 1, NOW());");
    echo "Demo cihaz limitleri eklendi.\n";

    // Cihaz Paketleri (Son 1-2 saatin verileri gibi, rastgele)
    for ($i = 0; $i < 4; $i++) { // Her cihaz için birkaç veri
        $cihaz_kodu = $cihazlar_data[$i][0];
        for ($j = 0; $j < 10; $j++) {
            $agirlik = round(mt_rand(100, 10000) / 100, 2); // Rastgele ağırlık
            $zaman = date('Y-m-d H:i:s', strtotime("- " . (mt_rand(1, 120)) . " minutes")); // Son 2 saat içinde rastgele zaman
            $db->exec("INSERT INTO `cihaz_paketleri` (`cihaz_kimligi`, `paket_no`, `agirlik_degeri`, `darbeSayisi`, `stabil_mi`, `cihaz_versiyonu`, `calisma_suresi_saniye`, `rs485_hata_sayisi`, `yazilim_surumu`, `ip_adresi`, `alinan_zaman`) VALUES ('{$cihaz_kodu}', {$j}, {$agirlik}, 0, 1, 1, 0, 0, 'v1.0', '127.0.0.1', '{$zaman}');");
        }
    }
    echo "Demo cihaz paketleri eklendi.\n";

    echo "Demo veri oluşturma tamamlandı.\n";
    log_action('INFO', 'Demo veri oluşturma scripti başarıyla tamamlandı.', ['veritabani' => $demo_db_name]);

} catch (PDOException $e) {
    echo "Veritabanı hatası: " . $e->getMessage() . "\n";
    log_action('ERROR', 'Demo veri oluşturma sırasında veritabanı hatası.', ['hata' => $e->getMessage(), 'veritabani' => $demo_db_name]);
} catch (Exception $e) {
    echo "Genel hata: " . $e->getMessage() . "\n";
    log_action('ERROR', 'Demo veri oluşturma sırasında genel hata.', ['hata' => $e->getMessage(), 'veritabani' => $demo_db_name]);
}

?>