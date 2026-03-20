<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fa-solid fa-layer-group"></i> SiloSense</h3>
    </div>

    <ul class="list-unstyled components">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <a href="index.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
        </li>

        <li>
            <a href="#gostergeSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fa-solid fa-eye me-2"></i> Gösterge
            </a>
            <ul class="collapse list-unstyled" id="gostergeSubmenu">
                <li><a href="index.php">📍 Tüm Cihazlar</a></li>
                <?php foreach($cihazlar_menu as $c): ?>
                    <li>
                        <a href="cihaz.php?id=<?php echo $c['cihaz_kimligi']; ?>">
                            🔹 <?php echo htmlspecialchars($c['cihaz_adi'] ?? $c['cihaz_kimligi']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>

        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'istatistik.php' ? 'active' : ''; ?>">
            <a href="istatistik.php"><i class="fa-solid fa-chart-line me-2"></i> İstatistikler</a>
        </li>

        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
        <li class="sidebar-divider">YÖNETİM</li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'entegreler.php' ? 'active' : ''; ?>">
            <a href="entegreler.php"><i class="fa-solid fa-building me-2"></i> Entegreler</a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'bayiler.php' ? 'active' : ''; ?>">
            <a href="bayiler.php"><i class="fa-solid fa-store me-2"></i> Bayiler</a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'kullanicilar.php' ? 'active' : ''; ?>">
            <a href="kullanicilar.php"><i class="fa-solid fa-users me-2"></i> Kullanıcılar</a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'ayarlar.php' ? 'active' : ''; ?>">
            <a href="ayarlar.php"><i class="fa-solid fa-gears me-2"></i> Ayarlar</a>
        </li>
        <?php endif; ?>
        
        <li class="sidebar-divider">HESAP</li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
            <a href="profil.php"><i class="fa-solid fa-user-gear me-2"></i> Profilim</a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-danger w-100"><i class="fa-solid fa-power-off"></i> Çıkış</a>
    </div>
</nav>
