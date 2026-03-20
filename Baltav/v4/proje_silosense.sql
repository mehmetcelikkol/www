-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 24 Şub 2026, 00:35:02
-- Sunucu sürümü: 5.7.44
-- PHP Sürümü: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `proje_silosense`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ariza_gecmisi`
--

CREATE TABLE `ariza_gecmisi` (
  `id` int(11) NOT NULL,
  `cihaz_id` int(11) NOT NULL,
  `ariza_tipi` enum('CIHAZ_RESET','HABERLESME_HATASI','BAGLANTI_KOPTU','ASIRI_YUK') COLLATE utf8_turkish_ci NOT NULL,
  `aciklama` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL,
  `olusturma_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci COMMENT='Kritik hata ve uyarı kayıtları';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihazlar`
--

CREATE TABLE `cihazlar` (
  `id` int(11) NOT NULL,
  `cihaz_kodu` varchar(32) COLLATE utf8_turkish_ci NOT NULL,
  `cihaz_adi` varchar(100) COLLATE utf8_turkish_ci DEFAULT NULL,
  `konum` varchar(100) COLLATE utf8_turkish_ci DEFAULT NULL,
  `yazilim_surumu` varchar(10) COLLATE utf8_turkish_ci DEFAULT 'v1.0',
  `aktif_mi` tinyint(1) DEFAULT '1',
  `kayit_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci COMMENT='Sahadaki Silo-Sense cihaz listesi';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_ayar`
--

CREATE TABLE `cihaz_ayar` (
  `id` int(11) NOT NULL,
  `cihaz_kimligi` varchar(50) NOT NULL,
  `bdrate` int(11) NOT NULL,
  `stopbit` int(1) NOT NULL,
  `slave_id1` int(3) NOT NULL,
  `slave_id2` int(3) NOT NULL,
  `silosayısı` int(1) NOT NULL,
  `ayar_ok` tinyint(1) NOT NULL,
  `bit` int(2) NOT NULL,
  `abcd` int(4) NOT NULL,
  `aciklama` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_limitleri`
--

