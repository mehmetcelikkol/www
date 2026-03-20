<?php
// ============================================================
// functions.php — SiloSense Yetki & Yardımcı Fonksiyonlar
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Rol Kontrol Fonksiyonları ----

function giris_yapildi_mi(): bool {
    return isset($_SESSION['kullanici_id']);
}

function is_admin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function is_bayi(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'bayi';
}

function is_entegre(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'entegre';
}

function oturum_kullanici_id(): int {
    return (int)($_SESSION['kullanici_id'] ?? 0);
}

function oturum_bagli_id(): ?int {
    return isset($_SESSION['bagli_id']) ? (int)$_SESSION['bagli_id'] : null;
}

function oturum_rol(): string {
    return $_SESSION['rol'] ?? '';
}

// ---- Yetki Kapısı (Guard) ----

/**
 * Belirtilen rollere sahip değilse kullanıcıyı giriş sayfasına yönlendir.
 * Kullanım: yetkili_giris_iste('admin', 'bayi');
 */
function yetkili_giris_iste(string ...$izin_verilen_roller): void {
    if (!giris_yapildi_mi()) {
        header('Location: /index.php?hata=oturum');
        exit;
    }
    if (!empty($izin_verilen_roller) && !in_array(oturum_rol(), $izin_verilen_roller, true)) {
        header('Location: /yetkisiz.php');
        exit;
    }
}

// ---- Cihaz Sorgulama Fonksiyonları ----

/**
 * Oturum açmış kullanıcıya ait cihazları getirir.
 * Admin tümünü görür. Bayi kendi cihazlarını, Entegre bağlı bayilerin cihazlarını görür.
 */
