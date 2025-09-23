<?php
// Gemi Boşaltma Sayfası - Toplam Operasyonlar

// Gemi boşaltma toplam verilerini çek
try {
    $sql = "SELECT * FROM flowveri 
            WHERE sensor_adi IN ('gflow1', 'gflow2') 
            AND okuma_zamani >= :start_date AND okuma_zamani <= :end_date
            GROUP BY sensor_adi, DATE(okuma_zamani)
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
    $data = [];
    $gemilog_results = [];
    $error_message = "Veri çekme hatası: " . $e->getMessage();
}
?>

<?php if (isset($error_message)): ?>
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
        </div>
    </div>
    
    <?php if (!empty($data)): ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rıhtım</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th>Süre</th>
                    <th>Ort. Debi (Ton/h)</th>
                    <th>Maks. Debi (Ton/h)</th>
                    <th>Toplam (Ton)</th>
                    <th>Okuma Sayısı</th>
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
                    
                    // Süre hesaplama (örnek değerler)
                    $sure_text = "2:30:45"; // Bu değer gerçek veriden hesaplanmalı
                    $ortalama_debi = $row['debi'] ?? 0;
                    $maksimum_debi = $row['debi'] ?? 0; // Max değer ayrı hesaplanmalı
                    $toplam_ton = $row['toplam'] ?? 0;
                    ?>
                    <tr>
                        <td class="sensor-name"><?= htmlspecialchars($rihtim_adi) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['okuma_zamani'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['okuma_zamani'] ?? ''))) ?></td>
                        <td><?= $sure_text ?></td>
                        <td class="amount"><?= number_format($ortalama_debi, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($maksimum_debi, 2, ',', '.') ?></td>
                        <td class="amount"><?= number_format($toplam_ton, 2, ',', '.') ?></td>
                        <td class="amount">1</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>Veri bulunamadı</h3>
        <p>Seçilen tarih aralığında gemi boşaltma operasyonu bulunamadı.</p>
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
                    <th>Aktif Yön</th>
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
    <div style="background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); color: white; padding: 1rem; margin: 1rem;">
        <h4>📊 Gemi Log İstatistikleri</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
            <div><strong>Toplam Kayıt:</strong> <?= count($gemilog_results) ?></div>
            <div><strong>Aktif Gemiler:</strong> <?= count(array_filter($gemilog_results, function($r) { return ($r['aktif_yon'] ?? 0) == 1; })) ?></div>
            <div><strong>Veri Aralığı:</strong> Son 100 kayıt</div>
        </div>
    </div>
</div>
<?php endif; ?>