CREATE TABLE `cihaz_limitleri` (
  `cihaz_kimligi` varchar(50) NOT NULL,
  `min_agirlik` float NOT NULL,
  `max_agirlik` float NOT NULL,
  `alarm_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `guncelleme_zamani` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_paketleri`
--

CREATE TABLE `cihaz_paketleri` (
  `id` bigint(20) NOT NULL,
  `cihaz_kimligi` varchar(50) COLLATE utf8_turkish_ci NOT NULL,
  `paket_no` int(10) UNSIGNED NOT NULL,
  `agirlik_degeri` float NOT NULL,
  `darbeSayisi` int(11) NOT NULL,
  `stabil_mi` tinyint(1) NOT NULL,
  `cihaz_versiyonu` int(11) DEFAULT NULL,
  `calisma_suresi_saniye` int(10) UNSIGNED NOT NULL,
  `rs485_hata_sayisi` int(10) UNSIGNED NOT NULL,
  `yazilim_surumu` varchar(20) COLLATE utf8_turkish_ci NOT NULL,
  `ip_adresi` varchar(45) COLLATE utf8_turkish_ci DEFAULT NULL,
  `alinan_zaman` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cihaz_son_durum`
--

CREATE TABLE `cihaz_son_durum` (
  `cihaz_kimligi` varchar(50) COLLATE utf8_turkish_ci NOT NULL,
  `paket_no` int(10) UNSIGNED DEFAULT NULL,
  `agirlik_degeri` float DEFAULT NULL,
  `stabil_mi` tinyint(1) DEFAULT NULL,
  `cihaz_versiyonu` int(11) DEFAULT NULL,
  `calisma_suresi_saniye` int(10) UNSIGNED DEFAULT NULL,
  `rs485_hata_sayisi` int(10) UNSIGNED DEFAULT NULL,
  `yazilim_surumu` varchar(20) COLLATE utf8_turkish_ci DEFAULT NULL,
  `son_gorulme` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `device_packets`
--

CREATE TABLE `device_packets` (
  `id` bigint(20) NOT NULL,
  `device_id` varchar(50) COLLATE utf8_turkish_ci NOT NULL,
  `packet_id` int(10) UNSIGNED NOT NULL,
  `weight_val` float NOT NULL,
  `is_stable` tinyint(1) NOT NULL,
  `uptime_sec` int(10) UNSIGNED NOT NULL,
  `rs485_err` int(10) UNSIGNED NOT NULL,
  `fw_ver` varchar(20) COLLATE utf8_turkish_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_turkish_ci DEFAULT NULL,
  `received_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `olcum_loglari_eski`
--

CREATE TABLE `olcum_loglari_eski` (
  `id` bigint(20) NOT NULL,
  `cihaz_id` int(11) NOT NULL,
  `paket_no` int(10) UNSIGNED NOT NULL,
  `agirlik_kg` decimal(10,2) NOT NULL,
  `denge_durumu` tinyint(1) DEFAULT '1',
  `calisma_suresi_sn` int(10) UNSIGNED DEFAULT '0',
  `hat_hata_sayisi` smallint(5) UNSIGNED DEFAULT '0',
  `sunucu_zamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci COMMENT='Saniyeler bazında akan sensör verileri';

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ziyaretler`
--

CREATE TABLE `site_ziyaretler` (
  `id` int(11) NOT NULL,
  `ip_adresi` varchar(100) DEFAULT NULL,
  `user_agent` text,
  `ziyaret_zamani` datetime DEFAULT NULL,
  `sayfa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `test`
--

CREATE TABLE `test` (
  `id` int(11) NOT NULL,
  `deger` int(11) NOT NULL,
  `tarih` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `yedek` varchar(3) COLLATE utf8_turkish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `ariza_gecmisi`
--
ALTER TABLE `ariza_gecmisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ariza_cihaz` (`cihaz_id`);

--
-- Tablo için indeksler `cihazlar`
--
ALTER TABLE `cihazlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cihaz_kodu` (`cihaz_kodu`),
  ADD KEY `idx_cihaz_kodu` (`cihaz_kodu`);

--
-- Tablo için indeksler `cihaz_ayar`
--
ALTER TABLE `cihaz_ayar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `cihaz_limitleri`
--
ALTER TABLE `cihaz_limitleri`
  ADD PRIMARY KEY (`cihaz_kimligi`);

--
-- Tablo için indeksler `cihaz_paketleri`
--
ALTER TABLE `cihaz_paketleri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cihaz_zaman` (`cihaz_kimligi`,`alinan_zaman`),
  ADD KEY `idx_cihaz_paket` (`cihaz_kimligi`,`paket_no`);

--
-- Tablo için indeksler `cihaz_son_durum`
--
ALTER TABLE `cihaz_son_durum`
  ADD PRIMARY KEY (`cihaz_kimligi`);

--
-- Tablo için indeksler `device_packets`
--
ALTER TABLE `device_packets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_time` (`device_id`,`received_at`),
  ADD KEY `idx_device_packet` (`device_id`,`packet_id`);

--
-- Tablo için indeksler `olcum_loglari_eski`
--
ALTER TABLE `olcum_loglari_eski`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cihaz_zaman` (`cihaz_id`,`sunucu_zamani`);

--
-- Tablo için indeksler `site_ziyaretler`
--
ALTER TABLE `site_ziyaretler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_adresi`),
  ADD KEY `idx_zaman` (`ziyaret_zamani`);

--
-- Tablo için indeksler `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `ariza_gecmisi`
--
ALTER TABLE `ariza_gecmisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `cihazlar`
--
ALTER TABLE `cihazlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `cihaz_ayar`
--
ALTER TABLE `cihaz_ayar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `cihaz_paketleri`
--
ALTER TABLE `cihaz_paketleri`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `device_packets`
--
ALTER TABLE `device_packets`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `olcum_loglari_eski`
--
ALTER TABLE `olcum_loglari_eski`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `site_ziyaretler`
--
ALTER TABLE `site_ziyaretler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `test`
--
ALTER TABLE `test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ariza_gecmisi`
--
ALTER TABLE `ariza_gecmisi`
  ADD CONSTRAINT `fk_ariza_cihaz` FOREIGN KEY (`cihaz_id`) REFERENCES `cihazlar` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