function kullanici_cihazlari_getir(PDO $pdo): array {
    $rol      = oturum_rol();
    $bagli_id = oturum_bagli_id();

    if ($rol === 'admin') {
        $stmt = $pdo->query("
            SELECT c.*, b.unvan AS bayi_adi, e.unvan AS entegre_adi
            FROM cihazlar c
            LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
            LEFT JOIN entegre_firmalar e ON e.id = c.aktif_entegre_id
            ORDER BY c.kayit_tarihi DESC
        ");
        return $stmt->fetchAll();
    }

    if ($rol === 'bayi') {
        $stmt = $pdo->prepare("
            SELECT c.*, b.unvan AS bayi_adi, e.unvan AS entegre_adi
            FROM cihazlar c
            LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
            LEFT JOIN entegre_firmalar e ON e.id = c.aktif_entegre_id
            WHERE c.sahip_bayi_id = ?
            ORDER BY c.kayit_tarihi DESC
        ");
        $stmt->execute([$bagli_id]);
        return $stmt->fetchAll();
    }

    if ($rol === 'entegre') {
        // Entegre firması: aktif_entegre_id'si kendisi olan cihazları görür
        $stmt = $pdo->prepare("
            SELECT c.*, b.unvan AS bayi_adi, e.unvan AS entegre_adi
            FROM cihazlar c
            LEFT JOIN bayiler b ON b.id = c.sahip_bayi_id
            LEFT JOIN entegre_firmalar e ON e.id = c.aktif_entegre_id
            WHERE c.aktif_entegre_id = ?
            ORDER BY c.kayit_tarihi DESC
        ");
        $stmt->execute([$bagli_id]);
        return $stmt->fetchAll();
    }

    return [];
}

/**
 * Tek bir cihazın yetkili kullanıcıya ait olup olmadığını doğrular.
 */
function cihaz_yetkisi_kontrol(PDO $pdo, int $cihaz_id): bool {
    $rol      = oturum_rol();
    $bagli_id = oturum_bagli_id();

    if ($rol === 'admin') return true;

    if ($rol === 'bayi') {
        $stmt = $pdo->prepare("SELECT id FROM cihazlar WHERE id = ? AND sahip_bayi_id = ?");
        $stmt->execute([$cihaz_id, $bagli_id]);
        return (bool)$stmt->fetch();
    }

    if ($rol === 'entegre') {
        $stmt = $pdo->prepare("SELECT id FROM cihazlar WHERE id = ? AND aktif_entegre_id = ?");
        $stmt->execute([$cihaz_id, $bagli_id]);
        return (bool)$stmt->fetch();
    }

    return false;
}

// ---- Silo / Doluluk Hesaplama ----

/**
 * Ağırlık değerini ve maksimum kapasiteyi alarak doluluk yüzdesini döner.
 * Limiti yoksa %50 varsayılan.
 */
function doluluk_yuzde_hesapla(PDO $pdo, string $cihaz_kodu, float $guncel_agirlik): float {
    $stmt = $pdo->prepare("SELECT max_agirlik FROM cihaz_limitleri WHERE cihaz_kimligi = ?");
    $stmt->execute([$cihaz_kodu]);
    $limit = $stmt->fetch();

    if (!$limit || $limit['max_agirlik'] <= 0) return 50.0;

    $yuzde = ($guncel_agirlik / $limit['max_agirlik']) * 100;
    return min(max(round($yuzde, 1), 0), 100);
}

/**
 * Doluluk yüzdesine göre renk döner (Bootstrap renk sınıfı).
 */
function doluluk_rengi(float $yuzde): string {
    if ($yuzde >= 70) return 'success';
    if ($yuzde >= 30) return 'warning';
    return 'danger';
}

/**
 * Doluluk yüzdesine göre hex renk döner (CSS animasyon için).
 */
function doluluk_rengi_hex(float $yuzde): string {
    if ($yuzde >= 70) return '#22c55e';
    if ($yuzde >= 30) return '#f59e0b';
    return '#ef4444';
}

// ---- Yem Bitiş Tarihi Tahmini ----

/**
 * Son N günlük ortalama tüketimi hesaplar ve bitiş tarihini tahmin eder.
 * @return array{gun_kaldi: float|null, bitis_tarihi: string|null, gunluk_tuketim: float|null}
 */
function yem_bitis_tahmini(PDO $pdo, string $cihaz_kodu, float $guncel_agirlik, int $analiz_gun = 7): array {
    $stmt = $pdo->prepare("
        SELECT agirlik_degeri, alinan_zaman
        FROM cihaz_paketleri
        WHERE cihaz_kimligi = ?
          AND alinan_zaman >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY alinan_zaman ASC
        LIMIT 2
    ");
    $stmt->execute([$cihaz_kodu, $analiz_gun]);
    $veriler = $stmt->fetchAll();

    // İlk ve son veriyi karşılaştırarak günlük tüketimi bul
    $stmt2 = $pdo->prepare("
        SELECT agirlik_degeri, alinan_zaman FROM cihaz_paketleri
        WHERE cihaz_kimligi = ? ORDER BY alinan_zaman ASC LIMIT 1
    ");
    $stmt2->execute([$cihaz_kodu]);
    $ilk = $stmt2->fetch();

    $stmt3 = $pdo->prepare("
        SELECT agirlik_degeri, alinan_zaman FROM cihaz_paketleri
        WHERE cihaz_kimligi = ?
          AND alinan_zaman >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY alinan_zaman DESC LIMIT 1
    ");
    $stmt3->execute([$cihaz_kodu, $analiz_gun]);
    $son = $stmt3->fetch();

    if (!$ilk || !$son) return ['gun_kaldi' => null, 'bitis_tarihi' => null, 'gunluk_tuketim' => null];

    $agirlik_farki = $ilk['agirlik_degeri'] - $son['agirlik_degeri'];
    $zaman_farki_gun = (strtotime($son['alinan_zaman']) - strtotime($ilk['alinan_zaman'])) / 86400;

    if ($zaman_farki_gun <= 0 || $agirlik_farki <= 0) {
        return ['gun_kaldi' => null, 'bitis_tarihi' => null, 'gunluk_tuketim' => null];
    }

    $gunluk_tuketim = $agirlik_farki / $zaman_farki_gun;
    $gun_kaldi = round($guncel_agirlik / $gunluk_tuketim, 1);
    $bitis_tarihi = date('d.m.Y', strtotime("+{$gun_kaldi} days"));

    return [
        'gun_kaldi'      => $gun_kaldi,
        'bitis_tarihi'   => $bitis_tarihi,
        'gunluk_tuketim' => round($gunluk_tuketim, 2),
    ];
}

// ---- Güvenlik Yardımcıları ----

function html_temizle(string $deger): string {
    return htmlspecialchars($deger, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function csrf_token_olustur(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_dogrula(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---- Flash Mesaj Sistemi ----

function flash_mesaj_ekle(string $tur, string $mesaj): void {
    $_SESSION['flash'][] = ['tur' => $tur, 'mesaj' => $mesaj];
}

function flash_mesajlari_al(): array {
    $mesajlar = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mesajlar;
}
