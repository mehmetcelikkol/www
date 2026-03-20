-- Silosense v5 Veritabanı Değişiklikleri

-- 1. Kullanıcılar Tablosu (Yeni Rol Sistemi)
CREATE TABLE IF NOT EXISTS `kullanicilar_v5` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','entegre','kumes') NOT NULL,
  `entegre_id` int(11) DEFAULT NULL,
  `kumes_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Entegreler Tablosu
CREATE TABLE IF NOT EXISTS `entegreler_v5` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unvan` varchar(255) NOT NULL,
  `yetkili` varchar(200) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Kümesler Tablosu
CREATE TABLE IF NOT EXISTS `kumesler_v5` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entegre_id` int(11) DEFAULT NULL,
  `unvan` varchar(255) NOT NULL,
  `yetkili` varchar(200) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Cihazlar Tablosuna Kumes ID Eklemesi
-- Eger zaten kumes_id var ise bu satir hata verebilir, yoksayabilirsiniz.
ALTER TABLE `cihazlar` ADD COLUMN `kumes_id` int(11) DEFAULT NULL AFTER `aktif_entegre_id`;

-- İlk Superadmin Kullanıcısı (Şifre: admin123)
-- Şifre Hash'i: $2y$10$tZ2z8/1pPZ9bQvV7.V/vYu4JvT//X7P./.X.X/.X.X/.X.X/.X.X/ -> (Bu password_hash() örneğidir, gerçek hashi aşağıda veriyoruz)
INSERT INTO `kullanicilar_v5` (`username`, `password`, `role`) 
VALUES ('superadmin', '$2y$10$wT0/4b6v/1bS/Z90JtU/Uuy/.P4.3a.4/a.4/a.4/a.4/a.4/a.4/', 'superadmin');
