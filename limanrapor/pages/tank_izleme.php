<?php
// Tank İzleme Sayfası

// Tank verilerini çek
try {
    $sql = "SELECT * FROM tank_verileri 
            WHERE tarihsaat >= :start_date AND tarihsaat <= :end_date 
            ORDER BY tarihsaat DESC LIMIT 500";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_date', $start_date . ' 00:00:00');
    $stmt->bindValue(':end_date', $end_date . ' 23:59:59');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tank dashboard için son değerler
    $tank_latest_data = [];
    $latest_sql = "SELECT * FROM tank_verileri WHERE tank IN (1, 2) ORDER BY tarihsaat DESC LIMIT 10";
    $latest_stmt = $pdo->prepare($latest_sql);
    $latest_stmt->execute();
    $all_latest = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ([1, 2] as $tank_num) {
        foreach ($all_latest as $row) {
            if ($row['tank'] == $tank_num) {
                $tank_latest_data[$tank_num] = $row;
                break;
            }
        }
    }
    
} catch(PDOException $e) {
    $data = [];
    $error_message = "Veri çekme hatası: " . $e->getMessage();
}
?>

<!-- Tank Dashboard -->
<?php if (!empty($tank_latest_data)): ?>
<div class="tank-dashboard">
    <div class="tank-dashboard-header">
        <h3>🛢️ Tank Durumu - Anlık Değerler</h3>
    </div>
    <div class="tanks-container">
        <?php foreach ([1, 2] as $tank_num): ?>
            <?php if (isset($tank_latest_data[$tank_num])): ?>
                <?php 
                $tank_data = $tank_latest_data[$tank_num];
                $radar_raw = $tank_data['rdr'] ?? 0;
                $radar_cm = $radar_raw / 10;
                $radar_kg = $tank_data['rdrmetre'] ?? 0;
                $basinc_raw = $tank_data['bsnc'] ?? 0;
                $basinc_bar = $basinc_raw / 100;
                $basinc_kg = $tank_data['bsncmetre'] ?? 0;
                $sicaklik = ($tank_data['pt100'] ?? 0) / 10;
                ?>
                <div class="tank-display tank-<?= $tank_num ?>-display">
                    <div class="tank-title">Tank <?= $tank_num ?></div>
                    <img src="img/tank.png" alt="Tank <?= $tank_num ?>" class="tank-image">
                    <div class="tank-values">
                        <div class="tank-value">
                            <div class="tank-value-label">Radar</div>
                            <div class="tank-value-data"><?= number_format($radar_raw, 0, ',', '.') ?></div>
                        </div>
                        <div class="tank-value">
                            <div class="tank-value-label">Radar (cm)</div>
                            <div class="tank-value-data"><?= number_format($radar_cm, 1, ',', '.') ?> cm</div>
                        </div>
                        <div class="tank-value">
                            <div class="tank-value-label">Radar (kg)</div>
                            <div class="tank-value-data"><?= number_format($radar_kg, 0, ',', '.') ?> kg</div>
                        </div>
                        <div class="tank-value">
                            <div class="tank-value-label">Basınç (bar)</div>
                            <div class="tank-value-data"><?= number_format($basinc_bar, 2, ',', '.') ?> bar</div>
                        </div>
                        <div class="tank-value">
                            <div class="tank-value-label">Basınç (kg)</div>
                            <div class="tank-value-data"><?= number_format($basinc_kg, 0, ',', '.') ?> kg</div>
                        </div>
                        <div class="tank-value">
                            <div class="tank-value-label">Sıcaklık</div>
                            <div class="tank-value-data"><?= number_format($sicaklik, 2, ',', '.') ?> °C</div>
                        </div>
                        <div class="tank-value tank-timestamp-value">
                            <div class="tank-value-label">Son Güncelleme</div>
                            <div class="tank-value-data"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($tank_data['tarihsaat'] ?? ''))) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tank Verileri Tablosu -->
<div class="data-section">
    <div class="data-header">
        <h3>Tank Seviyeleri</h3>
        <div class="header-actions">
            <span class="data-count"><?= count($data) ?> kayıt</span>
        </div>
    </div>
    
    <?php if (!empty($data)): ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tank No</th>
                    <th>Radar</th>
                    <th>Radar (cm)</th>
                    <th>Radar (kg)</th>
                    <th>Sıcaklık (°C)</th>
                    <th>Basınç (bar)</th>
                    <th>Basınç (kg)</th>
                    <th>Tarih/Saat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <?php 
                    $tank_no = $row['tank'] ?? '';
                    $radar_raw = $row['rdr'] ?? 0;
                    $radar_cm = $radar_raw / 10;
                    $radar_kg = $row['rdrmetre'] ?? 0;
                    $basinc_raw = $row['bsnc'] ?? 0;
                    $basinc_bar = $basinc_raw / 100;
                    $basinc_kg = $row['bsncmetre'] ?? 0;
                    $sicaklik = ($row['pt100'] ?? 0) / 10;
                    ?>
                    <tr>
                        <td class="tank-<?= $tank_no ?>">Tank <?= $tank_no ?></td>
                        <td class="amount"><?= number_format($radar_raw, 0, ',', '.') ?></td>
                        <td class="amount"><?= number_format($radar_cm, 1, ',', '.') ?> cm</td>
                        <td class="amount"><?= number_format($radar_kg, 0, ',', '.') ?> kg</td>
                        <td class="amount"><?= number_format($sicaklik, 2, ',', '.') ?> °C</td>
                        <td class="amount"><?= number_format($basinc_bar, 2, ',', '.') ?> bar</td>
                        <td class="amount"><?= number_format($basinc_kg, 0, ',', '.') ?> kg</td>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['tarihsaat'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>Veri bulunamadı</h3>
        <p>Seçilen tarih aralığında tank verisi bulunamadı.</p>
    </div>
    <?php endif; ?>
</div>