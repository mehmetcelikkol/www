<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$sql = "SELECT c.*, l.max_agirlik, p.agirlik_degeri, p.alinan_zaman 
        FROM cihazlar c
        LEFT JOIN cihaz_limitleri l ON c.cihaz_kodu = l.cihaz_kimligi
        LEFT JOIN (
            SELECT * FROM cihaz_paketleri WHERE id IN (
                SELECT MAX(id) FROM cihaz_paketleri GROUP BY cihaz_kimligi
            )
        ) p ON c.cihaz_kodu = p.cihaz_kimligi
        ORDER BY c.id DESC";
$cihazlar = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid animate__animated animate__fadeIn">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="neon-text mb-1">CİHAZ ENVANTERİ</h2>
            <p class="text-muted">Sistemdeki Tüm Aktif IoT Düğümleri</p>
        </div>
        <div class="col-md-6 text-md-end">
             <div class="badge bg-primary bg-opacity-10 text-primary p-2 px-3 border border-primary border-opacity-25">
                <i class="fa-solid fa-server me-2"></i> TOPLAM: <?php echo count($cihazlar); ?> ÜNİTE
             </div>
        </div>
    </div>
    
    <div class="card border-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">SİSTEM KODU</th>
                        <th>CİHAZ TANIMI</th>
                        <th>SAHA / KONUM</th>
                        <th>SON VERİ (KG)</th>
                        <th>DOLULUK ORANI</th>
                        <th>DURUM</th>
                        <th>ERİŞİM</th>
                        <th class="pe-4 text-end">AKSİYON</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cihazlar as $c): 
                        $agirlik = floatval($c['agirlik_degeri'] ?? 0);
                        $max = floatval($c['max_agirlik'] ?? 20000);
                        $yuzde = ($max > 0) ? round(($agirlik/$max)*100) : 0;
                        $yuzde = max(0, min(100, $yuzde));
                        
                        $zaman = !empty($c['alinan_zaman']) ? strtotime($c['alinan_zaman']) : 0;
                        $online = (time() - $zaman < 600);
                        
                        $yuzde_color = "bg-primary";
                        $row_class = "";
                        if($yuzde < 50) {
                            $yuzde_color = "bg-warning";
                        }
                        if($yuzde < 20) {
                            $yuzde_color = "bg-danger";
                            $row_class = "table-glow-danger";
                        }
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="ps-4 fw-bold text-info" style="font-family: 'Orbitron', sans-serif; font-size: 0.85rem;">
                            <?php if($online): ?>
                                <span class="pulse-live"></span>
                            <?php else: ?>
                                <span class="pulse-live pulse-danger"></span>
                            <?php endif; ?>
                            <?php echo $c['cihaz_kodu']; ?>
                        </td>
                        <td class="fw-bold"><?php echo htmlspecialchars($c['cihaz_adi'] ?? '-'); ?></td>
                        <td><i class="fa-solid fa-location-dot me-1 text-muted"></i> <?php echo htmlspecialchars($c['konum'] ?? '-'); ?></td>
                        <td class="fw-bold"><?php echo number_format($agirlik, 0, ',', '.'); ?></td>
                        <td style="width: 180px;">
                            <div class="d-flex align-items-center">
                                <div class="progress bg-white bg-opacity-10 flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar <?php echo $yuzde_color; ?> shadow-sm" style="width: <?php echo $yuzde; ?>%"></div>
                                </div>
                                <span class="ms-3 small fw-bold">%<?php echo $yuzde; ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if($online): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">AKTİF</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3">KAPALI</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?php echo $zaman > 0 ? date('d.m H:i', $zaman) : 'Veri Yok'; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <a href="cihaz.php?id=<?php echo $c['cihaz_kodu']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-solid fa-bolt me-1"></i> İNCELE
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
