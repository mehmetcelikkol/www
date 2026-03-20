/* ============================================================
   SiloSense — app.js
   Silo animasyonları, genel yardımcı fonksiyonlar
   ============================================================ */

'use strict';

// ============================================================
// ANİMASYONLU SİLO GÖSTERİMİ
// ============================================================

/**
 * Silo SVG bileşeni oluşturur ve hedef elemente ekler.
 * @param {HTMLElement} hedef     - SVG eklenecek container
 * @param {number}      yuzde    - Doluluk yüzdesi (0-100)
 * @param {string}      renk     - Doluluk rengi (hex)
 * @param {boolean}     animasyon - Animasyonlu başlatılsın mı?
 */
function siloOlustur(hedef, yuzde, renk, animasyon = true) {
    yuzde = Math.min(Math.max(yuzde, 0), 100);
    const siloYukseklik = 120; // SVG içi silo gövde yüksekliği
    const siloBaslangic = 10;  // Y başlangıç noktası
    const dolulukYukseklik = (yuzde / 100) * siloYukseklik;
    const dolulukY = siloBaslangic + siloYukseklik - dolulukYukseklik;

    hedef.innerHTML = `
        <svg class="silo-svg" viewBox="0 0 100 160" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="siloGovde_${hedef.id}" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%"   stop-color="rgba(255,255,255,0.05)"/>
                    <stop offset="100%" stop-color="rgba(255,255,255,0.02)"/>
                </linearGradient>
                <linearGradient id="siloSiv_${hedef.id}" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="${renk}" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="${renk}" stop-opacity="0.6"/>
                </linearGradient>
                <clipPath id="siloClip_${hedef.id}">
                    <rect x="15" y="10" width="70" height="120" rx="0"/>
                </clipPath>
            </defs>

            <!-- Silo Gövdesi - Dış Çerçeve -->
            <rect x="15" y="10" width="70" height="120" rx="5"
                  fill="rgba(255,255,255,0.03)"
                  stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>

            <!-- Silo Tepesi - Konik Çatı -->
            <polygon points="50,2 15,10 85,10"
                     fill="rgba(255,255,255,0.08)"
                     stroke="rgba(255,255,255,0.1)" stroke-width="1"/>

            <!-- Silo Alt - Konik Dip -->
            <polygon points="15,130 85,130 70,150 30,150"
                     fill="rgba(255,255,255,0.06)"
                     stroke="rgba(255,255,255,0.1)" stroke-width="1"/>

            <!-- Doluluk (Sıvı) -->
            <g clip-path="url(#siloClip_${hedef.id})">
                <rect class="silo-ic-doluluk" id="doluluk_${hedef.id}"
                      x="15"
                      y="${animasyon ? 130 : dolulukY}"
                      width="70"
                      height="${animasyon ? 0 : dolulukYukseklik}"
                      fill="url(#siloSiv_${hedef.id})"/>

                <!-- Dalga Efekti (üst kısım) -->
                <rect id="dalga_${hedef.id}"
                      x="15"
                      y="${animasyon ? 130 : dolulukY}"
                      width="70" height="6"
                      fill="${renk}" opacity="0.5"/>
            </g>

            <!-- Silo Yatay Çizgiler (tank görünümü) -->
            <line x1="15" y1="43" x2="85" y2="43" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="2,2"/>
            <line x1="15" y1="76" x2="85" y2="76" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="2,2"/>
            <line x1="15" y1="109" x2="85" y2="109" stroke="rgba(255,255,255,0.05)" stroke-width="1" stroke-dasharray="2,2"/>

            <!-- Parlama Efekti -->
            <rect x="18" y="13" width="8" height="60" rx="4"
                  fill="rgba(255,255,255,0.04)"/>
        </svg>`;

    // Animasyonlu doldurma
    if (animasyon) {
        setTimeout(() => {
            const dolulukEl = document.getElementById(`doluluk_${hedef.id}`);
            const dalgaEl   = document.getElementById(`dalga_${hedef.id}`);
            if (dolulukEl) {
                dolulukEl.setAttribute('y', dolulukY);
                dolulukEl.setAttribute('height', dolulukYukseklik);
            }
            if (dalgaEl) {
                dalgaEl.setAttribute('y', dolulukY - 3);
            }
        }, 100);
    }
}

