# Enerji İzleme Uygulaması

Bu depo, `c:/wamp64/www/enerji` altındaki PHP tabanlı enerji izleme uygulamasının kaynak kodlarını içerir.

## Başlarken
- PHP 7+/8+, WAMP ortamı
- İsteğe bağlı: Composer (kütüphaneler için)

## Yapılandırma
- Örnek ortam değişkenleri için `.env.example` dosyasını `.env` olarak kopyalayın ve değerleri doldurun.

## Geliştirme
- Değişikliklerinizi branch üzerinde yapın, açıklayıcı commit mesajları yazın.
- Hassas dosyalar repo dışında tutulur: `.env`, `auth.php`, `mail_util.php`, `logs/`.

## Versiyonlama ve GitHub’a Push
Yerelde:
1. İlk kurulum (zaten yapıldı):
	- `git init`
	- `git add -A`
	- `git commit -m "Initial commit"`
2. Uzak depo ekleyip gönderin:
	- `git branch -M main`
	- `git remote add origin https://github.com/<kullanici_adi>/enerji.git`
	- `git push -u origin main`

GitHub CLI ile (opsiyonel):
```
gh auth login --web --git-protocol https
gh repo create enerji --source . --private --push
```

## Dağıtım
- `.env` ve gizli anahtarları sunucuya manuel yerleştirin.
- Gerekirse `vendor/` klasörünü üretimde Composer ile kurun.

## Lisans
Bu proje şirket içi/özel kullanıma yöneliktir.
