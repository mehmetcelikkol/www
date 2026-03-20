@echo off
echo Video Optimizasyon Scripti - RMT Proje
echo ======================================

set "input_folder=c:\wamp64\www\rmtproje\images\projects\saglam-metal"
set "output_folder=c:\wamp64\www\rmtproje\images\projects\saglam-metal\optimized"

echo.
echo Giriş klasörü: %input_folder%
echo Çıkış klasörü: %output_folder%
echo.

if not exist "%output_folder%" (
    mkdir "%output_folder%"
    echo Optimized klasörü oluşturuldu.
)

echo.
echo Video dosyaları analiz ediliyor...
echo.

for %%f in ("%input_folder%\*.mp4") do (
    echo.
    echo İşlenen dosya: %%~nxf
    echo Boyut: 
    dir "%%f" | find "%%~nxf"
    
    echo Bu dosyayı optimize etmek ister misiniz? [Y/N]
    set /p choice=
    
    if /i "%choice%"=="Y" (
        echo Optimizasyon için önerilen araçlar:
        echo 1. HandBrake ^(ücretsiz GUI^)
        echo 2. FFmpeg ^(komut satırı^)
        echo 3. Online dönüştürücüler
        echo.
        echo Önerilen ayarlar:
        echo - Çözünürlük: 720p veya 1080p
        echo - Bitrate: 1-2 Mbps
        echo - Format: H.264 ^(MP4^)
        echo - Ses: AAC 128kbps
    )
)

echo.
echo İşlem tamamlandı.
echo.
echo WAMP sunucunuzun çalıştığından emin olun:
echo - Apache: Çalışıyor olmalı
echo - http://localhost/rmtproje/ adresinden siteye erişebilirsiniz
echo.
pause
