<?php
require 'sistem/baglanti.php';
$db->exec('ALTER TABLE kullanicilar ADD COLUMN parent_id INT DEFAULT NULL;');
echo "OK";
