/* Where did this visitor come from? Keeps the first and the latest campaign touch (utm_* tags, Google Ads click ids,
   landing page, referrer) in localStorage for 90 days and adds them to every enquiry form as a hidden "attribution"
   field, so each lead e-mail says which ad, campaign and keyword brought it. Nothing is sent anywhere else. */
(function () {
  var KEY = 'architive-src', TTL = 90 * 864e5, KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid'];
  function read() { try { var s = JSON.parse(localStorage.getItem(KEY) || 'null'); return s && Date.now() - s.t < TTL ? s : null; } catch (e) { return null; } }
  function write(s) { try { localStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {} }

  var q = new URLSearchParams(location.search), touch = {}, tagged = false;
  KEYS.forEach(function (k) { var v = q.get(k); if (v) { touch[k] = v.slice(0, 150); tagged = true; } });
  var ref = document.referrer && document.referrer.indexOf(location.origin) !== 0 ? document.referrer.slice(0, 200) : '';
  touch.landing = location.pathname;
  if (ref) { touch.referrer = ref; }
  touch.at = new Date().toISOString().slice(0, 10);

  var s = read();
  if (!s) { s = { first: touch, last: tagged ? touch : null, t: Date.now() }; write(s); }
  else if (tagged || ref) { s.last = touch; s.t = Date.now(); write(s); }   // a new campaign click or outside visit refreshes the 90 days

  function attach() {
    var cur = read(); if (!cur) { return; }
    var val = JSON.stringify({ first: cur.first, last: cur.last });
    document.querySelectorAll('form[data-contact-form]').forEach(function (f) {
      var i = f.querySelector('input[name="attribution"]');
      if (!i) { i = document.createElement('input'); i.type = 'hidden'; i.name = 'attribution'; f.appendChild(i); }
      i.defaultValue = i.value = val;   // defaultValue survives form.reset() after a successful send
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', attach); } else { attach(); }
})();
