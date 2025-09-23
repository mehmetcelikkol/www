<div class="hero contact-hero">
    <div class="hero-content">
        <h1>İletişim</h1>
        <p>Sizlere yardımcı olmak için buradayız</p>
    </div>
</div>

<div class="container">
    <div class="contact-grid">
        <div class="contact-info">
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3>Adres</h3>
                    <p>Balıkesir Organize Sanayi Bölgesi</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h3>Telefon</h3>
                    <p>+90 (XXX) XXX XX XX</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>E-posta</h3>
                    <p>info@rmtproje.com</p>
                </div>
            </div>
        </div>

        <div class="contact-form">
            <h2>Bize Ulaşın</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php 
                        foreach ($_SESSION['errors'] as $error) {
                            echo "<li>$error</li>";
                        }
                        unset($_SESSION['errors']);
                        ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/rmtproje/public/iletisim/submit" method="POST"> <!-- contact -> iletisim -->
                <div class="form-group">
                    <input type="text" name="name" placeholder="Adınız" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="E-posta Adresiniz" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Telefon Numaranız">
                </div>
                <div class="form-group">
                    <textarea name="message" placeholder="Mesajınız" required rows="5"></textarea>
                </div>
                <button type="submit" class="cta-button">Gönder</button>
            </form>
        </div>
    </div>
    
    <div class="map-container">
        <div class="map-wrapper">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d785.3043082833441!2d27.87996!3d39.64578!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMznCsDM4JzQ0LjkiTiAyN8KwNTInNDguNSJF!5e0!3m2!1str!2str!4v1625661234567!5m2!1str!2str" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
        <div class="map-actions">
            <a href="https://www.google.com/maps/dir/?api=1&destination=39.645782925107746,27.880151035367" 
               class="direction-button" 
               target="_blank">
                <i class="fas fa-directions"></i> Yol Tarifi Al
            </a>
        </div>
    </div>
</div>