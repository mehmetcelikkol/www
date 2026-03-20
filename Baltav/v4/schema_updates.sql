-- SiloSense IoT Platformu - Schema güncellemeleri
-- 1) Yeni tablolar

CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad` VARCHAR(200) NOT NULL,
  `eposta` VARCHAR(200) NOT NULL UNIQUE,
  `sifre_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin','entegre','bayi') NOT NULL DEFAULT 'bayi',
  `bagli_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bayiler` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `unvan` VARCHAR(255) NOT NULL,
  `yetkili` VARCHAR(200) DEFAULT NULL,
  `tel` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `entegre_firmalar` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `unvan` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bayi_entegre_iliski` (
  `bayi_id` INT UNSIGNED NOT NULL,
  `entegre_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`bayi_id`,`entegre_id`),
  CONSTRAINT `fk_bayi_iliski_bayi` FOREIGN KEY (`bayi_id`) REFERENCES `bayiler`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bayi_iliski_entegre` FOREIGN KEY (`entegre_id`) REFERENCES `entegre_firmalar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Mevcut `cihazlar` tablosuna sütun ekleme
ALTER TABLE `cihazlar`
  ADD COLUMN IF NOT EXISTS `sahip_bayi_id` INT UNSIGNED NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `aktif_entegre_id` INT UNSIGNED NULL AFTER `sahip_bayi_id`;

-- Opsiyonel: varsayilan indexler / foreign key'ler (varsa ilişkilendir)
ALTER TABLE `cihazlar`
  ADD CONSTRAINT IF NOT EXISTS `fk_cihazlar_bayi` FOREIGN KEY (`sahip_bayi_id`) REFERENCES `bayiler`(`id`) ON DELETE SET NULL,
  ADD CONSTRAINT IF NOT EXISTS `fk_cihazlar_entegre` FOREIGN KEY (`aktif_entegre_id`) REFERENCES `entegre_firmalar`(`id`) ON DELETE SET NULL;

-- NOT: MySQL versiyonuna göre "IF NOT EXISTS" ile ALTER TABLE ADD COLUMN desteği farklı olabilir.
-- Eğer sunucuda hata alırsanız, sütun ekleme satırlarını ayrı kontrol edip çalıştırınız.

-- 3) Örnek admin kullanıcı (sifre: değiştirin)
-- Parola örneği (GEÇİCİ): düz metin kullanılıyor. Daha sonra hash'e dönülmeli.

INSERT INTO `kullanicilar` (`ad_soyad`,`eposta`,`sifre_hash`,`rol`,`bagli_id`)
VALUES ('Sistem Yonetici','admin@ornek.local','sifre123', 'admin', NULL)
ON DUPLICATE KEY UPDATE `eposta`=`eposta`;
