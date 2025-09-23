// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle mobile menu
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger menu
            navToggle.classList.toggle('active');
        });
    }

    // Close mobile menu when clicking on a link
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        }
    });
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const headerHeight = document.querySelector('.header').offsetHeight;
            const targetPosition = target.offsetTop - headerHeight;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Active navigation link highlighting
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    const headerHeight = document.querySelector('.header').offsetHeight;
    
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop - headerHeight - 100;
        const sectionHeight = section.clientHeight;
        
        if (window.pageYOffset >= sectionTop && window.pageYOffset < sectionTop + sectionHeight) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});

// Header background change on scroll
window.addEventListener('scroll', function() {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.style.background = 'rgba(255, 255, 255, 0.98)';
        header.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.15)';
    } else {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
        header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    }
});

// Contact form handling
const contactForm = document.getElementById('contactForm');
if(contactForm){
    contactForm.addEventListener('submit', async function(e){
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Gönderiliyor...';
        const formData = new FormData(this);
        try {
            const res = await fetch(this.action, { method: 'POST', body: formData });
            const data = await res.json();
            if(data.success){
                submitBtn.textContent = 'Gönderildi';
                this.reset();
            } else {
                submitBtn.textContent = 'Tekrar Dene';
                alert(data.message || 'Gönderim başarısız');
            }
        } catch(err){
            submitBtn.textContent = 'Tekrar Dene';
            alert('Sunucu hatası veya bağlantı sorunu.');
        } finally {
            setTimeout(()=>{ submitBtn.disabled=false; submitBtn.textContent = originalText; }, 2500);
        }
    });
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.service-card, .project-card, .highlight-item');
    
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Statistics counter animation
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            start = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(start);
    }, 16);
}

// Trigger counter animation when stats section is visible
const statsObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counters = entry.target.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                if (!isNaN(target)) {
                    animateCounter(counter, target);
                }
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.addEventListener('DOMContentLoaded', function() {
    const statsSection = document.querySelector('.success-stats');
    if (statsSection) {
        statsObserver.observe(statsSection);
    }
});

// Typing effect for hero title (optional enhancement)
function typeWriter(element, text, speed = 100) {
    let i = 0;
    element.innerHTML = '';
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Parallax effect for hero background
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const heroBackground = document.querySelector('.hero-bg');
    
    if (heroBackground) {
        const speed = scrolled * 0.5;
        heroBackground.style.transform = `translateY(${speed}px)`;
    }
});

// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => {
        imageObserver.observe(img);
    });
});

