        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px; background: rgba(255, 255, 255, 0.95); padding: 12px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                    <img src="assets/img/btv-logo.png" alt="BTV" style="height: 30px; object-fit: contain;">
                    <img src="assets/img/rmt-logo.png" alt="RMT" style="height: 30px; object-fit: contain;">
                </div>
                <h3>Silosense <span style="color:#fff; font-weight:300;">v5</span></h3>
                <div style="font-size:12px; color:#aaa; margin-top:5px;">Tavuk Yemi Takip Sistemi</div>
            </div>
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Özet (Dashboard)</a>
                </li>
                <li>
                    <a href="cihazlar.php"><i class="fa-solid fa-tower-observation"></i> Silolar (Cihazlar)</a>
                </li>
                <?php if($_SESSION['kullanici_rolu'] == 'superadmin' || $_SESSION['kullanici_rolu'] == 'admin'): ?>
                <li>
                    <a href="isletmeciler.php"><i class="fa-solid fa-users"></i> İşletmeciler</a>
                </li>
                <li>
                    <a href="kumesler.php"><i class="fa-solid fa-house-chimney-window"></i> Kümes Yönetimi</a>
                </li>
                <li>
                    <a href="entegreler.php"><i class="fa-solid fa-industry"></i> Entegreler</a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="loglar.php"><i class="fa-solid fa-list-check"></i> İşlem Logları</a>
                </li>
                <li>
                    <a href="ayarlar.php"><i class="fa-solid fa-gears"></i> Ayarlar</a>
                </li>
                <li>
                    <a href="cikis.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a>
                </li>
            </ul>
        </nav>
