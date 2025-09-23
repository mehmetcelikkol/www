document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    menuToggle.addEventListener('click', function() {
        navLinks.classList.toggle('active');
    });

    // Sayfa scroll olduğunda menüyü kapat
    window.addEventListener('scroll', function() {
        if (navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
        }
    });

    // Preloader
    window.addEventListener('load', function() {
        const preloader = document.querySelector('.preloader');
        preloader.classList.add('fade-out');
        
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    });

    // Referans verilerini tanımlayalım
    const referenceData = {
        'ref2024-1': {
            title: 'Proje Başlığı',
            date: 'Ocak 2024',
            location: 'Balıkesir',
            description: 'Proje detaylı açıklaması...',
            features: [
                'PLC Programlama',
                'SCADA Sistemi',
                'HMI Tasarımı'
            ],
            images: [
                '/rmtproje/public/assets/images/references/2024/ref1-detail1.jpg',
                '/rmtproje/public/assets/images/references/2024/ref1-detail2.jpg'
            ]
        }
        // Diğer referanslar için benzer objeler...
    };

    // Modal işlemleri
    const modal = document.getElementById('referenceModal');
    const closeBtn = document.querySelector('.close-modal');
    let currentSlide = 0;

    // Referans kutularına tıklama
    document.querySelectorAll('.reference-logo').forEach(ref => {
        ref.addEventListener('click', function(e) {
            e.preventDefault();
            const refId = this.dataset.refId;
            const data = referenceData[refId];
            
            if (data) {
                showModal(data);
            }
        });
    });

    // Modal kapatma
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Modal içeriğini göster
    function showModal(data) {
        document.querySelector('.modal-title').textContent = data.title;
        document.querySelector('.project-date').textContent = data.date;
        document.querySelector('.project-location').textContent = data.location;
        document.querySelector('.project-description').textContent = data.description;
        
        // Özellikleri listele
        const featuresList = document.querySelector('.project-features');
        featuresList.innerHTML = data.features.map(feature => 
            `<li>${feature}</li>`
        ).join('');

        // Görselleri yükle
        loadImages(data.images);
        
        modal.style.display = "block";
    }

    // Görselleri yükle fonksiyonu
    function loadImages(images) {
        const slider = document.querySelector('.image-slider');
        slider.innerHTML = '';
        
        images.forEach((src, index) => {
            const img = document.createElement('img');
            img.src = src;
            img.style.transform = `translateX(${index * 100}%)`;
            slider.appendChild(img);
        });
    }

    // Slider navigasyonu
    document.querySelector('.prev-slide').onclick = () => changeSlide(-1);
    document.querySelector('.next-slide').onclick = () => changeSlide(1);

    function changeSlide(direction) {
        const slider = document.querySelector('.image-slider');
        const images = slider.querySelectorAll('img');
        currentSlide = (currentSlide + direction + images.length) % images.length;
        
        slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    }

    // Smooth scroll for partnership form
    document.querySelector('.scroll-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const formSection = document.querySelector('#basvuru-formu');
        const headerHeight = document.querySelector('header').offsetHeight;
        const offset = 20; // Extra boşluk
        
        window.scrollTo({
            top: formSection.offsetTop - headerHeight - offset,
            behavior: 'smooth'
        });
    });
});