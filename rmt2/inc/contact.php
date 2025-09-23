
<div class="contact-hero">
    <div class="hero-content">
        <h1>İletişim</h1>
        <p>Sorularınız için bize ulaşın</p>
    </div>
</div>

<div class="container">
    <section class="contact-section">
        <div class="contact-info">
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Adres</h3>
                <p>Organize Sanayi Bölgesi</p>
                <p>Teknokent, No: X</p>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <h3>Telefon</h3>
                <p>+90 XXX XXX XX XX</p>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <h3>E-posta</h3>
                <p>info@rmtproje.com</p>
            </div>
        </div>
        
        <form class="contact-form" action="submit.php" method="POST">
            <div class="form-group">
                <input type="text" name="name" placeholder="Adınız" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="E-posta Adresiniz" required>
            </div>
            <div class="form-group">
                <textarea name="message" placeholder="Mesajınız" required></textarea>
            </div>
            <button type="submit" class="submit-button">Gönder</button>
        </form>
    </section>
</div>