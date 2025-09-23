<?php
// filepath: c:\wamp64\www\limanrapor\pages\gemi_bosaltma.php
// Gemi Boşaltma Sayfası - Toplam Operasyonlar

$data = [];
$gemilog_results = [];
$error_message = null;

if ($pdo) {
    try {
        // Gemi boşaltma toplam verilerini çek
        $sql = "SELECT * FROM flowveri 
                WHERE sensor_adi IN ('gflow1', 'gflow2') 
                AND okuma_zamani >= :start_date AND okuma_zamani <= :end_date
                ORDER BY okuma_zamani DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
        $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gemi log verilerini de çek
        $gemilog_sql = "SELECT * FROM gemilog ORDER BY tarihsaat DESC LIMIT 100";
        $gemilog_stmt = $pdo->prepare($gemilog_sql);
        $gemilog_stmt->execute();
        $gemilog_results = $gemilog_stmt->fetchAll(PDO::FETCH_ASSOC);
        
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

<!-- Ana Operasyonlar Tablosu -->
<div class="data-section">
    <div class="data-header">
        <h3>🚢 Gemi Boşaltma Operasyonları</h3>
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
                    <th>Rıhtım</th>
                    <th>Zaman</th>
                    <th>Sıcaklık (°C)</th>
                    <th>Debi (T/h)</th>
                    <th>Yoğunluk (kg/L)</th>
                    <th>Operasyon Toplam (Ton)</th>
                    <th>Genel Toplam (Ton)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <?php 
                    // Sensör adını rıhtım ismiyle değiştir
                    $rihtim_adi = '';
                    if ($row['sensor_adi'] === 'gflow1') {
                        $rihtim_adi = 'Rıhtım 7';
                    } elseif ($row['sensor_adi'] === 'gflow2') {
                        $rihtim_adi = 'Rıhtım 8';
                    } else {
                        $rihtim_adi = $row['sensor_adi'] ?? '';
                    }
                    ?>
                    <tr>
                        <td class="sensor-name"><?= htmlspecialchars($rihtim_adi) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['okuma_zamani'] ?? ''))) ?></td>
                        <td class="amount"><?= number_format($row['sicaklik'] ?? 0, 1, ',', '.') ?></td>
                        <td class="amount" style="font-weight: bold; color: #c53030;"><?= number_format($row['debi'] ?? 0, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['yogunluk'] ?? 0, 3, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['operasyon_toplam'] ?? 0, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($row['toplam'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>Veri bulunamadı</h3>
        <p>Seçilen tarih aralığında gemi boşaltma operasyonu bulunamadı.</p>
        <small>Tarih aralığı: <?= $start_date ?> - <?= $end_date ?></small>
    </div>
    <?php endif; ?>
</div>

<!-- Gemi Log Kayıtları -->
<?php if (!empty($gemilog_results)): ?>
<div class="data-section" style="margin-top: 2rem;">
    <div class="data-header">
        <h3>🚢 Gemi Log Kayıtları</h3>
        <div class="header-actions">
            <span class="data-count"><?= count($gemilog_results) ?> kayıt</span>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gemi Adı</th>
                    <th>Durum</th>
                    <th>Tarih/Saat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gemilog_results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                        <td class="sensor-name"><?= htmlspecialchars($row['gemi_adi'] ?? '') ?></td>
                        <td>
                            <?php if (($row['aktif_yon'] ?? 0) == 1): ?>
                                <span class="status-badge status-active">Aktif</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['tarihsaat'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- İstatistikler -->
    <div style="background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; padding: 1.5rem; margin: 1rem; border-radius: 8px;">
        <h4>📊 Gemi Log İstatistikleri</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div><strong>Toplam Kayıt:</strong> <?= count($gemilog_results) ?></div>
            <div><strong>Aktif Gemiler:</strong> <?= count(array_filter($gemilog_results, function($r) { return ($r['aktif_yon'] ?? 0) == 1; })) ?></div>
            <div><strong>Veri Aralığı:</strong> Son 100 kayıt</div>
            <div><strong>Son Güncelleme:</strong> <?= date('d.m.Y H:i:s') ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Operasyon İstatistikleri -->
<?php if (!empty($data)): ?>
<div style="background: linear-gradient(135deg, #00b894 0%, #00a085 100%); color: white; padding: 1.5rem; margin: 2rem 0; border-radius: 8px;">
    <h4>📊 Gemi Boşaltma İstatistikleri</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <?php
        $total_tonnage = array_sum(array_column($data, 'toplam'));
        $avg_flow = count($data) > 0 ? array_sum(array_column($data, 'debi')) / count($data) : 0;
        $operations_today = count(array_filter($data, function($row) {
            return date('Y-m-d', strtotime($row['okuma_zamani'] ?? '')) === date('Y-m-d');
        }));
        ?>
        <div>
            <strong>Toplam Kayıt:</strong><br>
            <span style="font-size: 1.5em;"><?= count($data) ?></span> adet
        </div>
        <div>
            <strong>Bugünkü Operasyon:</strong><br>
            <span style="font-size: 1.5em;"><?= $operations_today ?></span> adet
        </div>
        <div>
            <strong>Toplam Tonaj:</strong><br>
            <span style="font-size: 1.5em;"><?= number_format($total_tonnage, 0, ',', '.') ?></span> ton
        </div>
        <div>
            <strong>Ortalama Debi:</strong><br>
            <span style="font-size: 1.5em;"><?= number_format($avg_flow, 1, ',', '.') ?></span> T/h
        </div>
    </div>
</div>
<?php endif; ?>