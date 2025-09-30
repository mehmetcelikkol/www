@echo off
cd /d C:\wamp64\www

echo -------------------------------------
echo  Git otomatik commit ve push basliyor
echo -------------------------------------

:: Kullanıcıdan commit mesajı al
set /p usermsg=Commit mesaji giriniz: 

:: Tarih + saat ekle
for /f "tokens=1-3 delims=/ " %%a in ("%date%") do (
    for /f "tokens=1-2 delims=:." %%x in ("%time%") do (
        set msg=%usermsg% (%%a-%%b-%%c_%%x%%y)
    )
)

git add .
git commit -m "%msg%"
git push origin master

echo -------------------------------------
echo  Commit ve push tamamlandi!
echo -------------------------------------

pause
