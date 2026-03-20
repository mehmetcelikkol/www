-- V3 Veritabanı Şema Güncelleme Script'i
-- Tarih: 2026-03-04

-- 1. `kullanicilar` tablosuna `rol` sütunu ekleme
-- `rol` sütunu, kullanıcının yetki seviyesini (admin, baltav, entegre, kumes) belirtecek.
ALTER TABLE `kullanicilar`
ADD COLUMN `rol` ENUM('admin', 'baltav', 'entegre', 'kumes') NOT NULL DEFAULT 'kumes' AFTER `eposta`;

-- 2. `kullanicilar` tablosuna `bagli_id` sütunu ekleme
-- `bagli_id` sütunu, kullanıcının hangi bayi/entegre/kümes ile ilişkili olduğunu tutacak.
-- Varsayılan olarak NULL olabilir.
ALTER TABLE `kullanicilar`
ADD COLUMN `bagli_id` INT(10) UNSIGNED DEFAULT NULL AFTER `rol`;

-- 3. `entegreler` tablosu oluşturma
-- Entegre firmalarının bilgilerini tutacak.
CREATE TABLE `entegreler` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `unvan` VARCHAR(255) NOT NULL,
  `yetkili` VARCHAR(200) DEFAULT NULL,
  `tel` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) UNIQUE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. `entegre_degisiklik_talepleri` tablosu oluşturma
-- Kümeslerden gelen entegre değişiklik taleplerini yönetecek.
CREATE TABLE `entegre_degisiklik_talepleri` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cihaz_id` INT(11) NOT NULL,
  `talep_eden_kullanici_id` INT(11) NOT NULL,
  `eski_entegre_id` INT(10) UNSIGNED DEFAULT NULL,
  `yeni_entegre_id` INT(10) UNSIGNED NOT NULL,
  `talep_durumu` ENUM('beklemede', 'onaylandi', 'reddedildi') NOT NULL DEFAULT 'beklemede',
  `talep_zamani` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `onay_zamani` TIMESTAMP NULL DEFAULT NULL,
  `onaylayan_kullanici_id` INT(11) DEFAULT NULL,
  `aciklama` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cihaz_id`) REFERENCES `cihazlar`(`id`),
  FOREIGN KEY (`talep_eden_kullanici_id`) REFERENCES `kullanicilar`(`id`),
  FOREIGN KEY (`eski_entegre_id`) REFERENCES `entegreler`(`id`),
  FOREIGN KEY (`yeni_entegre_id`) REFERENCES `entegreler`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mevcut cihazlar tablosundaki 'aktif_entegre_id' alanını 'entegreler' tablosuna bağlamak için FK ekleyelim
-- Önce mevcut NULL değerleri veya geçersiz değerleri temizlememiz veya güncellememiz gerekebilir.
-- Şimdilik doğrudan ekliyorum, sorun çıkarsa manuel düzeltme gerekebilir.
ALTER TABLE `cihazlar`
ADD CONSTRAINT `fk_aktif_entegre` FOREIGN KEY (`aktif_entegre_id`) REFERENCES `entegreler`(`id`);

-- Mevcut 'cihazlar' tablosundaki 'sahip_bayi_id' alanını 'bayiler' tablosuna bağlamak için FK ekleyelim
ALTER TABLE `cihazlar`
ADD CONSTRAINT `fk_sahip_bayi` FOREIGN KEY (`sahip_bayi_id`) REFERENCES `bayiler`(`id`);

-- `kullanicilar` tablosundaki `bagli_id` alanını `bayiler` ve `entegreler` tablosuna bağlamak için FK ekleyemeyiz doğrudan.
-- Bu ilişki uygulama seviyesinde yönetilecek. Yani rolüne göre ilgili ID'ye bakılacak.

-- Mehmet'in girişinin doğru rol ve bağlı_id ile çalışmasını sağlamak için varsayılan kullanıcıyı güncelle
-- Not: Bu 'mehmet' kullanıcısının zaten ID=9999 olarak login.php'de ayarlı olduğunu varsayar.
-- Eğer böyle bir kullanıcı yoksa bu komut hata verir veya yeni bir kullanıcı oluşturur.
-- Güvenli olması için manuel kontrol daha iyidir.
-- INSERT IGNORE INTO `kullanicilar` (`id`, `eposta`, `sifre_hash`, `ad_soyad`, `rol`, `bagli_id`) VALUES (9999, 'mehmet@rmt.com', '01200120', 'Mehmet (Süper Admin)', 'admin', NULL);
-- UPDATE `kullanicilar` SET `rol` = 'admin', `bagli_id` = NULL WHERE `id` = 9999 AND `eposta` = 'mehmet@rmt.com';

