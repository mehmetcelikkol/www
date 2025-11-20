<?php
include "header.php";
include "conn.php";

// Eğer oturum başlatılmamışsa oturumu başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eğer oturum zaten açılmışsa index.php'ye yönlendirme
if (isset($_SESSION['mail'])) {
    header("Location: index.php");
    exit();
}
?>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Giriş Yap</h2>
                        <form action="login.php" method="post" id="loginForm">
                            <div class="form-group mb-3">
                                <label for="mail">Kullanıcı Mail:</label>
                                <input type="text" name="mail" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="sifre">Şifre:</label>
                                <input type="password" name="sifre" class="form-control" required>
                            </div>
                            <div class="form-group mb-3 form-check">
                                <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">Beni Hatırla</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Giriş Yap</button>
                            </div>
                        </form>
                        <hr>
                        <div class="d-grid">
                            <button class="btn btn-primary">
                                <h6>
                                    <span class="btn-label">
                                        <i class="fas fa-fingerprint"></i>
                                    </span>
                                    <a href="signup.php" target="_blank" class="">Kayıt Ol</a>
                                </h6>
                            </button>
                        </div>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == "POST") {
                            $mail = trim($_POST['mail']);
                            $sifre = trim($_POST['sifre']);

                            $sql = "SELECT sifre, ad FROM cari WHERE mail = ?";
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("s", $mail);
                                $stmt->execute();
                                $stmt->store_result();

                                if ($stmt->num_rows > 0) {
                                    $stmt->bind_result($storedPassword, $ad);
                                    $stmt->fetch();

                                    if ($sifre === $storedPassword) {
                                        if (session_status() == PHP_SESSION_NONE) {
                                            session_start();
                                        }
                                        $_SESSION['mail'] = $mail;
                                        $_SESSION['ad'] = $ad;

                                        if (isset($_POST['remember_me'])) {
                                            setcookie('mail', $mail, time() + (30 * 24 * 60 * 60), "/");
                                            setcookie('ad', $ad, time() + (30 * 24 * 60 * 60), "/");
                                        }

                                        echo "<script>
                                        Swal.fire({
                                            title: 'Başarılı!',
                                            text: 'Giriş başarılı! Hoşgeldiniz, $ad',
                                            icon: 'success',
                                            timer: 3000,
                                            showConfirmButton: true,
                                            confirmButtonText: 'Tamam'
                                        }).then((result) => {
                                            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                                                window.location.href = 'index.php';
                                            }
                                        });
                                        </script>";
                                    } else {
                                        echo "<script>
                                        Swal.fire({
                                            title: 'Hata!',
                                            text: 'Şifre yanlış!',
                                            icon: 'error',
                                            timer: 3000,
                                            showConfirmButton: true,
                                            confirmButtonText: 'Tamam'
                                        });
                                        </script>";
                                    }
                                } else {
                                    echo "<script>
                                    Swal.fire({
                                        title: 'Hata!',
                                        text: 'Kullanıcı bulunamadı!',
                                        icon: 'error',
                                        timer: 3000,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                    </script>";
                                }
                                $stmt->close();
                            } else {
                                echo "<script>
                                Swal.fire({
                                    title: 'Sistem Hatası!',
                                    text: 'Veritabanı hatası: " . $conn->error . "',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Tamam'
                                });
                                </script>";
                            }
                            $conn->close();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
