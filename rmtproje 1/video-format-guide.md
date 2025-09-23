# Video Format Rehberi

## Tarayıcı Uyumluluğu için Video Formatları

### Önerilen Format Kombinasyonu:
1. **MP4 (H.264)** - En yaygın desteklenen format
2. **WebM** - Chrome, Firefox için optimize
3. **OGV** - Firefox için yedek format

### Video Dönüştürme Seçenekleri:

#### Ücretsiz Online Araçlar:
- CloudConvert.com
- Online-Convert.com
- Convertio.co

#### Masaüstü Yazılımlar:
- **FFmpeg** (Komut satırı - profesyonel)
- **HandBrake** (Ücretsiz GUI)
- **VLC Media Player** (Dönüştürme özelliği var)

### FFmpeg Komutları (Eğer FFmpeg kuruluysa):

```bash
# MP4 → WebM
ffmpeg -i input.mp4 -c:v libvpx-vp9 -crf 30 -b:v 0 -b:a 128k -c:a libopus output.webm

# MP4 → OGV
ffmpeg -i input.mp4 -c:v libtheora -q:v 7 -c:a libvorbis -q:a 4 output.ogv

# Web için optimize MP4
ffmpeg -i input.mp4 -c:v libx264 -preset slow -crf 22 -c:a aac -b:a 128k output.mp4
```

### Dosya Yapısı Önerisi:
```
images/projects/saglametal/
├── videos/
│   ├── video1.mp4
│   ├── video1.webm
│   └── video1.ogv
└── images/
    ├── image1.jpg
    └── image2.jpg
```

### JavaScript'te Çoklu Format Desteği:
Mevcut kodumuz şu anda tek format destekliyor. 
Çoklu format için video element'ine multiple source ekleyebiliriz.
