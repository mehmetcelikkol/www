<?php
// migrate_add_cihaz_columns.php
// `cihazlar` tablosuna eksik sütunları güvenli şekilde ekler ve varsa foreign key eklemeye çalışır.
// Kullanım: tarayıcıda http://localhost/Baltav/v2/migrate_add_cihaz_columns.php

require_once __DIR__ . '/db.php';
$db = Database::getConnection();

// Mevcut veritabanı adını al
$schema = $db->query('SELECT DATABASE()')->fetchColumn();
if (!$schema) {
    echo "Veritabanı adı alınamadı.\n";
    exit;
}

echo "Çalıştırılıyor. Veritabanı: $schema\n\n";

function columnExists($db, $schema, $table, $col) {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :col LIMIT 1");
    $stmt->execute([':schema'=>$schema,':table'=>$table,':col'=>$col]);
    return (bool)$stmt->fetchColumn();
}

function tableExists($db, $schema, $table) {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table LIMIT 1");
    $stmt->execute([':schema'=>$schema,':table'=>$table]);
    return (bool)$stmt->fetchColumn();
}

$table = 'cihazlar';
if (!tableExists($db, $schema, $table)) {
    echo "Hata: tablo '$table' bulunamadı. Lütfen doğru veritabanında çalıştığınızdan emin olun.\n";
    exit;
}

$changes = [];

if (!columnExists($db,$schema,$table,'sahip_bayi_id')) {
    try {
        $db->exec("ALTER TABLE `$table` ADD COLUMN `sahip_bayi_id` INT UNSIGNED NULL AFTER `id`");
        $changes[] = "sahip_bayi_id eklendi";
    } catch (Exception $e) {
        echo "sahip_bayi_id eklenemedi: " . $e->getMessage() . "\n";
    }
} else { $changes[] = "sahip_bayi_id zaten mevcut"; }

if (!columnExists($db,$schema,$table,'aktif_entegre_id')) {
    try {
        $db->exec("ALTER TABLE `$table` ADD COLUMN `aktif_entegre_id` INT UNSIGNED NULL AFTER `sahip_bayi_id`");
        $changes[] = "aktif_entegre_id eklendi";
    } catch (Exception $e) {
        echo "aktif_entegre_id eklenemedi: " . $e->getMessage() . "\n";
    }
} else { $changes[] = "aktif_entegre_id zaten mevcut"; }

// Foreign key ekleme (opsiyonel) - yalnızca hedef tablolar varsa ve ilgili FK yoksa
function fkExists($db, $schema, $table, $column) {
    $stmt = $db->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :col AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
    $stmt->execute([':schema'=>$schema,':table'=>$table,':col'=>$column]);
    return (bool)$stmt->fetchColumn();
}

// bayi fk
if (tableExists($db,$schema,'bayiler') && !fkExists($db,$schema,$table,'sahip_bayi_id')) {
    try {
        $db->exec("ALTER TABLE `$table` ADD CONSTRAINT `fk_cihazlar_bayi` FOREIGN KEY (`sahip_bayi_id`) REFERENCES `bayiler`(`id`) ON DELETE SET NULL");
        $changes[] = "fk_cihazlar_bayi eklendi";
    } catch (Exception $e) {
        echo "fk_cihazlar_bayi eklenemedi: " . $e->getMessage() . "\n";
    }
} else {
    $changes[] = tableExists($db,$schema,'bayiler') ? 'fk_cihazlar_bayi zaten var veya atlandı' : 'bayiler tablosu yok; fk atlanıldı';
}

// entegre fk
if (tableExists($db,$schema,'entegre_firmalar') && !fkExists($db,$schema,$table,'aktif_entegre_id')) {
    try {
        $db->exec("ALTER TABLE `$table` ADD CONSTRAINT `fk_cihazlar_entegre` FOREIGN KEY (`aktif_entegre_id`) REFERENCES `entegre_firmalar`(`id`) ON DELETE SET NULL");
        $changes[] = "fk_cihazlar_entegre eklendi";
    } catch (Exception $e) {
        echo "fk_cihazlar_entegre eklenemedi: " . $e->getMessage() . "\n";
    }
} else {
    $changes[] = tableExists($db,$schema,'entegre_firmalar') ? 'fk_cihazlar_entegre zaten var veya atlandı' : 'entegre_firmalar tablosu yok; fk atlanıldı';
}

// Rapor
echo "İşlem tamamlandı. Özet:\n";
foreach($changes as $c) echo " - $c\n";

?>