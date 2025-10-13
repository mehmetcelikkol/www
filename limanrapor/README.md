# SCADA Rapor Sistemi

Bu proje, SCADA1 veritabanındaki verileri görüntülemek ve raporlamak için geliştirilmiş minimal ve responsive bir PHP web uygulamasıdır.

## Özellikler

- 📊 **Veritabanı Raporlama**: SCADA veritabanındaki tabloları görüntüleme
- 📅 **Tarih Filtreleme**: Başlangıç ve bitiş tarihleri ile veri filtreleme
- 📱 **Responsive Tasarım**: Mobil ve masaüstü cihazlarda uyumlu
- 📁 **Tablo Seçimi**: Farklı tabloları seçme ve görüntüleme
- 📤 **CSV Export**: Verileri CSV formatında indirme
- 🎨 **Minimal Arayüz**: Gereksiz kaynak kullanımı olmadan temiz tasarım

## Kurulum

### 1. WAMP Server Kurulumu
- WAMP Server'ı indirin ve kurun
- Apache ve MySQL servislerini başlatın

### 2. Proje Dosyalarını Yerleştirme
```bash
c:\wamp64\www\limanrapor\
```

### 3. Veritabanı Kurulumu
1. phpMyAdmin'e gidin (http://localhost/phpmyadmin)
2. `database_setup.sql` dosyasını çalıştırın
3. Bu dosya `scada1` veritabanını ve örnek tabloları oluşturacak

### 4. Yapılandırma
`config.php` dosyasındaki veritabanı ayarlarını kontrol edin:
```php
'host' => 'localhost',
'dbname' => 'scada1',
'username' => 'root',
'password' => '',
```

### 5. Kullanım
Tarayıcınızda şu adrese gidin:
```
http://localhost/limanrapor
```

## Dosya Yapısı

```
limanrapor/
├── index.php              # Ana uygulama dosyası
├── config.php             # Veritabanı yapılandırması
├── database_setup.sql     # Veritabanı kurulum dosyası
└── README.md              # Bu dosya
```

## Veritabanı Tabloları

### sensor_data
- Sensör verilerini saklar (sıcaklık, basınç, nem)
- Sensör durumu ve konum bilgileri

### alarm_logs
- Alarm kayıtlarını tutar
- Önem seviyesi ve onay durumu

### system_status
- Sistem durumu bilgileri
- CPU, bellek, disk kullanımı

## Kullanım

1. **Tablo Seçimi**: Dropdown menüden görüntülemek istediğiniz tabloyu seçin
2. **Tarih Filtreleme**: Başlangıç ve bitiş tarihlerini belirleyin
3. **Filtrele**: Verileri görüntülemek için "Filtrele" butonuna tıklayın
4. **CSV İndir**: Verileri CSV formatında indirmek için "CSV İndir" butonunu kullanın

## Güvenlik Notları

- Üretim ortamında `config.php` dosyasını web erişiminden koruyun
- Veritabanı kullanıcısına sadece gerekli yetkileri verin
- HTTPS kullanımını düşünün

## Özelleştirme

### Yeni Tablo Ekleme
Veritabanınıza yeni tablo eklerseniz, otomatik olarak dropdown menüde görünecektir.

### Stil Değişiklikleri
CSS stilleri `index.php` dosyası içinde `<style>` tagları arasında bulunur.

### Fonksiyon Ekleme
JavaScript fonksiyonları dosyanın alt kısmında bulunur.

## Sorun Giderme

### Veritabanı Bağlantı Hatası
- WAMP servislerinin çalıştığını kontrol edin
- `config.php` dosyasındaki ayarları doğrulayın

### Tablolar Görünmüyor
- Veritabanının doğru şekilde oluşturulduğunu kontrol edin
- `database_setup.sql` dosyasını tekrar çalıştırın

### CSV İndirme Çalışmıyor
- Tarayıcının popup blocker ayarlarını kontrol edin
- JavaScript'in etkin olduğunu doğrulayın

## Lisans

Bu proje MIT lisansı ile lisanslanmıştır.
