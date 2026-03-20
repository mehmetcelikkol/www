<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
require_once __DIR__ . '/includes/header.php';

// Tüm Cihazları Çek
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

<div class="container-fluid">
    <h2 class="mb-4"><i class="fa-solid fa-list text-primary"></i> Tüm Cihazlar</h2>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kimlik</th>
                            <th>Adı</th>
                            <th>Konum</th>
                            <th>Son Ağırlık</th>
                            <th>Kapasite</th>
                            <th>Durum</th>
                            <th>Son Veri</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cihazlar as $c): 
                            $agirlik = floatval($c['agirlik_degeri'] ?? 0);
                            $max = floatval($c['max_agirlik'] ?? 0);
                            $yuzde = ($max > 0) ? round(($agirlik/$max)*100) : 0;
                            
                            $zaman = !empty($c['alinan_zaman']) ? strtotime($c['alinan_zaman']) : 0;
                            $fark = time() - $zaman;
                            $online = ($fark < 600); // 10 dk
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $c['cihaz_kodu']; ?></td>
                            <td><?php echo htmlspecialchars($c['cihaz_adi'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($c['konum'] ?? '-'); ?></td>
                            <td><?php echo number_format($agirlik, 0, ',', '.'); ?> kg</td>
                            <td>
                                <?php if($max > 0): ?>
                                    <div class="progress" style="height: 5px; width: 100px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $yuzde; ?>%"></div>
                                    </div>
                                    <small class="text-muted">%<?php echo $yuzde; ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $online ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $online ? 'ONLINE' : 'OFFLINE'; ?>
                                </span>
                            </td>
                            <td><?php echo $zaman > 0 ? date('d.m H:i', $zaman) : '-'; ?></td>
                            <td>
                                <a href="cihaz.php?id=<?php echo $c['cihaz_kodu']; ?>" class="btn btn-sm btn-outline-primary">Detay</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