/**
 * Tüm silo kartlarını sayfa yüklendiğinde başlatır.
 * Her kart: data-yuzde, data-renk, data-id attribute'larına sahip olmalı.
 */
function siloKartlariniBaslat() {
    document.querySelectorAll('.silo-svg-container[data-yuzde]').forEach(container => {
        const yuzde = parseFloat(container.dataset.yuzde) || 0;
        const renk  = container.dataset.renk || '#6366f1';
        siloOlustur(container, yuzde, renk, true);
    });
}

// ============================================================
// COUNTDOWN (Geri Sayım Sayacı)
// ============================================================

/**
 * Hedef tarihe göre canlı geri sayım başlatır.
 * @param {string} hedefId       - Container element ID
 * @param {string} birisTarihi   - 'DD.MM.YYYY' formatında tarih
 */
function geriSayimBaslat(hedefId, birisTarihi) {
    const el = document.getElementById(hedefId);
    if (!el) return;

    const parcalar = birisTarihi.split('.');
    if (parcalar.length !== 3) return;

    const hedef = new Date(`${parcalar[2]}-${parcalar[1]}-${parcalar[0]}T00:00:00`);

    function guncelle() {
        const simdi     = new Date();
        const fark      = hedef - simdi;
        const gunKaldi  = Math.ceil(fark / (1000 * 60 * 60 * 24));

        if (fark <= 0) {
            el.innerHTML = '<span style="color:var(--renk-tehlike)">⚠️ Yem Bitti!</span>';
            return;
        }

        const gun  = Math.floor(fark / (1000 * 60 * 60 * 24));
        const saat = Math.floor((fark % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const dak  = Math.floor((fark % (1000 * 60 * 60)) / (1000 * 60));
        const san  = Math.floor((fark % (1000 * 60)) / 1000);

        el.innerHTML = `
            <span class="countdown-gun">${gun}</span>
            <span class="countdown-sep">G</span>
            <span class="countdown-saat">${String(saat).padStart(2,'0')}</span>
            <span class="countdown-sep">S</span>
            <span class="countdown-dak">${String(dak).padStart(2,'0')}</span>
            <span class="countdown-sep">D</span>
            <span class="countdown-san">${String(san).padStart(2,'0')}</span>
            <span class="countdown-sep">S</span>
        `;
    }

    guncelle();
    setInterval(guncelle, 1000);
}

// ============================================================
// SweetAlert2 YARDIMCI FONKSİYONLARI
// ============================================================

const SS = {
    /**
     * Silme onay diyaloğu gösterir.
     * Kullanım: SS.silmeOnayi(url, 'Bu kaydı silmek istediğinize emin misiniz?')
     */
    silmeOnayi(url, mesaj = 'Bu işlem geri alınamaz!') {
        Swal.fire({
            title: 'Emin misiniz?',
            text: mesaj,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6366f1',
            background: '#1a2235',
            color: '#e2e8f0',
            iconColor: '#f59e0b',
        }).then(sonuc => {
            if (sonuc.isConfirmed) {
                window.location.href = url;
            }
        });
    },

    /** Başarı toast bildirimi */
    basari(mesaj) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: mesaj,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#1a2235',
            color: '#86efac',
            iconColor: '#22c55e',
        });
    },

    /** Hata toast bildirimi */
    hata(mesaj) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: mesaj,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            background: '#1a2235',
            color: '#fca5a5',
            iconColor: '#ef4444',
        });
    },

    /** Bilgi toast bildirimi */
    bilgi(mesaj) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: mesaj,
            showConfirmButton: false,
            timer: 3000,
            background: '#1a2235',
            color: '#7dd3fc',
            iconColor: '#38bdf8',
        });
    }
};

// ============================================================
// SAYFA YÜKLENDİĞİNDE ÇALIŞACAKLAR
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Silo kartlarını başlat
    siloKartlariniBaslat();

    // Sidebar overlay (mobil) - dışarı tıklayınca kapat
    document.addEventListener('click', (e) => {
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('menuToggle');
        if (sidebar?.classList.contains('ss-sidebar-open')
            && !sidebar.contains(e.target)
            && !toggle?.contains(e.target)) {
            sidebar.classList.remove('ss-sidebar-open');
        }
    });

    // Tablo satır hover efekti
    document.querySelectorAll('.ss-tablo tbody tr').forEach(tr => {
        tr.style.cursor = 'pointer';
    });
});
