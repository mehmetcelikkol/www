-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 23 Şub 2026, 22:20:41
-- Sunucu sürümü: 8.3.0
-- PHP Sürümü: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `silosense`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ariza_gecmisi`
--

DROP TABLE IF EXISTS `ariza_gecmisi`;
CREATE TABLE IF NOT EXISTS `ariza_gecmisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cihaz_id` int NOT NULL,
  `ariza_tipi` enum('CIHAZ_RESET','HABERLESME_HATASI','BAGLANTI_KOPTU','ASIRI_YUK') CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `aciklama` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ariza_cihaz` (`cihaz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci COMMENT='Kritik hata ve uyarı kayıtları';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bayiler`
--

DROP TABLE IF EXISTS `bayiler`;
CREATE TABLE IF NOT EXISTS `bayiler` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `unvan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yetkili` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bayi_entegre_iliski`
--

DROP TABLE IF EXISTS `bayi_entegre_iliski`;
CREATE TABLE IF NOT EXISTS `bayi_entegre_iliski` (
  `bayi_id` int UNSIGNED NOT NULL,
  `entegre_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`bayi_id`,`entegre_id`),
  KEY `fk_bayi_iliski_entegre` (`entegre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihazlar`
--

DROP TABLE IF EXISTS `cihazlar`;
CREATE TABLE IF NOT EXISTS `cihazlar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sahip_bayi_id` int UNSIGNED DEFAULT NULL,
  `aktif_entegre_id` int UNSIGNED DEFAULT NULL,
  `cihaz_kodu` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `cihaz_adi` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `konum` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `yazilim_surumu` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT 'v1.0',
  `aktif_mi` tinyint(1) DEFAULT '1',
  `kayit_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cihaz_kodu` (`cihaz_kodu`),
  KEY `idx_cihaz_kodu` (`cihaz_kodu`),
  KEY `fk_cihazlar_bayi` (`sahip_bayi_id`),
  KEY `fk_cihazlar_entegre` (`aktif_entegre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci COMMENT='Sahadaki Silo-Sense cihaz listesi';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_ayar`
--

DROP TABLE IF EXISTS `cihaz_ayar`;
CREATE TABLE IF NOT EXISTS `cihaz_ayar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cihaz_kimligi` varchar(50) NOT NULL,
  `bdrate` int NOT NULL,
  `stopbit` int NOT NULL,
  `slave_id1` int NOT NULL,
  `slave_id2` int NOT NULL,
  `silosayısı` int NOT NULL,
  `ayar_ok` tinyint(1) NOT NULL,
  `bit` int NOT NULL,
  `abcd` int NOT NULL,
  `aciklama` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_limitleri`
--

DROP TABLE IF EXISTS `cihaz_limitleri`;
CREATE TABLE IF NOT EXISTS `cihaz_limitleri` (
  `cihaz_kimligi` varchar(50) NOT NULL,
  `min_agirlik` float NOT NULL,
  `max_agirlik` float NOT NULL,
  `alarm_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `guncelleme_zamani` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cihaz_kimligi`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_paketleri`
--

DROP TABLE IF EXISTS `cihaz_paketleri`;
CREATE TABLE IF NOT EXISTS `cihaz_paketleri` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cihaz_kimligi` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `paket_no` int UNSIGNED NOT NULL,
  `agirlik_degeri` float NOT NULL,
  `darbeSayisi` int NOT NULL,
  `stabil_mi` tinyint(1) NOT NULL,
  `cihaz_versiyonu` int DEFAULT NULL,
  `calisma_suresi_saniye` int UNSIGNED NOT NULL,
  `rs485_hata_sayisi` int UNSIGNED NOT NULL,
  `yazilim_surumu` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `ip_adresi` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `alinan_zaman` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cihaz_zaman` (`cihaz_kimligi`,`alinan_zaman`),
  KEY `idx_cihaz_paket` (`cihaz_kimligi`,`paket_no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_son_durum`
--

DROP TABLE IF EXISTS `cihaz_son_durum`;
CREATE TABLE IF NOT EXISTS `cihaz_son_durum` (
  `cihaz_kimligi` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `paket_no` int UNSIGNED DEFAULT NULL,
  `agirlik_degeri` float DEFAULT NULL,
  `stabil_mi` tinyint(1) DEFAULT NULL,
  `cihaz_versiyonu` int DEFAULT NULL,
  `calisma_suresi_saniye` int UNSIGNED DEFAULT NULL,
  `rs485_hata_sayisi` int UNSIGNED DEFAULT NULL,
  `yazilim_surumu` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `son_gorulme` datetime DEFAULT NULL,
  PRIMARY KEY (`cihaz_kimligi`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `device_packets`
--

DROP TABLE IF EXISTS `device_packets`;
CREATE TABLE IF NOT EXISTS `device_packets` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `device_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `packet_id` int UNSIGNED NOT NULL,
  `weight_val` float NOT NULL,
  `is_stable` tinyint(1) NOT NULL,
  `uptime_sec` int UNSIGNED NOT NULL,
  `rs485_err` int UNSIGNED NOT NULL,
  `fw_ver` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL,
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_device_time` (`device_id`,`received_at`),
  KEY `idx_device_packet` (`device_id`,`packet_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `entegre_firmalar`
--

DROP TABLE IF EXISTS `entegre_firmalar`;
CREATE TABLE IF NOT EXISTS `entegre_firmalar` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `unvan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eposta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sifre_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','entegre','bayi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bayi',
  `bagli_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eposta` (`eposta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `olcum_loglari_eski`
--

DROP TABLE IF EXISTS `olcum_loglari_eski`;
CREATE TABLE IF NOT EXISTS `olcum_loglari_eski` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cihaz_id` int NOT NULL,
  `paket_no` int UNSIGNED NOT NULL,
  `agirlik_kg` decimal(10,2) NOT NULL,
  `denge_durumu` tinyint(1) DEFAULT '1',
  `calisma_suresi_sn` int UNSIGNED DEFAULT '0',
  `hat_hata_sayisi` smallint UNSIGNED DEFAULT '0',
  `sunucu_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cihaz_zaman` (`cihaz_id`,`sunucu_zamani`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci COMMENT='Saniyeler bazında akan sensör verileri';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ziyaretler`
--

DROP TABLE IF EXISTS `site_ziyaretler`;
CREATE TABLE IF NOT EXISTS `site_ziyaretler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_adresi` varchar(100) DEFAULT NULL,
  `user_agent` text,
  `ziyaret_zamani` datetime DEFAULT NULL,
  `sayfa` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_adresi`),
  KEY `idx_zaman` (`ziyaret_zamani`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `test`
--

DROP TABLE IF EXISTS `test`;
CREATE TABLE IF NOT EXISTS `test` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deger` int NOT NULL,
  `tarih` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `yedek` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ariza_gecmisi`
--
ALTER TABLE `ariza_gecmisi`
  ADD CONSTRAINT `fk_ariza_cihaz` FOREIGN KEY (`cihaz_id`) REFERENCES `cihazlar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `bayi_entegre_iliski`
--
ALTER TABLE `bayi_entegre_iliski`
  ADD CONSTRAINT `fk_bayi_iliski_bayi` FOREIGN KEY (`bayi_id`) REFERENCES `bayiler` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bayi_iliski_entegre` FOREIGN KEY (`entegre_id`) REFERENCES `entegre_firmalar` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `cihazlar`
--
ALTER TABLE `cihazlar`
  ADD CONSTRAINT `fk_cihazlar_bayi` FOREIGN KEY (`sahip_bayi_id`) REFERENCES `bayiler` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cihazlar_entegre` FOREIGN KEY (`aktif_entegre_id`) REFERENCES `entegre_firmalar` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
