document.addEventListener('DOMContentLoaded', function() {
    // Mobil menü toggle
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if(menuBtn) {
        menuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }

    // Scroll animasyonu
    const scrollBtn = document.querySelector('.scroll-btn');
    if(scrollBtn) {
        scrollBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            targetElement.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Form gönderimi
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('.submit-button');
            const originalText = submitButton.textContent;
            
            // Submit butonunu devre dışı bırak
            submitButton.disabled = true;
            submitButton.textContent = 'Gönderiliyor...';
            
            try {
                const response = await fetch('submit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                // Sonuç mesajını göster
                alert(result.message);
                
                // Başarılıysa formu temizle
                if (result.success) {
                    form.reset();
                }
            } catch (error) {
                alert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
            } finally {
                // Submit butonunu tekrar aktif et
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });

    // Header scroll efekti
    const header = document.querySelector('header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
});