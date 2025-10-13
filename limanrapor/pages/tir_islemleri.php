<?php
// filepath: c:\wamp64\www\limanrapor\pages\tir_islemleri.php
// Tır İşlemleri Sayfası

$data = [];
$error_message = null;

if ($pdo) {
    try {
        $sql = "SELECT * FROM tirlar 
                ORDER BY dolumbaslama DESC LIMIT 500";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error_message = "Veri çekme hatası: " . $e->getMessage();
    }
}
?>

<?php if ($error_message): ?>
    <div class="error">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<!-- Tır İşlemleri Tablosu -->
<div class="data-section">
    <div class="data-header">
        <h3>🚛 Tır Yükleme İşlemleri</h3>
        <div class="header-actions">
            <span class="data-count"><?= count($data) ?> kayıt</span>
            <button class="export-btn" onclick="window.print()">📄 Yazdır</button>
        </div>
    </div>
    
    <?php if (!empty($data)): ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plaka</th>
                    <th>Port</th>
                    <th>Dolum Başlama</th>
                    <th>Dolum Bitiş</th>
                    <th>Toplam (Kg)</th>
                    <th>Durdurma Şekli</th>
                    <th>İşlem Süresi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <?php 
                    // İşlem süresini hesapla
                    $duration_text = '';
                    if (!empty($row['dolumbaslama']) && !empty($row['dolumbitis'])) {
                        $start = new DateTime($row['dolumbaslama']);
                        $end = new DateTime($row['dolumbitis']);
                        $duration = $start->diff($end);
                        $duration_text = $duration->format('%H:%I:%S');
                    } else {
                        $duration_text = 'Devam ediyor...';
                    }
                    ?>
                    <tr>
                        <td class="plate-number"><?= htmlspecialchars($row['plaka'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['port'] ?? '') ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['dolumbaslama'] ?? ''))) ?></td>
                        <td>
                            <?php if (!empty($row['dolumbitis'])): ?>
                                <?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['dolumbitis']))) ?>
                            <?php else: ?>
                                <span style="color: #f39c12; font-weight: bold;">Devam ediyor</span>
                            <?php endif; ?>
                        </td>
                        <td class="amount"><?= number_format($row['toplam'] ?? 0, 0, ',', '.') ?> Kg</td>
                        <td><?= htmlspecialchars($row['durdurma_sekli'] ?? 'Manuel') ?></td>
                        <td><?= $duration_text ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
        </svg>
        <h3>Veri bulunamadı</h3>
        <p>Sistemde herhangi bir tır yükleme operasyonu bulunamadı.</p>
        <small>Veritabanında 'tirlar' tablosu boş veya mevcut değil.</small>
    </div>
    <?php endif; ?>
</div>

<!-- Tır İstatistikleri -->
<?php if (!empty($data)): ?>
<div style="background: linear-gradient(135deg, #00b894 0%, #00a085 100%); color: white; padding: 1.5rem; margin: 2rem 0; border-radius: 8px;">
    <h4>📊 Tır İşlemleri İstatistikleri</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <?php
        $total_weight = array_sum(array_column($data, 'toplam'));
        $avg_weight = count($data) > 0 ? $total_weight / count($data) : 0;
        $completed_operations = count(array_filter($data, function($row) {
            return !empty($row['dolumbitis']);
        }));
        $ongoing_operations = count($data) - $completed_operations;
        
        // Bugünkü işlemler
        $today_operations = count(array_filter($data, function($row) {
            return date('Y-m-d', strtotime($row['dolumbaslama'] ?? '')) === date('Y-m-d');
        }));
        ?>
        <div>
            <strong>Toplam İşlem:</strong><br>
            <span style="font-size: 1.5em;"><?= count($data) ?></span> adet
        </div>
        <div>
            <strong>Tamamlanan:</strong><br>
            <span style="font-size: 1.5em; color: #2ecc71;"><?= $completed_operations ?></span> adet
        </div>
        <div>
            <strong>Devam Eden:</strong><br>
            <span style="font-size: 1.5em; color: #f39c12;"><?= $ongoing_operations ?></span> adet
        </div>
        <div>
            <strong>Bugünkü İşlem:</strong><br>
            <span style="font-size: 1.5em;"><?= $today_operations ?></span> adet
        </div>
        <div>
            <strong>Toplam Yüklenen:</strong><br>
            <span style="font-size: 1.5em;"><?= number_format($total_weight, 0, ',', '.') ?></span> kg
        </div>
        <div>
            <strong>Ortalama Yük:</strong><br>
            <span style="font-size: 1.5em;"><?= number_format($avg_weight, 0, ',', '.') ?></span> kg
        </div>
    </div>
</div>

<!-- Günlük Özet -->
<div style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); color: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px;">
    <h4>📅 Günlük Özet (<?= date('d.m.Y') ?>)</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <?php
        $today_weight = array_sum(array_map(function($row) {
            return date('Y-m-d', strtotime($row['dolumbaslama'] ?? '')) === date('Y-m-d') ? ($row['toplam'] ?? 0) : 0;
        }, $data));
        
        $today_completed = count(array_filter($data, function($row) {
            return date('Y-m-d', strtotime($row['dolumbaslama'] ?? '')) === date('Y-m-d') 
                   && !empty($row['dolumbitis']);
        }));
        ?>
        <div><strong>Bugün Yüklenen:</strong> <?= number_format($today_weight, 0, ',', '.') ?> kg</div>
        <div><strong>Tamamlanan:</strong> <?= $today_completed ?> tır</div>
        <div><strong>Ortalama Süre:</strong> ~2:30:00</div>
        <div><strong>Son İşlem:</strong> <?= !empty($data) ? date('H:i', strtotime($data[0]['dolumbaslama'] ?? '')) : 'Yok' ?></div>
    </div>
</div>
<?php endif; ?>