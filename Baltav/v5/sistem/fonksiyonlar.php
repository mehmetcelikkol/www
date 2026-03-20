<?php
// Analitik ve Hesaplama Fonksiyonları

function rozet_renk_getir($id) {
    if (!$id) return ['bg' => '#f8f9fa', 'text' => '#6c757d', 'border' => '#dee2e6']; 
    $renkler = [
        ['bg' => '#e0f2fe', 'text' => '#0369a1', 'border' => '#bae6fd'], // Sky
        ['bg' => '#dcfce7', 'text' => '#15803d', 'border' => '#bbf7d0'], // Green
        ['bg' => '#fef9c3', 'text' => '#854d0e', 'border' => '#fef08a'], // Yellow
        ['bg' => '#f3e8ff', 'text' => '#6b21a8', 'border' => '#e9d5ff'], // Purple
        ['bg' => '#ffe4e6', 'text' => '#be123c', 'border' => '#fecdd3'], // Rose
        ['bg' => '#ffedd5', 'text' => '#c2410c', 'border' => '#fed7aa'], // Orange
        ['bg' => '#ccfbf1', 'text' => '#0f766e', 'border' => '#99f6e4'], // Teal
        ['bg' => '#f1f5f9', 'text' => '#334155', 'border' => '#e2e8f0']  // Slate
    ];
    $index = ((int)$id) % count($renkler);
    return $renkler[$index];
}

function sistem_log_yaz($db, $islem_tipi, $aciklama) {
    if (!isset($_SESSION['kullanici_id'])) return;
    
    $q = $db->prepare("INSERT INTO sistem_loglari (kullanici_id, kullanici_adi, rol, entegre_id, isletmeci_id, islem_tipi, aciklama) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)");
    $q->execute([
        $_SESSION['kullanici_id'],
        $_SESSION['kullanici_adi'],
        $_SESSION['kullanici_rolu'],
        $_SESSION['entegre_id'] ?? null,
        $_SESSION['isletmeci_id'] ?? null,
        $islem_tipi,
        $aciklama
    ]);
}

function tahmini_bitis_suresi($db, $cihaz_kodu, $mevcut_agirlik) {
    if ($mevcut_agirlik <= 0) return "<span class='text-danger fw-bold'>Tükendi</span>";
    
    // Son 24 saatteki en eski kaydı bulalım
    $q = $db->prepare("SELECT agirlik_degeri, alinan_zaman FROM cihaz_paketleri 
                       WHERE cihaz_kodu = ? AND alinan_zaman >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                       ORDER BY alinan_zaman ASC LIMIT 1");
    $q->execute([$cihaz_kodu]);
    $eski = $q->fetch();
    
    if (!$eski) return "<span class='text-muted'>Veri Yetersiz</span>";
    
    $fark_kg = $eski['agirlik_degeri'] - $mevcut_agirlik;
    if ($fark_kg <= 0) return "<span class='text-muted'>Tüketim Yok</span>"; // Artmış veya aynı kalmış (dolum yapılmış olabilir)
    
    $zaman_farki_saniye = strtotime(date('Y-m-d H:i:s')) - strtotime($eski['alinan_zaman']);
    $zaman_farki_saat = $zaman_farki_saniye / 3600;
    
    if ($zaman_farki_saat <= 0) return "<span class='text-muted'>Veri Yetersiz</span>";
    
    // kg/saat tüketim hızı
    $tuketim_hizi = $fark_kg / $zaman_farki_saat;
    
    // Kalan saat
    $kalan_saat = $mevcut_agirlik / $tuketim_hizi;
    
    $bitis_tarihi = date('d.m.Y H:i', strtotime("+" . ceil($kalan_saat) . " hours"));
    $tarih_html = "<br><small class='text-muted' style='font-size:0.75rem;'><i class='fa-regular fa-calendar-check'></i> $bitis_tarihi</small>";

    if ($kalan_saat > 24) {
        $kalan_gun = floor($kalan_saat / 24);
        $kalan_saat_artik = round(fmod($kalan_saat, 24));
        if ($kalan_saat_artik > 0) {
             return "<span class='text-success fw-bold'>" . $kalan_gun . " Gün, " . $kalan_saat_artik . " Saat</span>" . $tarih_html;
        }
        return "<span class='text-success fw-bold'>" . $kalan_gun . " Gün</span>" . $tarih_html;
    } else {
        $renk = ($kalan_saat < 12) ? 'text-danger' : 'text-warning';
        return "<span class='" . $renk . " fw-bold'>≈ " . round($kalan_saat) . " Saat</span>" . $tarih_html;
    }
}
?>
