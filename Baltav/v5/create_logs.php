<?php
require_once 'sistem/baglanti.php';
$sql = "CREATE TABLE IF NOT EXISTS `sistem_loglari` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `kullanici_id` int(11) DEFAULT NULL,
    `kullanici_adi` varchar(50) DEFAULT NULL,
    `rol` varchar(50) DEFAULT NULL,
    `entegre_id` int(11) DEFAULT NULL,
    `isletmeci_id` int(11) DEFAULT NULL,
    `islem_tipi` varchar(100) NOT NULL,
    `aciklama` text NOT NULL,
    `tarih` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$db->exec($sql);
echo "Logs table created.";
