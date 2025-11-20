<?php
include "header.php";
include "conn.php";
include "oturum.php";
include "sidebar.php";
include "navbar.php";

// Zaman filtresini al
$timeFilter = $_GET['filter'] ?? 'all';

// Başlangıç zamanını kaydet
$start_time = microtime(true);

// Oturumdan e-posta adresini alalım
$email = $_SESSION['mail'] ?? null;

if (!$email) {
	echo "E-posta adresi bulunamadı.";
	exit();
}

// Sorgu zaman aşımı süresini ayarlayalım
// $conn->query("SET SESSION MAX_EXECUTION_TIME=90000");

// E-posta adresine göre cari_id'yi almak için sorgu
$sql = "SELECT id FROM cari WHERE mail = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
	echo "Sorgu hazırlama hatası: " . $conn->error;
	exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$cari_id = null;
if ($row = $result->fetch_assoc()) {
	$cari_id = $row['id'];
} else {
	echo "Cari ID bulunamadı.";
	exit();
}

$stmt->close();

// Ana sorgu
$sql = "
SELECT 
c.id, 
c.serino, 
c.konum,
latest.kayit_tarihi,
latest.temp,
latest.hum,
stats.min_temp,
stats.max_temp,
stats.avg_temp,
stats.min_hum,
stats.max_hum,
stats.avg_hum
FROM cihazlar c
LEFT JOIN (
    SELECT serino, temp, hum, kayit_tarihi
    FROM veriler v1
    WHERE kayit_tarihi = (
        SELECT MAX(kayit_tarihi)
        FROM veriler v2
        WHERE v1.serino = v2.serino
    )
) latest ON c.serino = latest.serino
LEFT JOIN (
    SELECT 
        serino,
        MIN(temp) as min_temp,
        MAX(temp) as max_temp,
        ROUND(AVG(temp), 1) as avg_temp,
        MIN(hum) as min_hum,
        MAX(hum) as max_hum,
        ROUND(AVG(hum), 1) as avg_hum
    FROM veriler
    WHERE kayit_tarihi >= CASE ? 
        WHEN 'hour' THEN DATE_SUB(NOW(), INTERVAL 1 HOUR)
        WHEN 'today' THEN CURDATE()
        WHEN 'week' THEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        WHEN 'month' THEN DATE_FORMAT(NOW(), '%Y-%m-01')
        WHEN 'year' THEN DATE_FORMAT(NOW(), '%Y-01-01')
        ELSE '1970-01-01'
    END
    GROUP BY serino
) stats ON c.serino = stats.serino
WHERE c.firmaid = ?
ORDER BY latest.kayit_tarihi DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
	echo "Sorgu hazırlama hatası: " . $conn->error;
	exit();
}

$stmt->bind_param("si", $timeFilter, $cari_id);
//$stmt->bind_param("is", $cari_id, $timeFilter);
$stmt->execute();
$result = $stmt->get_result();

$cards = [];
while ($row = $result->fetch_assoc()) {
	$cards[] = $row;
}

$stmt->close();

$end_time = microtime(true);
$execution_time = $end_time - $start_time;
?>

<style>
	body, html {
		height: 100%;
		margin: 0;
		padding: 0;
		overflow: hidden;
	}
	.scroll-container {
		height: calc(100vh - 60px);
		overflow-y: auto;
		padding: 20px;
	}
	.time-filter {
		margin-bottom: 20px;
	}
	.col-md-3 {
		margin-bottom: 20px;
	}
	.card-pricing2 {
		margin: 0 10px;
	}
	.row {
		margin: 0 -10px;
	}
</style>


