<?php
include "header.php";
include "conn.php";
include "oturum.php";
include "sidebar.php";
include "navbar.php";
?>

</div>

<div class="container">
  <div class="page-inner">
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
      <div>
        <h3 class="fw-bold mb-3">Panel</h3>
        <h6 class="op-7 mb-2">Cihazlarınız hakkında özet ve görsel bilgileri burada bulabilirsiniz.</h6>
      </div>
      <div class="ms-md-auto py-2 py-md-0">

        <?php
        $mail = $_SESSION['mail']; 
      // Kullanıcıya ait API Key olup olmadığını kontrol et
        $sql = "SELECT id FROM api_keys WHERE user_id = (SELECT id FROM cari WHERE mail = ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
          die("Veritabanı hatası: " . $conn->error);
        }

        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $result = $stmt->get_result();

      // API Key olup olmadığını kontrol et
      $has_api_key = ($result->num_rows > 0); // API Key var mı?
      $stmt->close();
      ?>

      <button class="btn btn-label-info btn-round me-2" 
      <?php echo $has_api_key ? 'disabled="disabled"' : ''; ?>
      onclick="window.location.href='api_create_key.php';">
      <?php echo $has_api_key ? 'API Mevcut' : 'API İste'; ?>
    </button>


    <a href="chzekle.php" class="btn btn-primary btn-round">Cihaz Ekle</a>
  </div>
</div>

<div class="row">
  <div class="col-sm-6 col-md-3">
    <div class="card card-stats card-round">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-icon">
            <div
            class="icon-big text-center icon-primary bubble-shadow-small"
            >
              <!--
              <i class="fas fa-users"></i>
            -->
            <i class="fas fa-id-card-alt"></i>
          </div>
        </div>
        <div class="col col-stats ms-3 ms-sm-0">
          <div class="numbers">
            <p class="card-category">Kullanıcılar</p>
            <h4 class="card-title">1.294</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="col-sm-6 col-md-3">
  <div class="card card-stats card-round">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-icon">
          <div
          class="icon-big text-center icon-info bubble-shadow-small"
          >
          <i class="fas fa-fighter-jet"></i>
        </div>
      </div>
      <div class="col col-stats ms-3 ms-sm-0">
        <div class="numbers">
          <p class="card-category">Aktif Cihazlar</p>
          <h4 class="card-title"><?php echo $cihaz_sayisi ?></h4>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<div class="col-sm-6 col-md-3">
  <div class="card card-stats card-round">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-icon">
          <div
          class="icon-big text-center icon-success bubble-shadow-small"
          >
          <i class="fas fa-helicopter"></i>
        </div>
      </div>
      <div class="col col-stats ms-3 ms-sm-0">
        <div class="numbers">
          <p class="card-category">Pasif Cihazlar</p>
          <h4 class="card-title">$ 1,345</h4>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<div class="col-sm-6 col-md-3">
  <div class="card card-stats card-round">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-icon">
          <div
          class="icon-big text-center icon-secondary bubble-shadow-small"
          >
          <i class="far fa-check-circle"></i>
        </div>
      </div>
      <div class="col col-stats ms-3 ms-sm-0">
        <div class="numbers">
          <p class="card-category">Order</p>
          <h4 class="card-title">576</h4>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<div class="row">
  <div class="col-md-12">

    <div class="card card-round">
      <div class="card-header">
        <div class="card-head-row">
          <div class="card-title">Bu bölüm Hazırlanıyor!</div>
          <div class="card-tools">
            <a
            href="#"
            class="btn btn-label-success btn-round btn-sm me-2"
            >
            <span class="btn-label">
              <i class="fa fa-pencil"></i>
            </span>
            Export
          </a>
          <a href="#" class="btn btn-label-info btn-round btn-sm">
            <span class="btn-label">
              <i class="fa fa-print"></i>
            </span>
            Print
          </a>
        </div>
      </div>
    </div>
    
    <div class="card-body">
      <div class="chart-container" style="min-height: 375px">

        <center>
          <img src="assets/img/under.gif">
        </center>
        <!--
        <canvas id="statisticsChart"></canvas>
      -->

    </div>

    <div id="myChartLegend"></div>
  </div>
</div>
</div>


</div>
<?php  include "footer.php" ?>