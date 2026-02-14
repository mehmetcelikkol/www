(() => {
  // Mobile nav toggle
  const toggle = document.querySelector('.nav-toggle');
  const list = document.getElementById('primary-nav');
  if (toggle && list) {
    toggle.addEventListener('click', () => {
      const open = list.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  // Simple slider
  const slider = document.getElementById('heroSlider');
  if (slider) {
    const slides = [...slider.querySelectorAll('.slide')];
    const dots = [...slider.querySelectorAll('.dot')];
    const prev = slider.querySelector('.control.prev');
    const next = slider.querySelector('.control.next');

    let index = 0;
    let timer;

    function show(i){
      slides.forEach((s, idx) => s.classList.toggle('is-active', idx === i));
      dots.forEach((d, idx) => d.classList.toggle('is-active', idx === i));
      dots.forEach((d, idx) => d.setAttribute('aria-selected', String(idx === i)));
      index = i;
    }
    function step(dir){ show((index + dir + slides.length) % slides.length); }
    function start(){ stop(); timer = setInterval(() => step(1), 5000); }
    function stop(){ if (timer) clearInterval(timer); }

    prev.addEventListener('click', () => { step(-1); start(); });
    next.addEventListener('click', () => { step(1); start(); });
    dots.forEach((d, i) => d.addEventListener('click', () => { show(i); start(); }));

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);

    show(0);
    start();
  }

  // Dil seçimi ve i18n
  const i18n = {
    tr: {
      'nav.home':'Ana Sayfa','nav.products':'Ürünler','nav.why':'Neden Filla?','nav.brands':'Markalarımız','nav.contact':'İletişim',
      'hero.slide1.title':'Endüstriyel Sensörlerde Güvenilir Satış ve Tedarik',
      'hero.slide1.desc':'Distribütörü olduğumuz markaların sensörlerini stoktan hızlı teslim ediyoruz.',
      'hero.slide1.cta1':'Ürünleri İncele','hero.slide1.cta2':'Teklif Al',
      'hero.slide2.title':'Fotoelektrik ve Endüktif Sensör Çözümleri',
      'hero.slide2.desc':'IP67 koruma, geniş algılama mesafeleri ve hızlı tepki süreleri.',
      'hero.slide2.cta1':'Neden Filla?',
      'hero.slide3.title':'Teknik Destek ve Entegrasyon',
      'hero.slide3.desc':'Saha desteği, entegrasyon danışmanlığı ve hızlı tedarik.',
      'hero.slide3.cta1':'İletişime Geç',
      'section.why.title':'Neden Filla?',
      'feature1.title':'Güvenilir Tedarik','feature1.desc':'Distribütörü olduğumuz markalardan stoktan hızlı sevkiyat.',
      'feature2.title':'Endüstriyel Dayanıklılık','feature2.desc':'IP67 koruma, titreşim dayanımı ve geniş sıcaklık aralığı.',
      'feature3.title':'Teknik Destek','feature3.desc':'Entegrasyon danışmanlığı ve uygulama örnekleri.',
      'products.title':'Öne Çıkan Sensör Ürünleri',
      'prod1.title':'Fotoelektrik Sensör','prod1.desc':'Yüksek hassasiyet, uzun menzil ve ortam ışığına dayanım.',
      'prod2.title':'Endüktif Sensör','prod2.desc':'Metal algılama, kompakt tasarım ve hızlı tepki.',
      'prod3.title':'Kapasitif Sensör','prod3.desc':'Sıvı ve granül materyaller için güvenilir algılama.',
      'prod.btn':'Detay',
      'brands.title':'Markalarımız',
      'contact.title':'Satış ve İletişim',
      'form.nameLabel':'Ad Soyad','form.emailLabel':'E-Posta','form.messageLabel':'Mesajınız','form.submit':'Gönder',
      'form.namePlaceholder':'Ad Soyad','form.emailPlaceholder':'ornek@firma.com','form.messagePlaceholder':'İhtiyacınızı kısaca yazın...',
      'contact.infoTitle':'Hızlı İletişim','contact.infoLead':'Teklif ve demo talebi için bizimle iletişime geçin.',
      'contact.list.phone':'Telefon: +90 (___) ___ __ __','contact.list.email':'E-posta: info@filla.com.tr','contact.list.address':'Adres: İstanbul / Türkiye'
    },
    en: {
      'nav.home':'Home','nav.products':'Products','nav.why':'Why Filla?','nav.brands':'Our Brands','nav.contact':'Contact',
      'hero.slide1.title':'Reliable Sales and Supply in Industrial Sensors',
      'hero.slide1.desc':'We deliver sensors from the brands we distribute directly from stock, fast.',
      'hero.slide1.cta1':'Browse Products','hero.slide1.cta2':'Get a Quote',
      'hero.slide2.title':'Photoelectric and Inductive Sensor Solutions',
      'hero.slide2.desc':'IP67 protection, wide sensing ranges and fast response times.',
      'hero.slide2.cta1':'Why Filla?',
      'hero.slide3.title':'Technical Support & Integration',
      'hero.slide3.desc':'Field support, integration consultancy and fast supply.',
      'hero.slide3.cta1':'Contact Us',
      'section.why.title':'Why Filla?',
      'feature1.title':'Reliable Supply','feature1.desc':'Fast shipments from brands we distribute, directly from stock.',
      'feature2.title':'Industrial Durability','feature2.desc':'IP67 protection, vibration resistance and wide temperature range.',
      'feature3.title':'Technical Support','feature3.desc':'Integration consultancy and application examples.',
      'products.title':'Featured Sensor Products',
      'prod1.title':'Photoelectric Sensor','prod1.desc':'High precision, long range and ambient light immunity.',
      'prod2.title':'Inductive Sensor','prod2.desc':'Metal detection, compact design and fast response.',
      'prod3.title':'Capacitive Sensor','prod3.desc':'Reliable detection for liquids and granules.',
      'prod.btn':'Details',
      'brands.title':'Our Brands',
      'contact.title':'Sales & Contact',
      'form.nameLabel':'Full Name','form.emailLabel':'Email','form.messageLabel':'Your Message','form.submit':'Send',
      'form.namePlaceholder':'Full Name','form.emailPlaceholder':'example@company.com','form.messagePlaceholder':'Briefly describe your need...',
      'contact.infoTitle':'Quick Contact','contact.infoLead':'Reach us for quotes and demo requests.',
      'contact.list.phone':'Phone: +90 (___) ___ __ __','contact.list.email':'Email: info@filla.com.tr','contact.list.address':'Address: Istanbul / Türkiye'
    }
  };

  const langBtns = document.querySelectorAll('.lang-btn');
  const i18nEls = document.querySelectorAll('[data-i18n]');
  const i18nPlaceholderEls = document.querySelectorAll('[data-i18n-placeholder]');

  function applyLang(lang) {
    document.documentElement.setAttribute('lang', lang);
    i18nEls.forEach(el => {
      const key = el.dataset.i18n;
      const val = i18n[lang] && i18n[lang][key];
      if (val !== undefined) el.textContent = val;
    });
    i18nPlaceholderEls.forEach(el => {
      const key = el.dataset.i18nPlaceholder;
      const val = i18n[lang] && i18n[lang][key];
      if (val !== undefined) el.setAttribute('placeholder', val);
    });
    langBtns.forEach(b => b.classList.toggle('is-active', b.dataset.lang === lang));
  }

  const defaultLang = (new URLSearchParams(location.search).get('lang')) || 'tr';
  applyLang(defaultLang);
  langBtns.forEach(btn => btn.addEventListener('click', () => applyLang(btn.dataset.lang)));
})();

/* Side panel toggle behavior */
document.addEventListener('DOMContentLoaded', function(){
  const contactToggle = document.getElementById('contactToggle');
  const sidePanel = document.getElementById('sidePanel');
  const sideClose = sidePanel && sidePanel.querySelector('.side-close');

  // DEBUG: log presence of elements
  try{ console.debug('[side-panel] init'); }catch(e){}
  try{ console.debug('[side-panel] contactToggle:', !!contactToggle, contactToggle); }catch(e){}
  try{ console.debug('[side-panel] sidePanel:', !!sidePanel, sidePanel); }catch(e){}

  function openSide(){
    if(!sidePanel) return;
    sidePanel.classList.add('open');
    if(contactToggle) contactToggle.setAttribute('aria-expanded','true');
    sidePanel.setAttribute('aria-hidden','false');
    document.body.classList.add('no-scroll');
  }
  function closeSide(){
    if(!sidePanel) return;
    sidePanel.classList.remove('open');
    if(contactToggle) contactToggle.setAttribute('aria-expanded','false');
    sidePanel.setAttribute('aria-hidden','true');
    document.body.classList.remove('no-scroll');
  }

  if(contactToggle && sidePanel){
    const palette = ['#1f7aed','#0ea5e9','#06b6d4','#8b5cf6','#f59e0b','#10b981','#ef4444','#7c3aed'];
    function pickColor(){ return palette[Math.floor(Math.random()*palette.length)]; }

    // extra pointerdown log to catch blocked clicks
    contactToggle.addEventListener('pointerdown', (ev)=>{ try{ console.debug('[side-panel] pointerdown', ev.target); }catch(e){} });

    contactToggle.addEventListener('click', ()=>{
      const open = sidePanel.classList.toggle('open');
      try{ console.debug('[side-panel] toggle clicked, open=', open); }catch(e){}
      contactToggle.setAttribute('aria-expanded', String(open));
      sidePanel.setAttribute('aria-hidden', String(!open));
      document.body.classList.toggle('no-scroll', open);

      if(open){
        // assign a random accent color each time
        const c = pickColor();
        try{ console.debug('[side-panel] picked color', c); }catch(e){}
        contactToggle.style.background = c;
        // panel gets a gradient using the color and a dark base
        sidePanel.style.background = `linear-gradient(180deg, ${c}, #081025)`;
        // ensure form is inside panel (if moved elsewhere)
        const mainForm = document.getElementById('sideContactForm');
        if(mainForm && mainForm.parentElement !== sidePanel.querySelector('.side-inner')){
          sidePanel.querySelector('.side-inner').appendChild(mainForm);
        }
        const first = sidePanel.querySelector('input,textarea,button');
        if(first) first.focus();
      } else {
        // on close, remove inline panel background so CSS can control default
        contactToggle.style.background = '';
        sidePanel.style.background = '';
      }
    });
    if(sideClose) sideClose.addEventListener('click', closeSide);
    sidePanel.addEventListener('click', (e)=>{ if(e.target === sidePanel) closeSide(); });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && sidePanel.classList.contains('open')) closeSide(); });
  }
});