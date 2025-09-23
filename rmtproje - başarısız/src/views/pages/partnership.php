<div class="hero partnership-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>İş Ortaklığı Programı</h1>
        <p>"Sen Montajını Yap, Otomasyonu Bizden Al"</p>
        <a href="#basvuru-formu" class="cta-button scroll-btn">
            <i class="fas fa-chevron-down"></i> Başvuru Formuna Git
        </a>
    </div>
</div>

<div class="partnership-page">
    <div class="container">
        <!-- Başvuru Formu -->
        <section id="basvuru-formu" class="partnership-form-section">
            <h2>İş Ortağımız Olun</h2>
            <p class="section-subtitle">Formu doldurun, size özel çözümlerimizi anlatalım.</p>
            
            <form class="partnership-form" action="/rmtproje/public/is-ortakligi/submit" method="POST">
                <div class="form-group">
                    <label for="company">Firma/İşletme Adı</label>
                    <input type="text" id="company" name="company" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Yetkili Adı Soyadı</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="city">Şehir</label>
                    <input type="text" id="city" name="city" required>
                </div>

                <div class="form-group">
                    <label for="message">Mesajınız</label>
                    <textarea id="message" name="message" rows="4" placeholder="Hangi tip projelerde işbirliği yapmak istediğinizi kısaca anlatın..."></textarea>
                </div>

                <button type="submit" class="submit-button">
                    <i class="fas fa-paper-plane"></i> Başvuru Yap
                </button>
            </form>
        </section>

        <!-- İş Ortaklığı Detayları -->
        <section class="partnership-details">
            <!-- Ana sayfadaki iş ortaklığı içeriği buraya gelecek -->
            <?php include __DIR__ . '/home-partnership.php'; ?>
        </section>
    </div>
</div>