// Back to top button (optional)
function createBackToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '<i class="fas fa-arrow-up"></i>';
    button.className = 'back-to-top';
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2196F3, #1976D2);
        color: white;
        border: none;
        box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
    `;
    
    document.body.appendChild(button);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            button.style.opacity = '1';
            button.style.visibility = 'visible';
        } else {
            button.style.opacity = '0';
            button.style.visibility = 'hidden';
        }
    });
    
    // Scroll to top when clicked
    button.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Hover effect
    button.addEventListener('mouseenter', function() {
        button.style.transform = 'translateY(-3px)';
        button.style.boxShadow = '0 8px 25px rgba(33, 150, 243, 0.4)';
    });
    
    button.addEventListener('mouseleave', function() {
        button.style.transform = 'translateY(0)';
        button.style.boxShadow = '0 4px 15px rgba(33, 150, 243, 0.3)';
    });
}

// Initialize back to top button
document.addEventListener('DOMContentLoaded', createBackToTopButton);

// Project Gallery System
document.addEventListener('DOMContentLoaded', function() {
    const projectData = {
        'ford-otosan': {
            title: 'Ford Otosan Safety PLC - İstanbul',
            media: [
                { type: 'image', src: 'images/projects/ford-otosan/Ford-1.jpg', alt: 'Ford Otosan Safety PLC Proje 1' },
                { type: 'image', src: 'images/projects/ford-otosan/Ford-2.jpg', alt: 'Ford Otosan Safety PLC Proje 2' },
                { type: 'image', src: 'images/projects/ford-otosan/Ford-3.jpg', alt: 'Ford Otosan Safety PLC Proje 3' }
            ]
        },
        'saglam-metal': {
            title: 'Sağlam Metal - Gebze',
            media: [
                { type: 'image', src: 'images/projects/saglam-metal/Saglam-1.jpg', alt: 'Sağlam Metal Proje' },
                { 
                    type: 'video', 
                    src: 'images/projects/saglam-metal/Saglam-2.mp4', 
                    alt: 'Sağlam Metal Video 1',
                    poster: 'images/projects/saglam-metal/Saglam-1.jpg',
                    size: '140MB' 
                },
                { 
                    type: 'video', 
                    src: 'images/projects/saglam-metal/Saglam-3.mp4', 
                    alt: 'Sağlam Metal Video 2',
                    poster: 'images/projects/saglam-metal/Saglam-1.jpg',
                    size: '20MB' 
                },
                { 
                    type: 'video', 
                    src: 'images/projects/saglam-metal/Saglam-4.mp4', 
                    alt: 'Sağlam Metal Video 3',
                    poster: 'images/projects/saglam-metal/Saglam-1.jpg',
                    size: '106MB' 
                }
            ]
        },
        'tpi-composite': {
            title: 'TPI Composite Safety PLC - İzmir',
            media: [
                { type: 'image', src: 'images/projects/tpi-composite/Tpi-1.jpg', alt: 'TPI Composite Safety PLC Proje 1' },
                { type: 'image', src: 'images/projects/tpi-composite/Tpi-2.jpg', alt: 'TPI Composite Safety PLC Proje 2' },
                { type: 'image', src: 'images/projects/tpi-composite/Tpi-3.jpg', alt: 'TPI Composite Safety PLC Proje 3' }
            ]
        },
        'hasun': {
            title: 'Hasun A.Ş',
            media: [
                { type: 'image', src: 'images/projects/hasun/Hasun-1.jpg', alt: 'Hasun Proje 1' },
                { type: 'image', src: 'images/projects/hasun/Hasun-2.jpg', alt: 'Hasun Proje 2' }
            ]
        },
        'celebi-limani': {
            title: 'Çelebi Limanı - Bandırma',
            media: [
                { type: 'image', src: 'images/projects/celebi-limani/Celebi-1.jpg', alt: 'Çelebi Limanı Proje 1' },
                { type: 'image', src: 'images/projects/celebi-limani/Celebi-2.jpg', alt: 'Çelebi Limanı Proje 2' },
                { type: 'image', src: 'images/projects/celebi-limani/Celebi-3.jpg', alt: 'Çelebi Limanı Proje 3' },
                { type: 'image', src: 'images/projects/celebi-limani/Celebi-4.jpg', alt: 'Çelebi Limanı Proje 4' },
                { type: 'image', src: 'images/projects/celebi-limani/Celebi-5.jpg', alt: 'Çelebi Limanı Proje 5' }
            ]
        }
    };

    let currentProject = null;
    let currentMediaIndex = 0;

    const modal = document.getElementById('projectModal');
    const modalTitle = document.getElementById('modalTitle');
    const mainMedia = document.getElementById('mainMedia');
    const thumbnails = document.getElementById('thumbnails');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const closeBtn = document.querySelector('.modal-close');

    // Project card click handlers
    document.querySelectorAll('.project-card').forEach(card => {
        const galleryBtn = card.querySelector('.gallery-btn');
        if (galleryBtn) {
            galleryBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const projectId = card.getAttribute('data-project');
                openGallery(projectId);
            });
        }
    });

    function openGallery(projectId) {
        console.log('Gallery açılıyor, proje ID:', projectId);
        
        currentProject = projectData[projectId];
        if (!currentProject) {
            console.error('Proje bulunamadı:', projectId);
            return;
        }
        
        console.log('Proje:', currentProject.title, 'medya sayısı:', currentProject.media.length);

        currentMediaIndex = 0;
        modalTitle.textContent = currentProject.title;
        
        createThumbnails();
        showMedia(0);
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function createThumbnails() {
        thumbnails.innerHTML = '';
        currentProject.media.forEach((media, index) => {
            const thumb = document.createElement('div');
            thumb.className = 'thumbnail';
            thumb.addEventListener('click', () => showMedia(index));

            if (media.type === 'video') {
                thumb.classList.add('video');
                const video = document.createElement('video');
                video.src = media.src;
                video.muted = true;
                thumb.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = media.src;
                img.alt = media.alt;
                thumb.appendChild(img);
            }

            thumbnails.appendChild(thumb);
        });
    }

    function showMedia(index) {
        if (!currentProject) return;

        console.log('Medya gösteriliyor, index:', index);
        
        currentMediaIndex = index;
        const media = currentProject.media[index];
        
        console.log('Gösterilen medya:', media.src, 'tip:', media.type);
        
        // Update thumbnails
        document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });

        // Show main media
        mainMedia.innerHTML = '';
        
        if (media.type === 'video') {
            console.log('Video element oluşturuluyor...');
            
            const video = document.createElement('video');
            video.controls = true;
            video.muted = true;
            video.preload = 'none'; // Sadece kullanıcı tıklayınca yükle
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            
            // Poster ekle
            if (media.poster) {
                video.poster = media.poster;
            }
            
            // Set video attributes for better compatibility
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            
            // Video boyut uyarısı ekle
            if (media.size) {
                const warningDiv = document.createElement('div');
                warningDiv.style.cssText = 'position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; z-index: 10;';
                warningDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${media.size}`;
                
                const mediaContainer = document.createElement('div');
                mediaContainer.style.position = 'relative';
                mediaContainer.style.width = '100%';
                mediaContainer.style.height = '100%';
                mediaContainer.appendChild(warningDiv);
                mediaContainer.appendChild(video);
                
                mainMedia.appendChild(mediaContainer);
            } else {
                mainMedia.appendChild(video);
            }
            
            // Add loading indicator that shows when user clicks play
            video.addEventListener('loadstart', function() {
                console.log('Video yüklenmeye başladı:', media.src);
                if (media.size) {
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'video-loading';
                    loadingDiv.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 15px 20px; border-radius: 8px; z-index: 20; text-align: center;';
                    loadingDiv.innerHTML = `
                        <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                        <div>Video yükleniyor...</div>
                        <small style="opacity: 0.8;">${media.size}</small>
                    `;
                    video.parentElement.appendChild(loadingDiv);
                }
            });
            
            video.addEventListener('canplay', function() {
                console.log('Video oynatılmaya hazır');
                const loadingDiv = video.parentElement.querySelector('.video-loading');
                if (loadingDiv) {
                    loadingDiv.remove();
                }
            });
            
            // Add error handling
            video.onerror = function(e) {
                console.error('Video yükleme hatası:', media.src, e);
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f5f5f5; color: #666; flex-direction: column; padding: 20px; text-align: center; z-index: 30;';
                errorDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #ff9800;"></i>
                    <h4 style="margin: 0 0 0.5rem 0;">Video Yüklenemiyor</h4>
                    <p style="margin: 0.5rem 0; color: #999;">Dosya boyutu çok büyük veya format desteklenmiyor</p>
                    <small style="color: #999; word-break: break-all; margin-bottom: 1rem;">${media.src}</small>
                    <div style="background: rgba(255,152,0,0.1); padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                        <strong>Dosya Boyutu: ${media.size || 'Büyük'}</strong><br>
                        <small>Büyük video dosyaları yavaş bağlantılarda yüklenmeyebilir</small>
                    </div>
                    <button onclick="this.parentElement.remove(); video.load();" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">Tekrar Dene</button>
                `;
                
                const container = video.parentElement;
                if (container) {
                    container.appendChild(errorDiv);
                }
            };
            
            video.src = media.src;
        } else {
            const img = document.createElement('img');
            img.src = media.src;
            img.alt = media.alt;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            
            // Add error handling for images too
            img.onerror = function() {
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'display: flex; align-items: center; justify-content: center; height: 100%; background: #f5f5f5; color: #666; flex-direction: column;';
                errorDiv.innerHTML = `
                    <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 1rem; color: #ccc;"></i>
                    <p style="text-align: center; margin: 0;">Resim yüklenemiyor</p>
                `;
                mainMedia.innerHTML = '';
                mainMedia.appendChild(errorDiv);
            };
            
            mainMedia.appendChild(img);
        }

        // Update navigation buttons
        prevBtn.style.display = currentProject.media.length > 1 ? 'block' : 'none';
        nextBtn.style.display = currentProject.media.length > 1 ? 'block' : 'none';
    }

    function nextMedia() {
        if (!currentProject) return;
        const nextIndex = (currentMediaIndex + 1) % currentProject.media.length;
        showMedia(nextIndex);
    }

    function prevMedia() {
        if (!currentProject) return;
        const prevIndex = (currentMediaIndex - 1 + currentProject.media.length) % currentProject.media.length;
        showMedia(prevIndex);
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Stop any playing videos
        const videos = mainMedia.querySelectorAll('video');
        videos.forEach(video => {
            video.pause();
            video.currentTime = 0;
        });
    }

    // Event listeners
    nextBtn.addEventListener('click', nextMedia);
    prevBtn.addEventListener('click', prevMedia);
    closeBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (modal.style.display === 'block') {
            if (e.key === 'ArrowRight') nextMedia();
            if (e.key === 'ArrowLeft') prevMedia();
            if (e.key === 'Escape') closeModal();
        }
    });
});
