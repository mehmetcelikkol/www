Bu dosya, Alarm Sistemi için gerekli SQL şemasını içerir.

1) Kurallar ve e-posta alıcıları (zaten mevcut varsayılır):

```
CREATE TABLE IF NOT EXISTS `alarmlar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cihaz_id` INT NOT NULL,
  `cihaz_adres_id` INT NOT NULL,
  `kural_turu` ENUM('esik','oran') NOT NULL,
  `esik_min` DOUBLE NULL,
  `esik_max` DOUBLE NULL,
  `oran_pct` DOUBLE NULL,
  `yon` ENUM('herikisi','yukari','asagi') NOT NULL DEFAULT 'herikisi',
  `herkes_gorsun` TINYINT(1) NOT NULL DEFAULT 1,
  `olusturan_id` INT NOT NULL,
  `olusturma_zamani` DATETIME NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `aciklama` VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS `alarm_epostalar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alarm_id` INT NOT NULL,
  `eposta` VARCHAR(255) NOT NULL,
  INDEX (`alarm_id`)
);

CREATE TABLE IF NOT EXISTS `eposta_rehberi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `eposta` VARCHAR(255) UNIQUE NOT NULL,
  `etiket` VARCHAR(255) NULL,
  `olusturan_id` INT NULL,
  `olusturma_zamani` DATETIME NOT NULL
);
```

2) Alarm geçmişi tablosu (açık/kapanış takip):

```
CREATE TABLE IF NOT EXISTS `alarm_gecmisi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alarm_id` INT NOT NULL,
  `cihaz_id` INT NOT NULL,
  `cihaz_adres_id` INT NOT NULL,
  `acildi` DATETIME NOT NULL,
  `kapandi` DATETIME NULL,
  `neden` VARCHAR(255) NULL,
  `kapanis_nedeni` VARCHAR(255) NULL,
  `son_deger` DOUBLE NULL,
  INDEX(`alarm_id`),
  INDEX(`cihaz_id`,`cihaz_adres_id`)
);
```

Notlar:
- alarm_worker.php kuralları değerlendirir; tetiklenince alarm_gecmisi’ne kayıt açar, normale dönünce kapatır.
- E-posta gönderimi için PHP mail() kullanılır; WAMP’ta çalışmıyorsa enerji/logs/email.log dosyasına düşer.
- İsterseniz SMTP için PHPMailer entegre edilebilir.
