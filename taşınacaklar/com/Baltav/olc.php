<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Ölçüm Test Formu</title>
</head>
<body>

<h2>Ölçüm Gönder (Test)</h2>

<form action="olcum_post.php" method="post">

    <label>Cihaz Kimliği</label><br>
    <input type="text" name="cihaz_kimligi" value="SILO_01"><br><br>

    <label>Paket No</label><br>
    <input type="number" name="paket_no" value="1"><br><br>

    <label>Ağırlık Değeri (kg)</label><br>
    <input type="number" step="0.01" name="agirlik_degeri" value="12543.60"><br><br>

    <label>Stabil mi?</label><br>
    <select name="stabil_mi">
        <option value="1">Evet (1)</option>
        <option value="0">Hayır (0)</option>
    </select><br><br>

    <label>Çalışma Süresi (saniye)</label><br>
    <input type="number" name="calisma_suresi_saniye" value="982341"><br><br>

    <label>RS485 Hata Sayısı</label><br>
    <input type="number" name="rs485_hata_sayisi" value="3"><br><br>

    <label>Yazılım Sürümü</label><br>
    <input type="text" name="yazilim_surumu" value="v1.2"><br><br>

    <button type="submit">Gönder</button>

</form>

</body>
</html>