<div class="scroll-container">
	<div class="card-body">
		<div class="time-filter">
			<select id="timeFilter" class="form-control" onchange="updateStats(this.value)">
				<option value="all" <?php echo $timeFilter == 'all' ? 'selected' : ''; ?>>Tüm Zamanlar</option>
				<option value="year" <?php echo $timeFilter == 'year' ? 'selected' : ''; ?>>Bu Yıl</option>
				<option value="month" <?php echo $timeFilter == 'month' ? 'selected' : ''; ?>>Bu Ay</option>
				<option value="week" <?php echo $timeFilter == 'week' ? 'selected' : ''; ?>>Bu Hafta</option>
				<option value="today" <?php echo $timeFilter == 'today' ? 'selected' : ''; ?>>Bugün</option>
				<option value="hour" <?php echo $timeFilter == 'hour' ? 'selected' : ''; ?>>Bu Saat</option>
			</select>
		</div>

		<h3 class="fw-bold mb-3">Cihazların Önizlemesi:</h3>
		<div class="row">
			<?php foreach ($cards as $card): ?>
				<div class="col-md-3">
					<div class="card-pricing2 card-primary">
						<div class="pricing-header">
							<h3 class="fw-bold mb-3"><?php echo htmlspecialchars($card['konum']); ?></h3>
							<span class="sub-title"><strong>Kimlik: </strong><?php echo htmlspecialchars($card['serino']); ?></span>
						
						</div>
						<div class="price-value">
							<div class="value">
								<span class="currency"><h5><sup>o</sup>C</h5></span>
								<span class="amount"><h3><?php echo htmlspecialchars($card['temp']); ?></h3></span>
								<span class="amount"><span><h3>% <?php echo htmlspecialchars($card['hum']); ?></h3></span></span>

							</div>
						</div>
						<ul class="pricing-content">
							<?php 
							$kayit_tarihi = new DateTime($card['kayit_tarihi']);
							$now = new DateTime();
							$fark = $kayit_tarihi->diff($now);

    // Toplam dakika farkını hesapla
							$total_minutes = ($fark->days * 24 * 60) + ($fark->h * 60) + $fark->i;

    // 30 dakikadan fazla ise disable class'ı ekle
							$li_class = $total_minutes > 30 ? 'disable' : '';
							?>
							<li class="<?php echo $li_class; ?>">
								<p>
									<strong>Son Kayıt:</strong> 
									<?php 
									echo htmlspecialchars($card['kayit_tarihi']); 
									
									?>
								</p>
								<p>
									<?php 
									echo "  ";
									if ($total_minutes < 5) {
										echo "şimdi";
									} else {
										if ($fark->d > 0) {
											echo $fark->d . " gün ";
										}
										if ($fark->h > 0) {
											echo $fark->h . " saat ";
										}
										if ($fark->i > 0) {
											echo $fark->i . " dakika ";
										}
										echo "önce";
									}
									echo " ";
									?>
								</p>
							</li>
							<li><strong>Min Sıcaklık:</strong> <?php echo number_format($card['min_temp'], 1); ?> °C</li>
							<li><strong>Max Sıcaklık:</strong> <?php echo number_format($card['max_temp'], 1); ?> °C</li>
							<li><strong>Ort. Sıcaklık:</strong> <?php echo number_format($card['avg_temp'], 1); ?> °C</li>
							<li><strong>Min Nem:</strong> <?php echo number_format($card['min_hum'], 1); ?> %</li>
							<li><strong>Max Nem:</strong> <?php echo number_format($card['max_hum'], 1); ?> %</li>
							<li><strong>Ort. Nem:</strong> <?php echo number_format($card['avg_hum'], 1); ?> %</li>
						</ul>
						<a href="verioku.php?serino=<?php echo $card['serino']; ?>" class="btn btn-primary btn-border btn-lg w-75 fw-bold mb-3">Cihazı Göster</a>
						<a href="setle.php?serino=<?php echo $card['serino']; ?>" class="btn btn-success btn-border btn-lg w-75 fw-bold mb-3">Sınırlamalar</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="container mt-4">
		<p align="center" style="color:#f2f4f5"><?php echo number_format($execution_time, 4); ?></p>
	</div>
</div>



<script>
	function updateStats(timeFilter) {
		window.location.href = 'chzlar.php?filter=' + timeFilter;
	}
</script>

<?php include "footer.php" ?>
