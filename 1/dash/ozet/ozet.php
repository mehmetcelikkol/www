<?php
include '../conn.php';

if ($conn) {
    echo "Bağlantı başarılı!";
} else {
    echo "Bağlantı başarısız!";
}

$idsql = "SELECT id FROM veriler ORDER BY id DESC";
$result = $conn->query($idsql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id = $row['id'];
}


// İlk sayfa yüklemesi için veri çekme kodu aynı kalacak
$sql = "SELECT 
v.serino,
v.temp,
v.hum,
v.wifi,
v.versiyon,
v.kayit_tarihi,
c.firmaid,
cr.unvan as firma_adi,
TIMESTAMPDIFF(MINUTE, v.kayit_tarihi, NOW()) as dakika_once
FROM (
    SELECT serino, MAX(kayit_tarihi) as son_kayit
    FROM veriler 
    GROUP BY serino
) as son
JOIN veriler v ON v.serino = son.serino AND v.kayit_tarihi = son.son_kayit
LEFT JOIN cihazlar c ON v.serino = c.serino
LEFT JOIN cari cr ON c.firmaid = cr.id
ORDER BY v.kayit_tarihi DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>HavaKontrol - Özetler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.075);
        }
        .offline {
            background-color: #ffe6e6;
        }
        .highlight {
            animation: highlight 2s ease-in-out;
        }
        @keyframes highlight {
            0% { background-color: #ff0022; }
            50% { background-color: #ff00cd; }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>

    <?php
// Versiyon bilgisini al
    $version = trim(file_get_contents('https://www.rmtproje.com/ota/version.txt'));
    ?>

    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2 class="d-inline">Genel Durum  </h2>
                <span class="badge bg-primary ms-2"> Güncel Versiyon: <?php echo $version; ?></span>
                <span class="badge bg-danger ms-2"> IP: 
                    <?php
                    $ip = $_SERVER["REMOTE_ADDR"]; 
                    echo $ip;
                    ?>
                </span>
                <span class="badge bg-warning ms-2"> Son Satır: 
                    <?php  
                    echo $id;
                    ?>
                </span>
<span class="badge bg-info ms-2">
                <?php echo date('d.m.Y H:i:s'); ?>
      </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Seri No</th>
                        <th>Sıcaklık</th>
                        <th>Nem</th>
                        <th>WiFi</th>
                        <th>Versiyon</th>
                        <th>Oturum</th>
                        <th>Cari</th>
                        <th>Konum</th>
                        <th>Son Veri Zamanı</th>
                        <th>Geçen Süre</th>
                        <th>IP Adresi</th>
                    </tr>
                </thead>

                <tbody id="cihazTablosu">
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let sonVeriler = {};

        function formatGecenSure(dakika) {
            if(dakika < 60) return `${dakika} dakika önce`;
            if(dakika < 1440) return `${Math.floor(dakika/60)} saat ${dakika%60} dakika önce`;
            return `${Math.floor(dakika/1440)} gün önce`;
        }

        function veriGuncelle(yeniVeri, element) {
            const eskiVeri = element.textContent;
            if(eskiVeri !== yeniVeri) {
                element.textContent = yeniVeri;
                element.closest('td').classList.add('highlight');
                setTimeout(() => {
                    element.closest('td').classList.remove('highlight');
                }, 2000);
            }
        }

        function tabloyuGuncelle(veriler) {
            veriler.forEach((veri, index) => {
                const row = document.querySelector(`tr[data-serino="${veri.serino}"]`);
                if(row) {
                    veriGuncelle(veri.temp + '°C', row.querySelector('.temp'));
                    veriGuncelle('%' + veri.hum, row.querySelector('.hum'));
                    veriGuncelle(veri.wifi, row.querySelector('.wifi'));
                    veriGuncelle(veri.oturum, row.querySelector('.oturum'));    
                    veriGuncelle(veri.kayit_tarihi, row.querySelector('.kayit'));
                    veriGuncelle(formatGecenSure(veri.dakika_once), row.querySelector('.sure'));
         //   veriGuncelle(veri.ip, row.querySelector('.ip'));

                    row.classList.toggle('offline', veri.dakika_once > 30);
                }
            });
        }


        function ilkTabloyuOlustur() {
            fetch('veri_kontrol.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('cihazTablosu');
                data.forEach((veri, index) => {
                    const firma = veri.firma_adi || 'STOK ÜRÜN';
                    const offline = veri.dakika_once > 30 ? 'offline' : '';
                    
                    tbody.innerHTML += `
    <tr class="${offline}" data-serino="${veri.serino}">
        <td>${index + 1}</td>
        <td>${veri.serino}</td>
        <td><span class="temp">${veri.temp}°C</span></td>
        <td><span class="hum">%${veri.hum}</span></td>
        <td><span class="wifi">${veri.wifi}</span></td>
        <td><span class="versiyon">${veri.versiyon}</span></td>
        <td><span class="oturum">${veri.oturum}</span></td>    
        <td>${firma}</td>
                        <td>${veri.konum}</td>
        <td><span class="kayit">${veri.kayit_tarihi}</span></td>
        <td><span class="sure">${formatGecenSure(veri.dakika_once)}</span></td>
                        <td><span class="ip">${veri.ip}</span></td>
    </tr>
                    `;

                });
            });
        }

        function veriKontrol() {
            fetch('veri_kontrol.php')
            .then(response => response.json())
            .then(data => {
                tabloyuGuncelle(data);
            });
        }

    // Sayfa yüklendiğinde ilk tabloyu oluştur
        document.addEventListener('DOMContentLoaded', ilkTabloyuOlustur);
    // Her 5 saniyede bir kontrol et
        setInterval(veriKontrol, 5000);
    </script>

</body>
</html>