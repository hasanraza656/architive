/* Cookie and analytics notice: remembers the choice in localStorage ('architive-consent') and tells Google Analytics.
   The "Cookie settings" button in the footer reopens the notice. */
(function () {
  var KEY = 'architive-consent';
  var box = document.getElementById('consent');
  if (!box) { return; }

  function stored() {
    try { var c = JSON.parse(localStorage.getItem(KEY) || 'null'); return c && typeof c.analytics === 'boolean' ? c : null; } catch (e) { return null; }
  }
  function save(analytics) {
    try { localStorage.setItem(KEY, JSON.stringify({ analytics: analytics, t: Date.now() })); } catch (e) {}
    if (typeof window.gtag === 'function') { window.gtag('consent', 'update', { analytics_storage: analytics ? 'granted' : 'denied' }); }
  }
  function show() { box.hidden = false; }
  function hide() { box.hidden = true; }

  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-consent]');
    if (!b) { return; }
    save(b.getAttribute('data-consent') === 'accept');
    hide();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden && stored()) { hide(); } });
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-cookie-settings]');
    if (t) { e.preventDefault(); show(); box.querySelector('[data-consent="accept"]').focus(); }
  });

  if (!stored()) { setTimeout(show, 1200); }
})();
