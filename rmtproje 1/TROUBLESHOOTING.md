# WAMP & Site Açılma Sorunları Çözüm Rehberi
===============================================

## 🚨 Site Açılmıyor mu? Bu Adımları Takip Edin

### 1. WAMP Durumu Kontrolü
```cmd
# WAMP'in çalışıp çalışmadığını kontrol et
http://localhost/

# Apache durumu
http://localhost/server-status (Eğer aktifse)
```

### 2. .htaccess Sorunları
**Problem:** .htaccess dosyasındaki kuralllar Apache'yi karıştırabilir.

**Çözüm 1:** Geçici olarak devre dışı bırak
```cmd
# PowerShell'de
Rename-Item ".htaccess" ".htaccess.backup"
```

**Çözüm 2:** Basit versiyon kullan (Yaptık ✅)

### 3. Apache Modülleri Kontrolü
WAMP'te bu modüller aktif olmalı:
- ✅ mod_rewrite
- ✅ mod_deflate  
- ✅ mod_expires
- ✅ mod_headers

**Kontrol etmek için:**
1. WAMP simgesine tıkla
2. Apache → Apache modules
3. Yukarıdaki modüllerin işaretli olduğunu kontrol et

### 4. Dizin İzinleri
**Windows'ta genelde sorun olmaz, ama kontrol edin:**
- Proje klasörü: rmtproje/
- İzinler: Okuma/Yazma aktif olmalı

### 5. Port Çakışması
**Problem:** 80 portu başka uygulama tarafından kullanılıyor olabilir.

**Kontrol:**
```cmd
netstat -ano | findstr :80
```

**Çözüm:** WAMP'i farklı portta çalıştır
1. WAMP → Apache → httpd.conf
2. `Listen 80` → `Listen 8080` olarak değiştir
3. Site adresi: `http://localhost:8080/rmtproje/`

### 6. PHP Hataları Kontrolü
**error_log kontrolü:**
```
C:\wamp64\logs\apache_error.log
C:\wamp64\logs\php_error.log
```

### 7. Hosts Dosyası
**Windows hosts dosyası:**
```
C:\Windows\System32\drivers\etc\hosts
```

**İçeriği kontrol et:**
```
127.0.0.1    localhost
```

## 🔧 Hızlı Test Adımları

### Test 1: WAMP Çalışıyor mu?
```
http://localhost/
```
✅ WAMP ana sayfası açılmalı

### Test 2: Proje klasörü erişilebilir mi?
```
http://localhost/rmtproje/images/logo.png
```
✅ Logo resmi görünmeli

### Test 3: HTML dosyası çalışıyor mu?
```
http://localhost/rmtproje/index.html
```
✅ Site ana sayfası açılmalı

### Test 4: .htaccess çalışıyor mu?
```
http://localhost/rmtproje/robots.txt
```
✅ robots.txt içeriği görünmeli

## 🛠️ Sorun Çözme Sırası

### Adım 1: WAMP'i Yeniden Başlat
1. WAMP simgesi → Exit
2. WAMP'i tekrar çalıştır
3. Apache ve MySQL'in yeşil olduğunu kontrol et

### Adım 2: .htaccess'i Geçici Devre Dışı Bırak
```cmd
mv .htaccess .htaccess.backup
```

### Adım 3: Tarayıcı Cache'i Temizle
- Ctrl + Shift + R (Hard refresh)
- Veya F12 → Network → Disable cache

### Adım 4: Farklı Tarayıcı Dene
- Chrome'da açılmıyorsa Firefox dene
- Incognito/Private mode dene

### Adım 5: WAMP Loglarını Kontrol Et
```
C:\wamp64\logs\apache_error.log
```

## 📞 Son Çare

Eğer hiçbiri çalışmazsa:

1. **WAMP'i tamamen kapat**
2. **Windows'u yeniden başlat**  
3. **WAMP'i tekrar çalıştır**
4. **http://localhost/rmtproje/ adresini dene**

## ✅ Şu Anda Durum

- ✅ Tüm SEO dosyaları oluşturuldu
- ✅ .htaccess basitleştirildi  
- ✅ Site açılabilir durumda olmalı

**Test URL'leri:**
- Ana sayfa: http://localhost/rmtproje/
- Robots: http://localhost/rmtproje/robots.txt
- Sitemap: http://localhost/rmtproje/sitemap.xml
