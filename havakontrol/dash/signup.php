<?php
include "header.php";  
include "conn.php";
?>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-7 col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Kayıt Ol</h2>
                        <form action="signup.php" method="post" id="signupForm" onsubmit="return validateForm()">
                            <div class="form-group">
                                <label for="unvan">Şirket Ünvanı</label>
                                <textarea class="form-control" id="unvan" name="unvan" rows="2" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="ad">İsim</label>
                                <input type="text" class="form-control" id="ad" name="ad" placeholder="Adınız" required />
                            </div>

                            <div class="form-group">
                                <label for="soyad">Soyisim</label>
                                <input type="text" class="form-control" id="soyad" name="soyad" placeholder="Soyadınız" required />
                            </div>

                            <div class="form-group">
                                <label for="mail">E-Posta Adresi</label>
                                <input type="email" class="form-control" id="mail" name="mail" placeholder="Ornek@domain.com" required />
                                <small class="form-text text-muted">E-posta adresiniz aynı zamanda kullanıcı adınız olacaktır.</small>
                            </div>

                            <div class="form-group">
                                <label for="gsm">Telefon Numaranız</label>
                                <input type="text" class="form-control" id="gsm" name="gsm" placeholder="05xxxxxxxxx" required pattern="^05\d{9}$" />
                            </div>

                            <div class="form-group">
                                <label for="adres">Adresiniz</label>
                                <textarea class="form-control" id="adres" name="adres" rows="5" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="password">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Şifreniz" required />
                            </div>

                            <div class="form-group">
                                <label for="password2">Şifre (tekrar)</label>
                                <input type="password" class="form-control" id="password2" name="password2" placeholder="Tekrar" required />
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">KAYIT OL</button>
                            </div>
                        </form>
                        <hr>
                        <div class="d-grid">
                            <button class="btn btn-primary">
                                <h6>          
                                    <span class="btn-label">
                                        <i class="fas fa-archway"></i>
                                    </span>
                                    <a href="login.php" target="_blank" class="">Giriş Yap</a> 
                                </h6>
                            </button>
                        </div>

                        <script>
                            function validateForm() {
                                const password = document.getElementById('password').value;
                                const password2 = document.getElementById('password2').value;
                                const mail = document.getElementById('mail').value;
                                const gsm = document.getElementById('gsm').value;

                                if (password !== password2) {
                                    Swal.fire({
                                        title: 'Hata!',
                                        text: 'Şifreler eşleşmiyor!',
                                        icon: 'error',
                                        timer: 3000,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                    return false;
                                }

                                const phonePattern = /^05\d{9}$/;
                                if (!phonePattern.test(gsm)) {
                                    Swal.fire({
                                        title: 'Hata!',
                                        text: 'Lütfen geçerli bir telefon numarası girin (05xxxxxxxxx)!',
                                        icon: 'error',
                                        timer: 3000,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                    return false;
                                }

                                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (!emailPattern.test(mail)) {
                                    Swal.fire({
                                        title: 'Hata!',
                                        text: 'Lütfen geçerli bir e-posta adresi girin!',
                                        icon: 'error',
                                        timer: 3000,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                    return false;
                                }

                                return true;
                            }
                        </script>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == "POST") {
                            $unvan = isset($_POST['unvan']) ? trim($_POST['unvan']) : '';
                            $ad = isset($_POST['ad']) ? trim($_POST['ad']) : '';
                            $soyad = isset($_POST['soyad']) ? trim($_POST['soyad']) : '';
                            $mail = isset($_POST['mail']) ? trim($_POST['mail']) : '';
                            $gsm = isset($_POST['gsm']) ? trim($_POST['gsm']) : '';
                            $adres = isset($_POST['adres']) ? trim($_POST['adres']) : '';
                            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
                            $password2 = isset($_POST['password2']) ? trim($_POST['password2']) : '';

                            if (empty($unvan) || empty($ad) || empty($soyad) || empty($mail) || empty($gsm) || empty($adres) || empty($password) || empty($password2)) {
                                echo "<script>
                                Swal.fire({
                                    title: 'Hata!',
                                    text: 'Lütfen tüm alanları doldurunuz!',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Tamam'
                                });
                                </script>";
                            } elseif ($password !== $password2) {
                                echo "<script>
                                Swal.fire({
                                    title: 'Hata!',
                                    text: 'Şifreler eşleşmiyor!',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Tamam'
                                });
                                </script>";
                            } elseif (!preg_match("/^05\d{9}$/", $gsm)) {
                                echo "<script>
                                Swal.fire({
                                    title: 'Hata!',
                                    text: 'Telefon numarası geçersiz! Lütfen 05xxxxxxxxx formatında giriniz.',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Tamam'
                                });
                                </script>";
                            } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                echo "<script>
                                Swal.fire({
                                    title: 'Hata!',
                                    text: 'Geçersiz e-posta adresi!',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Tamam'
                                });
                                </script>";
                            } else {
                                $sql = "INSERT INTO cari (unvan, ad, soyad, mail, gsm, adres, sifre) VALUES (?, ?, ?, ?, ?, ?, ?)";

                                if ($stmt = $conn->prepare($sql)) {
                                    $stmt->bind_param("sssssss", $unvan, $ad, $soyad, $mail, $gsm, $adres, $password);

                                    if ($stmt->execute()) {
                                        echo "<script>
                                        Swal.fire({
                                            title: 'Başarılı!',
                                            text: 'Kayıt başarılı! Hoşgeldiniz, $ad',
                                            icon: 'success',
                                            timer: 3000,
                                            showConfirmButton: true,
                                            confirmButtonText: 'Tamam'
                                        }).then((result) => {
                                            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                                                window.location.href = 'login.php';
                                            }
                                        });
                                        </script>";
                                    } else {
                                        echo "<script>
                                        Swal.fire({
                                            title: 'Hata!',
                                            text: 'Kayıt sırasında bir hata oluştu!',
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
                                        title: 'Hata!',
                                        text: 'Veritabanı hatası: " . $conn->error . "',
                                        icon: 'error',
                                        timer: 3000,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Tamam'
                                    });
                                    </script>";
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
