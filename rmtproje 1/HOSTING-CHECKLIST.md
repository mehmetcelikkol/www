# Hosting Yükleme Öncesi Kontrol Listesi
==========================================

## ⚠️ FTP ÖNCESİ YAPILMASI GEREKENLER

### 1. 🌐 Domain Bilgilerini Güncelle

**Değiştirilecek Dosyalar:**
- ✅ sitemap.xml
- ✅ robots.txt  
- ✅ index.html (meta etiketler)
- ✅ .htaccess (HTTPS yönlendirme)

**Örnek:**
```
Şu an: http://localhost/rmtproje/
Olacak: https://www.rmtproje.com/
```

### 2. 📁 Klasör Yapısı

**public_html Klasör Düzeni:**
```
public_html/
├── index.html          ← Ana dosya
├── robots.txt
├── sitemap.xml
├── .htaccess
├── 404.html
├── 403.html
├── 500.html
├── css/
│   └── style.css
├── js/
│   └── script.js
└── images/
    ├── logo.png
    ├── 10yil.png
    └── projects/
```

### 3. 🔒 Güvenlik Kontrolleri

**Yüklenmemesi gereken dosyalar:**
- ❌ seo-test.bat
- ❌ video-test.html
- ❌ optimize-videos.bat
- ❌ .htaccess.backup
- ❌ SEO-CHECKLIST.md
- ❌ TROUBLESHOOTING.md
- ❌ video-format-guide.md

### 4. 📊 Hosting Gereksinimleri

**Hosting Özellikleri:**
- ✅ PHP 7.4+ (Form işlemi için)
- ✅ Apache + mod_rewrite
- ✅ HTTPS SSL sertifikası
- ✅ GZIP compression
- ✅ File permissions (644/755)

### 5. ✉️ İletişim Formu

**Form PHP dosyası gerekli! Şu anda sadece JavaScript var.**

## 🚀 YÜKLEMEYİ BAŞLATALIM!

Bu kontrol listesini okuduktan sonra eğer hazırsanız, 
dosyaları hosting için optimize edelim.
