@echo off
echo ========================================
echo RMT Proje - SEO ve Performans Test
echo ========================================
echo.

echo 1. robots.txt dosyası kontrol ediliyor...
if exist "robots.txt" (
    echo    ✓ robots.txt bulundu
    echo    İçerik önizleme:
    type robots.txt | more
) else (
    echo    ✗ robots.txt bulunamadı
)

echo.
echo 2. sitemap.xml dosyası kontrol ediliyor...
if exist "sitemap.xml" (
    echo    ✓ sitemap.xml bulundu
) else (
    echo    ✗ sitemap.xml bulunamadı
)

echo.
echo 3. .htaccess dosyası kontrol ediliyor...
if exist ".htaccess" (
    echo    ✓ .htaccess bulundu
) else (
    echo    ✗ .htaccess bulunamadı
)

echo.
echo 4. Hata sayfaları kontrol ediliyor...
if exist "404.html" (
    echo    ✓ 404.html bulundu
) else (
    echo    ✗ 404.html bulunamadı
)

if exist "403.html" (
    echo    ✓ 403.html bulundu
) else (
    echo    ✗ 403.html bulunamadı
)

if exist "500.html" (
    echo    ✓ 500.html bulundu
) else (
    echo    ✗ 500.html bulunamadı
)

echo.
echo 5. CSS ve JS dosyaları kontrol ediliyor...
if exist "css\style.css" (
    echo    ✓ CSS dosyası bulundu
) else (
    echo    ✗ CSS dosyası bulunamadı
)

if exist "js\script.js" (
    echo    ✓ JavaScript dosyası bulundu
) else (
    echo    ✗ JavaScript dosyası bulunamadı
)

echo.
echo 6. Resim dosyaları kontrol ediliyor...
if exist "images\logo.png" (
    echo    ✓ Logo bulundu
) else (
    echo    ✗ Logo bulunamadı
)

echo.
echo 7. WAMP sunucu durumu kontrol ediliyor...
echo    Tarayıcınızda test etmek için:
echo    → http://localhost/rmtproje/
echo    → http://localhost/rmtproje/robots.txt
echo    → http://localhost/rmtproje/sitemap.xml

echo.
echo ========================================
echo Test tamamlandı!
echo.
echo Sonraki adımlar:
echo 1. WAMP'i başlatın (eğer çalışmıyorsa)
echo 2. Tarayıcıda http://localhost/rmtproje/ adresini açın
echo 3. F12 geliştirici araçlarıyla performansı kontrol edin
echo 4. Google PageSpeed Insights ile test edin
echo 5. SEO-CHECKLIST.md dosyasını inceleyin
echo ========================================
echo.
pause
