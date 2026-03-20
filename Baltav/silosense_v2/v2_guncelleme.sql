-- SiloSense V2 SaaS Güncellemesi
-- Mevcut veritabanı üzerine eklenecek tablolar

SET NAMES utf8mb4;

-- 1. Yetki ve Kullanıcılar
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(100) NOT NULL,
  `eposta` varchar(100) NOT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `rol` enum('admin','entegre','bayi') NOT NULL DEFAULT 'bayi',
  `bagli_kurum_id` int(11) DEFAULT 0 COMMENT 'Bayi ID veya Entegre ID',
  `son_giris` datetime DEFAULT NULL,
  `olusturma_tarihi` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eposta` (`eposta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan Admin (Şifre: 123456)
INSERT INTO `kullanicilar` (`ad_soyad`, `eposta`, `sifre_hash`, `rol`, `bagli_kurum_id`) VALUES
('Süper Admin', 'admin@rmt.com', '$2y$10$YourHashHere...', 'admin', 0); 
-- Not: Gerçek kullanımda şifreyi PHP password_hash() ile oluşturun.

-- 2. Kurumsal Yapı
CREATE TABLE IF NOT EXISTS `entegre_firmalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unvan` varchar(150) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `yetkili_kisi` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS `bayiler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unvan` varchar(150) NOT NULL,
  `sehir` varchar(50) DEFAULT NULL,
  `yetkili_kisi` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 3. İlişkiler (Hangi Bayi Hangi Entegreye Bağlı?)
CREATE TABLE IF NOT EXISTS `bayi_entegre_iliski` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bayi_id` int(11) NOT NULL,
  `entegre_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bayi` (`bayi_id`),
  KEY `idx_entegre` (`entegre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 4. Cihaz Tablosunu Güncelleme
-- Eğer sütunlar yoksa ekle (Hata vermemesi için prosedür gerekebilir ama manuel ekleme daha güvenli)
-- ALTER TABLE `cihazlar` ADD COLUMN `sahip_bayi_id` INT(11) DEFAULT 0;
-- ALTER TABLE `cihazlar` ADD COLUMN `aktif_entegre_id` INT(11) DEFAULT 0;
-- ALTER TABLE `cihazlar` ADD COLUMN `silo_kapasitesi_kg` FLOAT DEFAULT 10000;
