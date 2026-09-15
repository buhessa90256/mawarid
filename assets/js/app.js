(function () {
  var btn = document.getElementById('menuBtn');
  var side = document.getElementById('sidebar');
  if (!btn || !side) return;
  btn.addEventListener('click', function () {
    side.classList.toggle('open');
  });
  document.addEventListener('click', function (e) {
    if (window.innerWidth > 900) return;
    if (!side.classList.contains('open')) return;
    if (side.contains(e.target) || btn.contains(e.target)) return;
    side.classList.remove('open');
  });
})();
