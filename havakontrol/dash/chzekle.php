<?php
// Ek kodları koruyoruz
include "header.php";
include "conn.php";
include "oturum.php";
include "sidebar.php";
include "navbar.php";
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

<body>
    <div class="scroll-container">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-head-bg-primary mt-4">
                                <thead>
                                    <tr>
                                        <th scope="col">Sıra No</th>
                                        <th scope="col">Kimlik</th>
                                        <th scope="col">Konum</th>
                                        <th scope="col">Kayıt Tarihi</th>   
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    $sql = "SELECT konum, serino, kayit_tarihi FROM cihazlar WHERE firmaid = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $cari_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    $siraNo = 1;

                                    while ($row = $result->fetch_assoc()) {
                                        $serino = htmlspecialchars($row['serino']);
                                        $konum = htmlspecialchars($row['konum']);
                                        $kayit_tarihi = htmlspecialchars($row['kayit_tarihi']);

                                        echo '<tr><td>' . $siraNo . '</td><td>' . $serino . '</td><td>' . $konum . '</td><td>' . $kayit_tarihi . '</td></tr>';
                                        $siraNo++;
                                    }

                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-7 col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="text-center mb-4">Yeni Cihaz Ekle</h2>
                            <form action="" method="post" id="signupForm">
                                <div class="form-group">
                                    <label for="id">Kimlik</label>
                                    <input type="text" class="form-control" id="id" name="id" placeholder="Cihazın ekranında görünen kimlik" required />
                                </div>

                                <div class="form-group">
                                    <label for="konum">Konum</label>
                                    <input type="text" class="form-control" id="konum" name="konum" placeholder="Cihaz nerede çalışacak?" required />
                                </div>

                                <div class="form-group">
                                    <label for="kod1dk">Doğrulama Kodu</label>
                                    <input type="text" class="form-control" id="kod1dk" name="kod1dk" placeholder="Cihaz ekranındaki doğrulama kodunu girin" required />
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">KAYIT ET</button>
                                </div>
                            </form>

                            <?php
                            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                                $kimlik = isset($_POST['id']) ? trim($_POST['id']) : '';
                                $konum = isset($_POST['konum']) ? trim($_POST['konum']) : '';
                            $kod1dk = isset($_POST['kod1dk']) ? (int)trim($_POST['kod1dk']) : 0; // int'e dönüştürüyoruz

                            if (empty($kimlik) || empty($konum) || $kod1dk == 0) {
                                echo "<div class='alert alert-danger mt-3'>Lütfen tüm alanları doldurun!</div>";
                            } else {
                                // Cihazın daha önce kayıtlı olup olmadığını kontrol et
                                $sqlCihaz = "SELECT COUNT(*) FROM cihazlar WHERE serino = ?";
                                $stmtCihaz = $conn->prepare($sqlCihaz);
                                $stmtCihaz->bind_param("s", $kimlik);
                                $stmtCihaz->execute();
                                $stmtCihaz->bind_result($cihazSayisi);
                                $stmtCihaz->fetch();
                                $stmtCihaz->close();

                                // Eğer cihaz zaten kayıtlıysa
                                if ($cihazSayisi > 0) {
                                    // Cihazın bu kullanıcıya ait olup olmadığını kontrol et
                                    $sqlUserCheck = "SELECT COUNT(*) FROM cihazlar WHERE serino = ? AND firmaid = ?";
                                    $stmtUserCheck = $conn->prepare($sqlUserCheck);
                                    $stmtUserCheck->bind_param("si", $kimlik, $cari_id);
                                    $stmtUserCheck->execute();
                                    $stmtUserCheck->bind_result($userCihazSayisi);
                                    $stmtUserCheck->fetch();
                                    $stmtUserCheck->close();

                                    // Eğer cihaz bu kullanıcıya ait ise
                                    if ($userCihazSayisi > 0) {
                                        echo "<script>alert('Bu cihazı zaten kaydettiniz.');</script>";
                                    } else {
                                        echo "<script>alert('Bu cihaz daha önce kayıt edilmiş. Her cihaz bir kullanıcı da kayıtlı olabilir.');</script>";
                                    }
                                } else {
                                    // En yeni kodu kontrol et
                                    $sql = "SELECT kod1dk FROM veriler WHERE serino = ? ORDER BY id DESC LIMIT 1";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("s", $kimlik);
                                    $stmt->execute();
                                    $stmt->bind_result($dbKod1dk);
                                    $stmt->fetch();
                                    $stmt->close();

                                    // Eğer serino bulunamadıysa
                                    if (is_null($dbKod1dk)) {
                                        echo "<script>alert('Cihazınız henüz hiç internete bağlanmamış görünüyor. Önce cihazınızın WiFi ayarlarını yapıp internete çıktığından emin olunuz.');</script>";
                                    } else {
                                        // Hata ayıklama: Kodları ekrana yazdır
                                      //buraya bir el atalım =  echo "<div class='alert alert-info mt-3'>Girilen Kod: $kod1dk, Veritabanı Kodu: $dbKod1dk</div>";

                                        if ($kod1dk === (int)$dbKod1dk) { // Burada da karşılaştırmayı int yapıyoruz
                                            // Yeni cihaz ekle
                                            $stmt = $conn->prepare("INSERT INTO cihazlar (serino, konum, firmaid) VALUES (?, ?, ?)");
                                            $stmt->bind_param("ssi", $kimlik, $konum, $cari_id);

                                            if ($stmt->execute()) {
                                                echo "<div class='alert alert-success mt-3'>Cihaz başarıyla kaydedildi!</div>";
                                            } else {
                                                echo "<div class='alert alert-danger mt-3'>Cihaz kaydedilirken bir hata oluştu!</div>";
                                            }

                                            $stmt->close();
                                        } else {
                                            echo "<div class='alert alert-danger mt-3'>Doğrulama kodu yanlış!</div>";
                                        }
                                    }
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>

<?php 
include "footer.php"; 
?>
