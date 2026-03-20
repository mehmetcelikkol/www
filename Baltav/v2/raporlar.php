<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth();
$auth->requireLogin();
require_once __DIR__ . '/includes/header.php';

$baslangic = $_GET['baslangic'] ?? date('Y-m-d');
$bitis = $_GET['bitis'] ?? date('Y-m-d');
$cihaz_id = $_GET['cihaz_id'] ?? '';

$db = Database::getConnection();
$cihazlar = $db->query("SELECT cihaz_kodu, cihaz_adi FROM cihazlar")->fetchAll();

$veriler = [];
if($cihaz_id) {
    $sql = "SELECT * FROM cihaz_paketleri 
            WHERE cihaz_kimligi = ? 
            AND DATE(alinan_zaman) BETWEEN ? AND ? 
            ORDER BY alinan_zaman DESC LIMIT 1000";
    $stmt = $db->prepare($sql);
    $stmt->execute([$cihaz_id, $baslangic, $bitis]);
    $veriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <h2 class="mb-4"><i class="fa-solid fa-file-csv text-success"></i> Tüketim Raporları</h2>
    
    <div class="card border-0 shadow-sm p-4 mb-4">
        <form class="row g-3">
            <div class="col-md-4">
                <label>Cihaz Seçin</label>
                <select name="cihaz_id" class="form-select">
                    <option value="">-- Seçiniz --</option>
                    <?php foreach($cihazlar as $c): ?>
                        <option value="<?php echo $c['cihaz_kodu']; ?>" <?php echo $cihaz_id == $c['cihaz_kodu'] ? 'selected' : ''; ?>>
                            <?php echo $c['cihaz_adi']; ?> (<?php echo $c['cihaz_kodu']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Başlangıç</label>
                <input type="date" name="baslangic" class="form-control" value="<?php echo $baslangic; ?>">
            </div>
            <div class="col-md-3">
                <label>Bitiş</label>
                <input type="date" name="bitis" class="form-control" value="<?php echo $bitis; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-filter"></i> Getir</button>
            </div>
        </form>
    </div>

    <?php if($cihaz_id && !empty($veriler)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Rapor Sonucu (<?php echo count($veriler); ?> Kayıt)</h5>
            <button class="btn btn-success btn-sm" onclick="exportTableToExcel('raporTable', 'Silo_Raporu')">
                <i class="fa-solid fa-file-excel"></i> Excel İndir
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px;">
                <table class="table table-striped mb-0" id="raporTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Paket No</th>
                            <th>Ağırlık (kg)</th>
                            <th>Darbe</th>
                            <th>Stabilite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($veriler as $v): ?>
                        <tr>
                            <td><?php echo $v['alinan_zaman']; ?></td>
                            <td><?php echo $v['paket_no']; ?></td>
                            <td><?php echo $v['agirlik_degeri']; ?></td>
                            <td><?php echo $v['darbeSayisi']; ?></td>
                            <td><?php echo $v['stabil_mi'] ? 'Dengeli' : 'Hareketli'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif($cihaz_id): ?>
        <div class="alert alert-warning">Bu tarih aralığında kayıt bulunamadı.</div>
    <?php endif; ?>
</div>

<script>
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    filename = filename?filename+'.xls':'excel_data.xls';
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
