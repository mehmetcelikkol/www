-- V3 Veritabanı Şema Güncelleme Script'i - Cihaz Konum Bilgileri
-- Tarih: 2026-03-04

-- `cihazlar` tablosuna `lat` (enlem) ve `lon` (boylam) sütunları ekleme
-- Harita özelliğinin çalışması için gereklidir.
ALTER TABLE `cihazlar`
ADD COLUMN `lat` DECIMAL(10, 8) DEFAULT NULL AFTER `konum`,
ADD COLUMN `lon` DECIMAL(11, 8) DEFAULT NULL AFTER `lat`;

-- Mevcut cihazlara örnek konum bilgisi ekleme (isteğe bağlı, demo için faydalı olabilir)
-- UPDATE `cihazlar` SET `lat` = 39.6484 + (RAND() * 0.1 - 0.05), `lon` = 27.8826 + (RAND() * 0.1 - 0.05) WHERE `lat` IS NULL;
