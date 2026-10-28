/* Cookie and analytics notice. Shown only to visitors in the EU and the UK (client decision, 28 Oct 2026).
   Location is guessed in the browser from the time zone and language, so no location lookup is sent anywhere.
   Everyone else is not asked: analytics runs, and they can opt out with Google's browser add-on (see the privacy page).
   Google also applies its own region rules (config in partials/analytics.blade.php), so analytics cookies stay off in those
   places until the visitor accepts. The choice is kept in localStorage ('architive-consent'). */
(function () {
  var KEY = 'architive-consent';
  var box = document.getElementById('consent');
  if (!box) { return; }

  var EU = 'AT BE BG HR CY CZ DK EE FI FR DE GR HU IE IT LV LT LU MT NL PL PT RO SK SI ES SE GB'.split(' ');
  // EU member states and the UK (with the Crown Dependencies and Gibraltar); other European time zones are not asked
  var EU_TZ = /^(Europe\/(Amsterdam|Athens|Berlin|Bratislava|Brussels|Bucharest|Budapest|Busingen|Copenhagen|Dublin|Helsinki|Lisbon|Ljubljana|London|Belfast|Guernsey|Isle_of_Man|Jersey|Gibraltar|Luxembourg|Madrid|Malta|Mariehamn|Paris|Prague|Riga|Rome|Sofia|Stockholm|Tallinn|Vienna|Vilnius|Warsaw|Zagreb|Nicosia)|Asia\/(Nicosia|Famagusta)|Atlantic\/(Canary|Madeira|Azores))$/;
  function needsConsent() {
    try {
      var tz = (Intl.DateTimeFormat().resolvedOptions().timeZone || '');
      if (EU_TZ.test(tz)) { return true; }
      var langs = navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language || ''];
      for (var i = 0; i < langs.length; i++) {
        var m = /[-_]([A-Za-z]{2})$/.exec(langs[i]);
        if (m && EU.indexOf(m[1].toUpperCase()) !== -1) { return true; }
      }
    } catch (e) {}
    return false;
  }
  if (!needsConsent()) { return; }

  var openers = document.querySelectorAll('[data-cookie-settings]');
  for (var k = 0; k < openers.length; k++) { openers[k].hidden = false; }

  function stored() {
    try { var c = JSON.parse(localStorage.getItem(KEY) || 'null'); return c && typeof c.analytics === 'boolean' ? c : null; } catch (e) { return null; }
  }
  function save(analytics) {
    try { localStorage.setItem(KEY, JSON.stringify({ analytics: analytics, t: Date.now() })); } catch (e) {}
    if (typeof window.gtag === 'function') {
      var v = analytics ? 'granted' : 'denied';
      window.gtag('consent', 'update', { analytics_storage: v, ad_storage: v, ad_user_data: v, ad_personalization: v });
    }
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
