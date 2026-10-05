/* ==========================================================================
   Architive – page-specific interactive widgets.
   Each block is guarded: it only runs when its markup exists on the page.
   ========================================================================== */
(function ($, window, document) {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var raf = window.requestAnimationFrame || function (fn) { return setTimeout(fn, 16); };

  function inView(el, cb, threshold) {
    if (!('IntersectionObserver' in window)) { cb(); return; }
    var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { io.disconnect(); cb(); } }, { threshold: threshold || 0.35 });
    io.observe(el);
  }

  /* ---- "Split vs one team" diagram ---------------------------------------- */
  $('[data-unify]').each(function () {
    var $u = $(this), touched = false;
    function set(state) {
      $u.attr('data-state', state);
      $u.find('[data-state-btn]').each(function () {
        var on = $(this).data('state-btn') === state;
        $(this).toggleClass('is-active', on).attr('aria-pressed', on);
      });
      $u.find('[data-cap]').each(function () { $(this).prop('hidden', $(this).data('cap') !== state); });
    }
    $u.on('click', '[data-state-btn]', function () { touched = true; set($(this).data('state-btn')); });
    set('split');
    if (!reduceMotion) { inView(this, function () { setTimeout(function () { if (!touched) { set('one'); } }, 1900); }, 0.5); }
  });

  /* ---- Before / after compare --------------------------------------------- */
  $('[data-compare]').each(function () {
    var el = this, $c = $(el), $r = $c.find('.compare__range');
    function set(v) { el.style.setProperty('--pos', v + '%'); }
    $r.on('input change', function () { $c.removeClass('is-intro'); set(this.value); });
    if (!reduceMotion) {
      set(8); $c.addClass('is-intro');
      inView(el, function () {
        setTimeout(function () { set(58); setTimeout(function () { set(50); }, 1500); setTimeout(function () { $c.removeClass('is-intro'); }, 3000); }, 350);
      }, 0.4);
    }
    // Re-run layout when a hidden Bootstrap tab becomes visible.
    $(document).on('shown.bs.tab', function () { set($r.val()); });
  });

  /* ---- CAD layer toggles --------------------------------------------------- */
  $('[data-layers]').on('click', '.layer', function () {
    var $b = $(this), on = !$b.hasClass('is-on'), key = $b.data('layer');
    $b.toggleClass('is-on', on).attr('aria-pressed', on);
    $('[data-layer-group="' + key + '"]').toggleClass('is-off', !on);
  });

  /* ---- Services picker ------------------------------------------------------ */
  $('[data-picker]').each(function () {
    var $p = $(this), $res = $p.find('.picker__result');
    $p.on('click', '.picker__opt', function () {
      var $o = $(this);
      $p.find('.picker__opt').attr('aria-pressed', false); $o.attr('aria-pressed', true);
      $res.find('[data-pick-title]').text($o.data('title'));
      $res.find('[data-pick-text]').text($o.data('text'));
      $res.find('[data-pick-link]').attr('href', $o.data('url'));
      $res.prop('hidden', false);
      $res.css('animation', 'none'); void $res[0].offsetWidth; $res.css('animation', '');
    });
  });

  /* ---- Collaboration filters ------------------------------------------------ */
  $('.filters').each(function () {
    var $f = $(this), $rows = $('.collab-row');
    $f.on('click', 'button', function () {
      var k = $(this).data('filter');
      $f.find('button').removeClass('is-active'); $(this).addClass('is-active');
      $rows.each(function () { $(this).toggleClass('is-hidden', k !== 'all' && $(this).data('kind') !== k); });
    });
  });

  /* ---- Vertical timeline progress ------------------------------------------- */
  $('[data-vsteps]').each(function () {
    var el = this;
    function update() {
      var r = el.getBoundingClientRect(), vh = window.innerHeight;
      var p = (vh * 0.55 - r.top) / r.height;
      el.style.setProperty('--p', Math.max(0, Math.min(1, p)).toFixed(3));
    }
    $(window).on('scroll resize', function () { raf(update); }); update();
  });

  /* ---- Time-zone widget ------------------------------------------------------- */
  $('[data-tz]').each(function () {
    var $t = $(this), $region = $t.find('[data-tz-region]'), $slider = $t.find('[data-tz-slider]');
    var $you = $t.find('[data-tz-bar="you"]'), $studio = $t.find('[data-tz-bar="studio"]');
    var OPEN = [9, 17], STUDIO_PKT = [9, 18], PKT = 5;
    function pad(n) { return (n < 10 ? '0' : '') + n + ':00'; }
    function mod(n) { return ((n % 24) + 24) % 24; }
    function seg($bar, from, to) {            // draw a (possibly midnight-wrapping) segment on a 24h bar
      from = mod(from); to = mod(to);
      function add(a, b) { $('<i></i>').css({ left: (a / 24 * 100) + '%', width: ((b - a) / 24 * 100) + '%' }).appendTo($bar); }
      $bar.empty();
      if (from < to) { add(from, to); } else { add(from, 24); add(0, to); }
    }
    function render() {
      var off = parseInt($region.val(), 10), h = parseInt($slider.val(), 10);
      var studioHour = mod(h - off + PKT);                 // local hour -> UTC -> Pakistan time
      seg($you, OPEN[0], OPEN[1]);
      seg($studio, STUDIO_PKT[0] - PKT + off, STUDIO_PKT[1] - PKT + off);
      $t.find('[data-tz-local]').text(pad(h));
      $t.find('[data-tz-studio]').text(pad(studioHour) + ' PKT');
      $t.find('[data-tz-marker]')[0].style.setProperty('--h', h);
      var officeOpen = h >= OPEN[0] && h < OPEN[1], studioOpen = studioHour >= STUDIO_PKT[0] && studioHour < STUDIO_PKT[1], msg;
      if (officeOpen && studioOpen) { msg = '<b>Both online.</b> A good moment to brief and review together.'; }
      else if (officeOpen) { msg = '<b>Your office is open.</b> The studio is offline—send today\'s well-defined tasks before you finish for the day.'; }
      else if (studioOpen) { msg = '<b>Your office is closed. The studio is working.</b> A suitable redline or model update can be ready for your morning.'; }
      else { msg = '<b>Both offline.</b> Work queues for the start of the studio day.'; }
      $t.find('[data-tz-status]').html(msg);
    }
    $region.add($slider).on('input change', render); render();
  });

  /* ---- reCAPTCHA v2 widget (called by Google's script once it has loaded) ---- */
  window.architiveCaptcha = function () {
    $('[data-captcha]').each(function () {
      var el = this, w = $(el).parent().width();
      el._wid = grecaptcha.render(el, {
        sitekey: $(el).data('sitekey'),
        theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light',
        size: (w && w < 306) ? 'compact' : 'normal',          // keeps it inside narrow phone screens
        callback: function () { $(el).closest('form').find('[data-err-for="captcha"]').text(''); }
      });
    });
  };

  /* ---- Contact form --------------------------------------------------------- */
  $('[data-contact-form]').each(function () {
    var form = this, $f = $(form);
    function setErr(name, msg) {
      $f.find('[data-err-for="' + name + '"]').text(msg || '');
      $f.find('[name="' + name + '"]').attr('aria-invalid', msg ? 'true' : null);
    }
    function validate() {
      var ok = true, v = function (n) { return $.trim($f.find('[name="' + n + '"]').val() || ''); };
      ['name', 'email', 'message', 'consent', 'captcha'].forEach(function (n) { setErr(n, ''); });
      if (!v('name')) { setErr('name', 'Please tell us your name.'); ok = false; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v('email'))) { setErr('email', 'Please enter a valid email address.'); ok = false; }
      if (v('message').length < 10) { setErr('message', 'A little more detail helps—at least 10 characters.'); ok = false; }
      if (!$f.find('[name="consent"]').is(':checked')) { setErr('consent', 'Please confirm you agree to be contacted about this enquiry.'); ok = false; }
      var $cap = $f.find('[data-captcha]');
      if ($cap.length && !(window.grecaptcha && $cap[0]._wid !== undefined && grecaptcha.getResponse($cap[0]._wid))) {
        setErr('captcha', 'Please tick “I’m not a robot” to continue.'); ok = false;
      }
      return ok;
    }
    function resetCaptcha() { var $c = $f.find('[data-captcha]'); if ($c.length && window.grecaptcha && $c[0]._wid !== undefined) { grecaptcha.reset($c[0]._wid); } }
    $f.on('input change', 'input, textarea', function () { if (this.name && $(this).attr('aria-invalid')) { setErr(this.name, ''); } });
    $f.on('submit', function (e) {
      var $err = $f.find('[data-form-error]').prop('hidden', true);
      if (!validate()) {
        e.preventDefault();
        var $first = $f.find('[aria-invalid="true"]').first(); if ($first.length) { $first.trigger('focus'); }
        return;
      }
      if (!window.fetch) { return; }       // fall back to a normal POST
      e.preventDefault();
      $f.addClass('is-loading');
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (res) { return res.json().then(function (data) { return { res: res, data: data }; }); })
        .then(function (o) {
          $f.removeClass('is-loading');
          resetCaptcha();
          if (o.res.ok) {
            var $s = $('[data-success]'); $s.find('[data-success-text]').text(o.data.message || '');
            $f.prop('hidden', true); $s.prop('hidden', false).addClass('is-shown');
            $s[0].scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
          } else if (o.res.status === 422 && o.data.errors) {
            $.each(o.data.errors, function (k, msgs) { setErr(k === 'g-recaptcha-response' ? 'captcha' : k, msgs[0]); });
          } else {
            $err.text('Something went wrong—please try again, or email us directly.').prop('hidden', false);
          }
        })
        .catch(function () {
          $f.removeClass('is-loading');
          resetCaptcha();
          $err.text('We could not send your message. Please check your connection or email us directly.').prop('hidden', false);
        });
    });
  });

}(jQuery, window, document));
