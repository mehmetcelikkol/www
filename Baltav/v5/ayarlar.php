<?php
require_once 'sistem/header.php';
require_once 'sistem/sidebar.php';

// Güvenli oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit;
}

$id = $_SESSION['kullanici_id'];
$rol = $_SESSION['kullanici_rolu'];
$alt_kullanici_limiti = 5;
$mevcut_alt_kullanici = 0;

// Sadece Superadmin olmayanlar için limit kontrolü yap
if ($rol != 'superadmin') {
    $alt_kullanici_sorgu = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE parent_id = ?");
    $alt_kullanici_sorgu->execute([$id]);
    $mevcut_alt_kullanici = $alt_kullanici_sorgu->fetchColumn();
}

// Form POST İşlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['islem']) && $_POST['islem'] == 'sifre_degistir') {
        $mevcut_sifre = $_POST['mevcut_sifre'];
        $yeni_sifre = $_POST['yeni_sifre'];
        $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
        
        $q = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
        $q->execute([$id]);
        $uye = $q->fetch();
        
        if (password_verify($mevcut_sifre, $uye['sifre'])) {
            if ($yeni_sifre === $yeni_sifre_tekrar && mb_strlen($yeni_sifre) >= 6) {
                $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
                $uq = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                $uq->execute([$yeni_hash, $id]);
                
                sistem_log_yaz($db, 'Şifre Güncelleme', 'Kullanıcı kendi profil şifresini değiştirdi.');
                $basari = "Şifreniz başarıyla güncellendi.";
            } else {
                $hata = "Yeni şifreler eşleşmiyor veya 6 karakterden kısa!";
            }
        } else {
            $hata = "Mevcut şifrenizi yanlış girdiniz!";
        }
    }
    
    // Alt Personel / Sistem Kullanıcı Ekleme 
    if (isset($_POST['islem']) && $_POST['islem'] == 'kullanici_ekle') {
        $yeni_kullanici = trim($_POST['kullanici_adi']);
        $yeni_sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
        
        if ($rol == 'superadmin') {
            $yeni_rol = $_POST['rol'];
            $e_id = empty($_POST['entegre_id']) ? NULL : $_POST['entegre_id'];
            $i_id = empty($_POST['isletmeci_id']) ? NULL : $_POST['isletmeci_id'];
            $parent_id = NULL;
        } else {
            // Kotayı tekrar kontrol edelim
            if ($mevcut_alt_kullanici >= $alt_kullanici_limiti) {
                $hata = "Maksimum alt kullanıcı limitinize ulaştınız!";
            } else {
                $yeni_rol = $rol;
                $e_id = $_SESSION['entegre_id'] ?? NULL;
                $i_id = $_SESSION['isletmeci_id'] ?? NULL;
                $parent_id = $id;
            }
        }
        
        if (!isset($hata)) {
            $check = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
            $check->execute([$yeni_kullanici]);
            if ($check->rowCount() > 0) {
                $hata = "Bu giriş adı başkası tarafından kullanılıyor!";
            } else {
                $q = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, rol, entegre_id, isletmeci_id, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
                $q->execute([$yeni_kullanici, $yeni_sifre, $yeni_rol, $e_id, $i_id, $parent_id]);
                
                $mevcut_alt_kullanici++; // Arayüzü bozmasın diye artırıyoruz
                sistem_log_yaz($db, 'Kullanıcı Ekleme', "Sisteme ($yeni_kullanici) adlı yeni personel hesabı tanımlandı.");
                $basari = "Yeni personel hesabı başarıyla yaratıldı.";
            }
        }
    }
    
    // Kullanıcı Silme
    if (isset($_POST['islem']) && $_POST['islem'] == 'kullanici_sil') {
        $sil_id = $_POST['sil_id'];
        
        if ($rol == 'superadmin') {
            $yetkili_mi = true;
        } else {
            $checkQ = $db->prepare("SELECT id FROM kullanicilar WHERE id = ? AND parent_id = ?");
            $checkQ->execute([$sil_id, $id]);
            $yetkili_mi = $checkQ->rowCount() > 0;
        }
        
        if ($sil_id != $id && $yetkili_mi) {
            $q = $db->prepare("DELETE FROM kullanicilar WHERE id = ?");
            $q->execute([$sil_id]);
            $mevcut_alt_kullanici--;
            sistem_log_yaz($db, 'Kullanıcı Silme', "Sistemden ($sil_id) ID'li personel erişimi kaldırıldı.");
            $basari = "Personel hesabı başarıyla silindi.";
        } else {
            $hata = "Bu işlemi yapmaya yetkiniz yok veya kendi hesabınızı silemezsiniz!";
        }
    }
}

