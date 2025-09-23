# RMT Proje Web Sitesi - Hosting Dağıtım Rehberi

## Genel Bakış
Bu rehber, RMT Proje web sitesinin rmtproje.com domain'ine yüklenmesi için gerekli adımları içermektedir.

## Ön Koşullar
- ✅ Domain: rmtproje.com (kayıtlı ve aktif)
- ✅ Web hosting hesabı (cPanel veya benzeri)
- ✅ FTP erişim bilgileri
- ✅ E-posta yapılandırması (rmt@rmtproje.com)

## Dosya Listesi
Aşağıdaki dosyalar hosting'e yüklenmelidir:

### Ana Dosyalar
- `index.html` - Ana sayfa
- `.htaccess` - Apache yapılandırması
- `contact.php` - İletişim formu handler'ı

### SEO Dosyları
- `robots.txt` - Arama motoru talimatları
- `sitemap.xml` - Site haritası

### Hata Sayfaları
- `404.html` - Sayfa bulunamadı
- `403.html` - Erişim engellendi
- `500.html` - Sunucu hatası

### Medya Dosyaları
- `images/` klasörü ve tüm içeriği
  - `logo.png`
  - `hero-bg.jpg`
  - `10yil.png`
  - `partnership-bg.png`
  - `projects-hero.jpg`
  - `service-hero-bg.jpg`
  - `projects/` alt klasörleri
  - `references/` alt klasörleri
  - `services/` alt klasörleri

## Dağıtım Adımları

### 1. FTP ile Dosya Yükleme
```
1. FTP istemcisi açın (FileZilla, WinSCP vb.)
2. Hosting FTP bilgilerinizi girin
3. public_html/ klasörüne bağlanın
4. Tüm dosyaları public_html/ klasörüne yükleyin
```

### 2. Dosya İzinleri (CHMOD)
```
- Klasörler: 755
- HTML/CSS/JS dosyaları: 644
- PHP dosyaları: 644
- .htaccess: 644
```

### 3. E-posta Yapılandırması
```
1. cPanel > Email Accounts
2. rmt@rmtproje.com e-posta hesabını oluşturun
3. Contact form'un çalışması için gerekli
```

### 4. SSL Sertifikası
```
1. cPanel > SSL/TLS
2. Let's Encrypt veya hosting sağlayıcısının SSL'ini aktifleştirin
3. www.rmtproje.com ve rmtproje.com için geçerli olmalı
```

## Doğrulama Kontrolleri

### 1. Ana Sayfa Testi
- [ ] https://www.rmtproje.com yükleniyor
- [ ] https://rmtproje.com otomatik www'ye yönlendiriyor
- [ ] HTTP https'e yönlendiriyor

### 2. Responsive Tasarım
- [ ] Mobil cihazlarda düzgün görünüm
- [ ] Tablet cihazlarda düzgün görünüm
- [ ] Desktop'ta düzgün görünüm

### 3. İşlevsellik Testleri
- [ ] Menü navigasyonu çalışıyor
- [ ] Proje galerisi açılıyor
- [ ] Video oynatma çalışıyor
- [ ] İletişim formu çalışıyor

### 4. SEO Doğrulaması
- [ ] robots.txt erişilebilir: https://www.rmtproje.com/robots.txt
- [ ] sitemap.xml erişilebilir: https://www.rmtproje.com/sitemap.xml
- [ ] Meta etiketleri doğru
- [ ] Open Graph etiketleri çalışıyor

### 5. Hata Sayfaları
- [ ] 404 sayfası çalışıyor
- [ ] 403 sayfası çalışıyor
- [ ] 500 sayfası çalışıyor

### 6. Performans
- [ ] Sayfa yüklenme hızı (<3 saniye)
- [ ] GZIP sıkıştırma aktif
- [ ] Browser caching çalışıyor

## Potansiyel Sorunlar ve Çözümleri

### .htaccess Sorunları
```
Eğer .htaccess hata verirse:
1. Dosyayı geçici olarak yeniden adlandırın
2. Site çalışıyor mu kontrol edin
3. Hosting sağlayıcısından mod_rewrite desteği isteyin
```

### E-posta Sorunları
```
Contact form çalışmıyorsa:
1. PHP mail() fonksiyonu aktif mi kontrol edin
2. SMTP ayarlarını hosting sağlayıcısından öğrenin
3. contact.php'de SMTP kullanımına geçin
```

### SSL Sorunları
```
Mixed content hataları için:
1. Tüm kaynaklarda HTTPS kullanın
2. External link'leri kontrol edin
3. Browser console'da hata mesajlarını kontrol edin
```

## Bakım ve Güncelleme

### Düzenli Kontroller
- Site erişilebilirliği (haftalık)
- SSL sertifikası durumu (aylık)
- Backup kontrolü (aylık)

### İçerik Güncellemeleri
- Yeni projeler eklendiğinde sitemap.xml güncelle
- Yeni sayfalar eklendiğinde robots.txt kontrol et
- Meta açıklamaları güncel tut

### Güvenlik
- PHP ve hosting güncellemelerini takip et
- .htaccess güvenlik kurallarını gözden geçir
- Contact form spam korumasını izle

## İletişim Bilgileri
- Hosting sorunu: Hosting sağlayıcınızın desteği
- Teknik sorun: Web geliştirici
- Domain sorunu: Domain registrar'ı

## Notlar
- Bu site Apache web sunucusu için optimize edilmiştir
- PHP 7.4+ gereklidir
- MySQL veritabanı şu anda kullanılmamaktadır
- Tüm URL'ler https://www.rmtproje.com için yapılandırılmıştır

---
Dağıtım Tarihi: [Dağıtım tarihini buraya yazın]
Son Güncelleme: [Son güncelleme tarihini buraya yazın]
