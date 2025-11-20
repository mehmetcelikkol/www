<body>

  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="index.php" class="logo">
            <!-- logoyu buradan değiştir -->
            <img
            src="assets/img/logo-1.png"  
            alt="navbar brand"
            class="navbar-brand"
            height="30"
            />
          </a>
          <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
              <i class="gg-menu-right"></i>
            </button>
            <button class="btn btn-toggle sidenav-toggler">
              <i class="gg-menu-left"></i>
            </button>
          </div>
          <button class="topbar-toggler more">
            <i class="gg-more-vertical-alt"></i>
          </button>
        </div>
        <!-- End Logo Header -->
      </div>
      <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
          <ul class="nav nav-secondary">
            <li class="nav-item active">
              <a
              data-bs-toggle="collapse"
              href="#dashboard"
              class="collapsed" 
              aria-expanded="false"
              >
              <i class="fas fa-home"></i>
              <p>Kontrol</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="dashboard">
              <ul class="nav nav-collapse">
                <li>
                  <a href="index.php">
                    <span class="sub-item">Panel</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#sidebarLayouts">
              <i class="fas fa-th-list"></i>
              <p>Cihazlarım</p>

              <?php 
              // Oturumdan e-posta adresini alalım
              $email = $_SESSION['mail'] ?? null; 

              // Kullanıcının cari_id'sini alalım
              $sql = "SELECT id FROM cari WHERE mail = ?";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("s", $email);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($row = $result->fetch_assoc()) {
                $cari_id = $row['id'];
              }

              // Cihaz sayısını almak için sorgu
              $sql = "SELECT COUNT(*) as cihaz_sayisi FROM cihazlar WHERE firmaid = ?";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("i", $cari_id);
              $stmt->execute();
              $result = $stmt->get_result();
              $row = $result->fetch_assoc();
              $cihaz_sayisi = $row['cihaz_sayisi'];
              $stmt->close();
              ?>
              <span class="badge badge-success"><?php echo $cihaz_sayisi ?></span>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="sidebarLayouts">
              <ul class="nav nav-collapse">
                <li>
                  <a href="chzlar.php">
                    <span class="sub-item">Tümü</span>
                  </a>
                </li>

                <?php
                // Kullanıcının cihazlarını alalım
                $sql = "SELECT konum, serino FROM cihazlar WHERE firmaid = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $cari_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // Cihazların her birini menü öğesi olarak ekleyelim
                while ($row = $result->fetch_assoc()) {
                  $serino = htmlspecialchars($row['serino']);
                  $konum = htmlspecialchars($row['konum']);

                  echo '<li><a href="verioku.php?serino=' . $serino . '"><span class="sub-item">' . $konum . '</span></a></li>';
                }

                $stmt->close();
                ?>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- End Sidebar -->
</body>
