# SEO Kontrol Listesi - RMT Proje
=======================================

## ✅ Tamamlanan SEO İşlemleri

### 1. **robots.txt** ✅
- Dosya konumu: `/rmtproje/robots.txt`
- Arama motorlarına yönerge verir
- Sitemap konumunu belirtir
- Taranmasını istemediğimiz dizinleri engeller

### 2. **sitemap.xml** ✅
- Dosya konumu: `/rmtproje/sitemap.xml`
- Tüm sayfa URL'lerini listeler
- Güncellenme sıklığını belirtir
- Öncelik seviyelerini tanımlar

### 3. **.htaccess** ✅
- Apache sunucu yapılandırması
- GZIP sıkıştırma aktif
- Browser cache optimizasyonu
- Güvenlik başlıkları
- URL yeniden yazma kuralları

### 4. **Meta Etiketleri** ✅
- Title: Anahtar kelimelerle optimize edildi
- Description: 160 karakter altında, çekici açıklama
- Keywords: İlgili anahtar kelimeler
- Open Graph: Facebook/sosyal medya için
- Twitter Cards: Twitter paylaşımları için
- Canonical URL: Tekrarlanan içerik engelleme

### 5. **Structured Data** ✅
- JSON-LD formatında şirket bilgileri
- Google için local business şeması
- İletişim bilgileri yapılandırıldı

### 6. **Hata Sayfaları** ✅
- 404.html: Sayfa bulunamadı
- 403.html: Erişim engellendi  
- 500.html: Sunucu hatası

## 📋 Canlı Yayına Alırken Yapılacaklar

### 1. **Domain ve URL Güncellemeleri**
```
Şu anda: http://localhost/rmtproje/
Güncellenecek: https://www.rmtproje.com/
```

**Güncellenecek Dosyalar:**
- `sitemap.xml` - Tüm URL'ler
- `robots.txt` - Sitemap URL'si
- `index.html` - Meta etiketlerdeki URL'ler
- `.htaccess` - HTTPS yönlendirmesi aktif et

### 2. **SSL Sertifikası**
`.htaccess` dosyasındaki HTTPS yönlendirmesini aktif et:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. **Google Search Console**
- Site ownership doğrulama
- Sitemap gönderimi
- Index durumu takibi

### 4. **Google Analytics**
```html
<!-- Google Analytics kodu ekle -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
```

### 5. **Sosyal Medya Meta Resmi**
- Open Graph için logo/kapak resmi optimize et
- Boyut: 1200x630 px önerilen

## 🔧 WAMP/cPanel Yapılandırması

### Apache Modülleri (Aktif Olmalı):
- `mod_rewrite` - URL yeniden yazma
- `mod_deflate` - GZIP sıkıştırma  
- `mod_expires` - Cache kontrol
- `mod_headers` - Güvenlik başlıkları

### Dosya İzinleri:
- `.htaccess`: 644
- `robots.txt`: 644
- `sitemap.xml`: 644
- Diğer dosyalar: 644
- Dizinler: 755

## 📊 SEO Test Araçları

### Kontrol Edilecek Siteler:
1. **Google PageSpeed Insights**: Sayfa hızı
2. **GTmetrix**: Performance analizi
3. **SEMrush**: SEO skoru
4. **Google Search Console**: Index durumu
5. **Bing Webmaster Tools**: Bing için optimize

### Lokal Test Komutları:
```bash
# robots.txt kontrolü
curl http://localhost/rmtproje/robots.txt

# Sitemap kontrolü  
curl http://localhost/rmtproje/sitemap.xml

# Meta etiket kontrolü
curl -I http://localhost/rmtproje/
```

## 🎯 Anahtar Kelime Stratejisi

### Ana Anahtar Kelimeler:
- endüstriyel otomasyon
- PLC programlama  
- SCADA sistemleri
- Safety PLC
- HMI tasarımı

### Lokasyon Bazlı:
- balıkesir otomasyon
- teknopark firması
- endüstriyel otomasyon türkiye

### Sektörel:
- ford otosan otomasyon
- otomotiv PLC
- kompozit üretim otomasyon

## ⚡ Performans Optimizasyonu

### Yapılan İyileştirmeler:
- ✅ CSS/JS sıkıştırma
- ✅ Resim lazy loading
- ✅ Browser caching
- ✅ GZIP compression
- ✅ Video optimizasyonu

### Gelecek İyileştirmeler:
- [ ] WebP resim formatı
- [ ] CDN kullanımı
- [ ] Critical CSS
- [ ] Service Worker (PWA)

Bu dosyayı referans alarak SEO işlemlerinizi tamamlayabilirsiniz!
