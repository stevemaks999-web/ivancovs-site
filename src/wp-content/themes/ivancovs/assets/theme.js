(function () {
  var r = document.documentElement;
  var b = document.getElementById('theme');
  function set(m) {
    r.setAttribute('data-theme', m);
    if (b) b.textContent = m === 'dark' ? '☀️' : '🌙';
    try { localStorage.setItem('ivt', m); } catch (e) {}
  }
  if (b) {
    set(r.getAttribute('data-theme') || 'light');
    b.addEventListener('click', function () { set(r.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'); });
  }
  var bg = document.getElementById('burger');
  if (bg) bg.addEventListener('click', function () { document.body.classList.toggle('menu-open'); });
})();
