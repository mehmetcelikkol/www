(function(){
  function applyTheme(){
    const pref = localStorage.getItem('appTheme');
    if(pref==='light') document.body.classList.add('light'); else document.body.classList.remove('light');
  }
  function toggleTheme(){
    const isLight = document.body.classList.toggle('light');
    localStorage.setItem('appTheme', isLight ? 'light' : 'dark');
  }
  window.__applyTheme = applyTheme;
  window.__toggleTheme = toggleTheme;
  document.addEventListener('DOMContentLoaded', ()=>{
    applyTheme();
    const btn = document.getElementById('themeToggle');
    if(btn){ btn.addEventListener('click', toggleTheme); }
  });
})();