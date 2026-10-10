/* Blog article page: reading progress bar, table-of-contents highlight, copy link. */
(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---- reading progress ---- */
  var bar = document.querySelector('[data-read-progress]'), prose = document.querySelector('.prose');
  if (bar && prose) {
    var ticking = false;
    var update = function () {
      var r = prose.getBoundingClientRect(), total = r.height - window.innerHeight * 0.55;
      var done = total > 0 ? Math.min(1, Math.max(0, (-r.top + window.innerHeight * 0.1) / total)) : 1;
      bar.style.transform = 'scaleX(' + done.toFixed(4) + ')';
      ticking = false;
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', update);
    update();
  }

  /* ---- table of contents: highlight the section being read ---- */
  var links = Array.prototype.slice.call(document.querySelectorAll('.post-toc--side a'));
  if (links.length && 'IntersectionObserver' in window) {
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var heads = Object.keys(map).map(function (id) { return document.getElementById(id); }).filter(Boolean);
    var current = null;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          if (current) { current.classList.remove('is-active'); }
          current = map[e.target.id]; current.classList.add('is-active');
        }
      });
    }, { rootMargin: '-15% 0px -70% 0px' });
    heads.forEach(function (h) { io.observe(h); });
  }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.post-toc a');
    if (!a) { return; }
    var t = document.getElementById(a.getAttribute('href').slice(1));
    if (t) { e.preventDefault(); t.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }); history.replaceState(null, '', '#' + t.id); }
  });

  /* ---- copy link ---- */
  document.querySelectorAll('[data-copy-link]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-copy-link'), label = b.querySelector('[data-copy-state]');
      var done = function () {
        b.classList.add('is-copied');
        if (label) { var old = label.textContent; label.textContent = 'Link copied'; setTimeout(function () { label.textContent = old; b.classList.remove('is-copied'); }, 1800); }
        else { setTimeout(function () { b.classList.remove('is-copied'); }, 1800); }
      };
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(url).then(done); return; }
      var ta = document.createElement('textarea'); ta.value = url; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (err) {} ta.remove();
    });
  });
}());