// Menüler İçin Detayları Çekelim
if ($rol == 'superadmin') {
    $tum_kullanicilar = $db->query("SELECT k.*, e.unvan as entegre_adi, i.unvan as isletmeci_adi FROM kullanicilar k LEFT JOIN entegreler e ON k.entegre_id = e.id LEFT JOIN isletmeciler i ON k.isletmeci_id = i.id ORDER BY k.id DESC")->fetchAll();
    $entegreler = $db->query("SELECT id, unvan FROM entegreler ORDER BY unvan ASC")->fetchAll();
    $isletmeciler = $db->query("SELECT id, unvan FROM isletmeciler ORDER BY unvan ASC")->fetchAll();
} else {
    $q_personeller = $db->prepare("SELECT k.*, e.unvan as entegre_adi, i.unvan as isletmeci_adi FROM kullanicilar k LEFT JOIN entegreler e ON k.entegre_id = e.id LEFT JOIN isletmeciler i ON k.isletmeci_id = i.id WHERE k.parent_id = ? ORDER BY k.id DESC");
    $q_personeller->execute([$id]);
    $tum_kullanicilar = $q_personeller->fetchAll();
}
?>

<div class="main-content">
    <div class="topbar">
        <h5 class="m-0 page-title">Sistem & Profil Ayarları</h5>
    </div>
    
    <?php if(isset($basari)): ?><div class="alert alert-success bg-success text-white border-0"><?= $basari ?></div><?php endif; ?>
    <?php if(isset($hata)): ?><div class="alert alert-danger bg-danger text-white border-0"><?= $hata ?></div><?php endif; ?>

    <div class="row g-4">
        <!-- Sol Taraf: Profil ve Şifre -->
        <div class="col-lg-4">
            <div class="silo-card glass p-4 mb-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3" style="width:80px; height:80px; font-size:32px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h4 class="fw-bold"><?= htmlspecialchars($_SESSION['kullanici_adi']) ?></h4>
                    <span class="badge bg-secondary"><?= strtoupper($rol) ?></span>
                </div>
                <hr>
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-key text-warning"></i> Şifre Değiştir</h6>
                <form method="POST">
                    <input type="hidden" name="islem" value="sifre_degistir">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Mevcut Şifreniz</label>
                        <input type="password" name="mevcut_sifre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Yeni Şifre (En az 6 karakter)</label>
                        <input type="password" name="yeni_sifre" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Yeni Şifre Tekrar</label>
                        <input type="password" name="yeni_sifre_tekrar" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Şifremi Güncelle</button>
                </form>
            </div>
        </div>

        <!-- Sağ Taraf: Kullanıcı/Personel Yönetimi -->
        <div class="col-lg-8">
            <?php if($rol == 'superadmin' || $mevcut_alt_kullanici < $alt_kullanici_limiti): ?>
            <div class="silo-card glass p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-plus text-primary"></i> Yeni Personel (Alt Kullanıcı) Tanımla</h5>
                <?php if($rol != 'superadmin'): ?>
                    <div class="alert alert-info small py-2">
                        Sistemde toplam <strong><?= $alt_kullanici_limiti ?></strong> personel kotanız bulunmaktadır. Kalan hakkınız: <strong><?= $alt_kullanici_limiti - $mevcut_alt_kullanici ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-3 bg-light p-3 rounded border">
                    <input type="hidden" name="islem" value="kullanici_ekle">
                    
                    <!-- Kullanıcı Adı ve Şifre (Herkes görür) -->
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Giriş Adı</label>
                        <input type="text" name="kullanici_adi" class="form-control form-control-sm" required autocomplete="off">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Giriş Şifresi</label>
                        <input type="text" name="sifre" class="form-control form-control-sm" required autocomplete="off">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success btn-sm w-100">Oluştur</button>
                    </div>

                    <!-- Superadmin Ekstra Yetkilendirme Alanları -->
                    <?php if($rol == 'superadmin'): ?>
                        <div class="col-md-4 mt-3">
                            <label class="form-label small text-muted">Sistem Rolü</label>
                            <select name="rol" class="form-select form-select-sm" required onchange="yetkiAlanlariniGoster(this.value)">
                                <option value="isletmeci">İşletmeci</option>
                                <option value="entegre">Entegre Firma</option>
                                <option value="admin">Yönetici (Admin)</option>
                                <option value="superadmin">Superadmin</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3" id="alan_isletmeci">
                            <label class="form-label small text-muted">Bağlanacak İşletmeci</label>
                            <select name="isletmeci_id" class="form-select form-select-sm">
                                <option value="">Seçiniz</option>
                                <?php foreach($isletmeciler as $i): ?>
                                    <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['unvan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mt-3" id="alan_entegre" style="display:none;">
                            <label class="form-label small text-muted">Bağlanacak Entegre</label>
                            <select name="entegre_id" class="form-select form-select-sm">
                                <option value="">Seçiniz</option>
                                <?php foreach($entegreler as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['unvan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <?php elseif($rol != 'superadmin'): ?>
            <div class="silo-card glass p-4 mb-4 text-center">
                <h5 class="fw-bold text-danger"><i class="fa-solid fa-ban"></i> Limit Doldu</h5>
                <p class="text-muted">Belirlenen <strong><?= $alt_kullanici_limiti ?></strong> adet alt personel kotanıza tamamen ulaştınız. Yeni personel eklemek için mevcut hesaplardan birini silmeniz gerekmektedir.</p>
            </div>
            <?php endif; ?>

            <!-- Kullanıcı Listesi (Herkesin yetkisinde olanları) -->
            <div class="silo-card glass p-4">
                <h5 class="fw-bold mb-3">
                    <?= ($rol == 'superadmin') ? 'Sistemdeki Tüm Kayıtlı Kullanıcılar' : 'Sizin Açtığınız Alt Personel Hesapları' ?>
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Giriş Adı</th>
                                <th>Rol / Yetki</th>
                                <th>Bağlantı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tum_kullanicilar as $uk): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($uk['kullanici_adi']) ?></td>
                                <td><span class="badge bg-secondary"><?= strtoupper($uk['rol']) ?></span></td>
                                <td>
                                    <?php 
                                    if ($uk['rol'] == 'isletmeci') echo "<small class='text-muted'>İşletmeci:</small> " . htmlspecialchars($uk['isletmeci_adi'] ?? 'Kendisi');
                                    elseif ($uk['rol'] == 'entegre') echo "<small class='text-muted'>Entegre:</small> " . htmlspecialchars($uk['entegre_adi'] ?? 'Kendisi');
                                    elseif ($uk['rol'] == 'admin') echo "<span class='badge border border-dark text-dark'>Sistem Admin</span>";
                                    else echo "<span class='text-primary fw-bold'>Tam Yetki</span>";
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" class="m-0" onsubmit="return confirm('Personel girişini silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="islem" value="kullanici_sil">
                                        <input type="hidden" name="sil_id" value="<?= $uk['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-user-xmark"></i> Çıkar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($tum_kullanicilar) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Henüz bir alt personel hesabı yaratmadınız.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if($rol == 'superadmin'): ?>
        <script>
        function yetkiAlanlariniGoster(rol) {
            document.getElementById('alan_isletmeci').style.display = (rol === 'isletmeci') ? 'block' : 'none';
            document.getElementById('alan_entegre').style.display = (rol === 'entegre') ? 'block' : 'none';
        }
        document.addEventListener("DOMContentLoaded", function() {
            var selectedRol = document.querySelector("select[name='rol']").value;
            yetkiAlanlariniGoster(selectedRol);
        });
        </script>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'sistem/footer.php'; ?>
