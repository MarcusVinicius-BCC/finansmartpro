document.addEventListener('DOMContentLoaded', function(){
  const t = document.getElementById('themeToggle');
  if(t){
    t.addEventListener('click', function(){
      const html = document.documentElement;
      const cur = html.getAttribute('data-theme') || 'light';
      const next = cur === 'light' ? 'dark' : 'light';
      html.setAttribute('data-theme', next);
      localStorage.setItem('finansmart_theme', next);
    });
    const saved = localStorage.getItem('finansmart_theme');
    if(saved) document.documentElement.setAttribute('data-theme', saved);
  }
});