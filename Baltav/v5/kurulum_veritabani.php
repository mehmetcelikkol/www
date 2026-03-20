<?php

$sunucu = 'localhost';
$kullanici = 'root';
$sifre = '';

try {
    // MySQL'e bağlan
    $pdo = new PDO("mysql:host=$sunucu", $kullanici, $sifre);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Veritabanı oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `rmtproje_silosense_v5` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `rmtproje_silosense_v5`");

    // Tabloları temizle ve oluştur
    $sql = "
    DROP TABLE IF EXISTS `cihaz_paketleri`;
    DROP TABLE IF EXISTS `cihazlar`;
    DROP TABLE IF EXISTS `kullanicilar`;
    DROP TABLE IF EXISTS `kumesler`;
    DROP TABLE IF EXISTS `isletmeciler`;
    DROP TABLE IF EXISTS `entegreler`;
    
    CREATE TABLE `entegreler` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `unvan` varchar(255) NOT NULL,
      `yetkili` varchar(200) DEFAULT NULL,
      `telefon` varchar(50) DEFAULT NULL,
      `olusturma_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE `isletmeciler` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `unvan` varchar(255) NOT NULL,
      `yetkili` varchar(200) DEFAULT NULL,
      `telefon` varchar(50) DEFAULT NULL,
      `olusturma_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE `kumesler` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `isletmeci_id` int(11) NOT NULL,
      `entegre_id` int(11) DEFAULT NULL,
      `unvan` varchar(255) NOT NULL,
      `adres` varchar(255) DEFAULT NULL,
      `olusturma_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`isletmeci_id`) REFERENCES `isletmeciler`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`entegre_id`) REFERENCES `entegreler`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE `kullanicilar` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `kullanici_adi` varchar(50) NOT NULL,
      `sifre` varchar(255) NOT NULL,
      `rol` enum('superadmin','admin','entegre','isletmeci') NOT NULL,
      `entegre_id` int(11) DEFAULT NULL,
      `isletmeci_id` int(11) DEFAULT NULL,
      `olusturma_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`entegre_id`) REFERENCES `entegreler`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`isletmeci_id`) REFERENCES `isletmeciler`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE `cihazlar` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `kumes_id` int(11) DEFAULT NULL,
      `cihaz_kodu` varchar(32) NOT NULL,
      `cihaz_adi` varchar(100) DEFAULT NULL,
      `konum` varchar(100) DEFAULT NULL,
      `kapasite_kg` float DEFAULT 20000,
      `aktif_mi` tinyint(1) DEFAULT 1,
      `kayit_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`kumes_id`) REFERENCES `kumesler`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE `cihaz_paketleri` (
      `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `cihaz_kodu` varchar(32) NOT NULL,
      `paket_no` int(10) UNSIGNED NOT NULL,
      `agirlik_degeri` float NOT NULL,
      `alinan_zaman` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);

    // Sahte Veriler Ekle (Demo Veriler)
    $pdo->exec("INSERT INTO `entegreler` (`id`, `unvan`, `yetkili`, `telefon`) VALUES 
    (1, 'Güneş Tavukçuluk Entegre Tesisleri', 'Ahmet Yılmaz', '05551112233'),
    (2, 'Lezzet Piliç A.Ş.', 'Ayşe Demir', '05554445566')");

    $pdo->exec("INSERT INTO `isletmeciler` (`id`, `unvan`, `yetkili`, `telefon`) VALUES 
    (1, 'Marmara Çiftlikleri A.Ş.', 'Hasan Kaya', '05321112233'),
    (2, 'Ege Tarım İşletmesi', 'Ali Veli', '05331112233')");

    $pdo->exec("INSERT INTO `kumesler` (`id`, `isletmeci_id`, `entegre_id`, `unvan`, `adres`) VALUES 
    (1, 1, 1, 'Marmara 1 Nolu Kümes', 'Bandırma'),
    (2, 1, 2, 'Marmara 2 Nolu Kümes', 'Karacabey'),
    (3, 2, 2, 'Ege Ana Kümes', 'Manisa')");

    $pdo->exec("INSERT INTO `cihazlar` (`id`, `kumes_id`, `cihaz_kodu`, `cihaz_adi`, `konum`, `kapasite_kg`) VALUES 
    (1, 1, 'SILO-1001', '1. Silo (Tavuk Yemi)', 'Kuzey Cephe', 25000),
    (2, 1, 'SILO-1002', '2. Silo (Tavuk Yemi)', 'Güney Cephe', 25000),
    (3, 2, 'SILO-1003', 'Ana Silo', 'Ana Giriş', 30000),
    (4, 3, 'SILO-2001', 'Yem Silosu 1', 'Arka Cephe', 20000)");

    // Tüm şifreler: '123456'
    $sifre_hash = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO `kullanicilar` (`id`, `kullanici_adi`, `sifre`, `rol`, `entegre_id`, `isletmeci_id`) VALUES 
    (1, 'superadmin', ?, 'superadmin', NULL, NULL),
    (2, 'admin1', ?, 'admin', NULL, NULL),
    (3, 'entegre_gunes', ?, 'entegre', 1, NULL),
    (4, 'isletmeci_marmara', ?, 'isletmeci', NULL, 1)");
    $stmt->execute([$sifre_hash, $sifre_hash, $sifre_hash, $sifre_hash]);

    // Tüketim simülasyonu verileri: Son 48 saati saatlik tüketim ile dolduralım
    $paket_no = 1;
    foreach (['SILO-1001', 'SILO-1002', 'SILO-1003', 'SILO-2001'] as $cihaz_kodu) {
        $mevcut_agirlik = 20000;
        for ($i = 48; $i >= 0; $i--) {
            $dusus = rand(100, 400);
            $mevcut_agirlik -= $dusus;
            if ($mevcut_agirlik < 0) $mevcut_agirlik = 0;
            $zaman = date('Y-m-d H:i:s', strtotime("-" . $i . " hours"));
            $pdo->query("INSERT INTO `cihaz_paketleri` (`cihaz_kodu`, `paket_no`, `agirlik_degeri`, `alinan_zaman`) 
                         VALUES ('$cihaz_kodu', $paket_no, $mevcut_agirlik, '$zaman')");
            $paket_no++;
        }
    }

    echo "Kurulum Basarili! rmtproje_silosense_v5 (V2 Semali) olusturuldu.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
