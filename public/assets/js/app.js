/* ==========================================================================
   Architive – core behaviour (jQuery + Bootstrap 5)
   Theme, header, reveal/split animations, counters, page transitions,
   parallax, micro-interactions, FAQ filter, quote slider.
   Everything degrades gracefully without JS; motion respects reduced-motion.
   ========================================================================== */
(function ($, window, document) {
  'use strict';

  var root = document.documentElement;
  var $win = $(window);
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var raf = window.requestAnimationFrame || function (fn) { return setTimeout(fn, 16); };

  function safe(fn) { try { return fn(); } catch (e) { return null; } }

  /* ---- Theme toggle ------------------------------------------------------ */
  function syncThemeLabel() {
    var dark = root.getAttribute('data-theme') === 'dark';
    $('[data-theme-toggle]').attr('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
  }
  $(document).on('click', '[data-theme-toggle]', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    safe(function () { localStorage.setItem('architive-theme', next); });
    syncThemeLabel();
  });
  syncThemeLabel();

  /* ---- Split headings into words ----------------------------------------- */
  function splitText(el) {
    var idx = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (ch) {
        if (ch.nodeType === 3) {
          var frag = document.createDocumentFragment();
          ch.textContent.split(/(\s+)/).forEach(function (p) {
            if (!p) { return; }
            if (/^\s+$/.test(p)) { frag.appendChild(document.createTextNode(' ')); return; }
            var w = document.createElement('span'), i = document.createElement('span');
            w.className = 'w'; w.style.setProperty('--wi', idx++);
            i.textContent = p; w.appendChild(i); frag.appendChild(w);
          });
          node.replaceChild(frag, ch);
        } else if (ch.nodeType === 1 && ch.tagName !== 'BR') {
          walk(ch);
        }
      });
    })(el);
  }
  $('[data-split]').each(function () { splitText(this); });

  /* ---- Reveal on scroll + counters --------------------------------------- */
  function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
  function runCount(el) {
    var $el = $(el), to = parseFloat($el.data('count')), from = parseFloat($el.data('from')) || 0;
    var suffix = $el.data('suffix') || '', plain = $el.data('from') !== undefined;
    var dur = 1900, start = null;
    function fmt(n) { n = Math.round(n); return (plain ? String(n) : n.toLocaleString('en-US')) + suffix; }
    if (reduceMotion) { $el.text(fmt(to)); return; }
    $el.text(fmt(from));
    function step(ts) {
      if (start === null) { start = ts; }
      var p = Math.min((ts - start) / dur, 1);
      $el.text(fmt(from + (to - from) * easeOutExpo(p)));
      if (p < 1) { raf(step); }
    }
    setTimeout(function () { raf(step); }, parseInt($el.data('delay'), 10) || 0);
  }

  function activate(el) {
    el.classList.add('is-in');
    $(el).find('[data-count]').addBack('[data-count]').each(function () { if (!this._counted) { this._counted = true; runCount(this); } });
  }
  var revealTargets = Array.prototype.slice.call(document.querySelectorAll('[data-reveal], [data-split], [data-count]'));
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          if (e.target.hasAttribute('data-count') && !e.target.hasAttribute('data-reveal')) {
            if (!e.target._counted) { e.target._counted = true; runCount(e.target); }
          } else { activate(e.target); }
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    revealTargets.forEach(function (el) { io.observe(el); });
  } else {
    revealTargets.forEach(activate);
  }
  // Safety net: never leave content hidden if the observer misfires.
  setTimeout(function () { $('[data-reveal]:not(.is-in), [data-split]:not(.is-in)').each(function () {
    var r = this.getBoundingClientRect(); if (r.top < window.innerHeight * 1.1) { activate(this); } }); }, 2500);

  /* ---- Header: scrolled / hide-on-scroll / progress / to-top -------------- */
  var $header = $('#siteHeader'), $progress = $('.scroll-progress span'), $top = $('.to-top');
  var lastY = window.pageYOffset, ticking = false;
  function onScroll() {
    var y = window.pageYOffset, h = document.documentElement.scrollHeight - window.innerHeight;
    $header.toggleClass('is-scrolled', y > 8);
    lastY = y;
    if ($progress.length && h > 0) { $progress[0].style.transform = 'scaleX(' + Math.min(y / h, 1) + ')'; }
    $top.toggleClass('is-visible', y > 700);
    parallax();
    ticking = false;
  }
  $win.on('scroll resize', function () { if (!ticking) { ticking = true; raf(onScroll); } });
  $header.on('focusin', function () { $header.removeClass('is-hidden'); });
  $top.on('click', function () { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); });
  $(document).on('click', '[data-to-top]', function (e) { e.preventDefault(); $top.trigger('click'); });

  /* ---- Mega menu (keyboard + click; hover handled in CSS) ----------------- */
  $(document).on('click', '.mega-toggle', function (e) {
    e.stopPropagation();
    var $li = $(this).closest('.has-mega'), open = !$li.hasClass('open');
    $li.toggleClass('open', open); $(this).attr('aria-expanded', open);
  });
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.has-mega').length) { $('.has-mega').removeClass('open').find('.mega-toggle').attr('aria-expanded', false); }
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') { $('.has-mega').removeClass('open').find('.mega-toggle').attr('aria-expanded', false); }
  });
  $('.has-mega').on('mouseenter', function () { $(this).find('.mega-toggle').attr('aria-expanded', true); })
                .on('mouseleave', function () { $(this).find('.mega-toggle').attr('aria-expanded', false); });

  /* ---- Parallax ----------------------------------------------------------- */
  var $par = $('[data-parallax]');
  function parallax() {
    if (reduceMotion || !$par.length) { return; }
    var vh = window.innerHeight;
    $par.each(function () {
      var f = parseFloat($(this).data('parallax')) || 0.1, host = this.parentElement.closest('section, .hero__card, .page-hero') || this.parentElement;
      var r = host.getBoundingClientRect();
      if (r.bottom < -100 || r.top > vh + 100) { return; }
      var off = (r.top + r.height / 2 - vh / 2) * -f;
      this.style.setProperty('--py', Math.max(-60, Math.min(60, off)).toFixed(1) + 'px');
    });
  }

  /* ---- Pointer micro-interactions (fine pointers only) -------------------- */
  if (canHover && !reduceMotion) {
    $(document).on('mousemove', '[data-spotlight]', function (e) {
      var r = this.getBoundingClientRect();
      this.style.setProperty('--mx', (e.clientX - r.left) + 'px'); this.style.setProperty('--my', (e.clientY - r.top) + 'px');
    });
    $('[data-magnetic]').each(function () {
      var el = this;
      $(el).on('mousemove', function (e) {
        var r = el.getBoundingClientRect(), x = (e.clientX - r.left - r.width / 2) * 0.22, y = (e.clientY - r.top - r.height / 2) * 0.32;
        el.style.transform = 'translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px)';
      }).on('mouseleave', function () { el.style.transform = ''; });
    });
    $('[data-tilt]').each(function () {
      var el = this;
      $(el).on('mousemove', function (e) {
        var r = el.getBoundingClientRect(), px = (e.clientX - r.left) / r.width - 0.5, py = (e.clientY - r.top) / r.height - 0.5;
        el.style.transform = 'perspective(1000px) rotateY(' + (px * 5).toFixed(2) + 'deg) rotateX(' + (-py * 5).toFixed(2) + 'deg) translateZ(0)';
      }).on('mouseleave', function () { el.style.transform = ''; });
    });
    $('[data-hero]').on('mousemove', function (e) {
      var r = this.getBoundingClientRect(), px = (e.clientX - r.left) / r.width - 0.5, py = (e.clientY - r.top) / r.height - 0.5;
      var layer = $(this).find('[data-tilt-layer]')[0];
      if (layer) { layer.style.transform = 'translate(' + (px * -22).toFixed(1) + 'px,' + (py * -14).toFixed(1) + 'px)'; }
    }).on('mouseleave', function () { var layer = $(this).find('[data-tilt-layer]')[0]; if (layer) { layer.style.transform = ''; } });
  }

  /* ---- Quick enquiry pop-up: any link to /contact/ opens the form straight away ---- */
  var enquiryEl = document.getElementById('enquiryModal');
  var enquiryModal = (enquiryEl && window.bootstrap) ? new bootstrap.Modal(enquiryEl) : null;
  window.architiveEnquiry = {
    open: function (o) {
      o = o || {};
      if (!enquiryModal) { window.location.href = '/contact/' + (o.service ? '?service=' + encodeURIComponent(o.service) : ''); return; }
      var $m = $(enquiryEl), $f = $m.find('form'), $ok = $m.find('[data-success]');
      if ($ok.length && !$ok.prop('hidden')) { $ok.prop('hidden', true); $f.prop('hidden', false); }     // fresh form after a previous success
      if (o.service) { $m.find('input[name="service"][value="' + o.service + '"]').prop('checked', true); }
      $m.find('input[name="audience"]').val(o.audience || '');
      $m.find('input[name="topic"]').val(o.topic || '');
      var $ctx = $m.find('[data-enquiry-context]');
      if (o.topic) { $ctx.text('You selected: ' + o.topic).prop('hidden', false); } else { $ctx.text('').prop('hidden', true); }
      enquiryModal.show();
    }
  };
  if (enquiryEl) {
    enquiryEl.addEventListener('shown.bs.modal', function () {
      if (window.architiveCaptchaLoad) { window.architiveCaptchaLoad(); }
      if (window.architiveTawk) { window.architiveTawk(false); }
      if (canHover) { $(enquiryEl).find('input[name="name"]').trigger('focus'); }
    });
    enquiryEl.addEventListener('hidden.bs.modal', function () { if (window.architiveTawk) { window.architiveTawk(true); } });
  }
  $(document).on('click', 'a[href]', function (e) {
    if (!enquiryModal || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.which > 1) { return; }
    var a = this;
    if (a.hostname !== window.location.hostname || a.pathname.replace(/\/+$/, '') !== '/contact') { return; }
    if ($(a).closest('.main-nav__list, .footer-list, .mobile-menu__nav, .crumbs, .legal, .sitemap-list, [data-no-modal]').length || a.hasAttribute('data-no-modal')) { return; }
    e.preventDefault();
    var q = new URLSearchParams(a.search), opts = { service: q.get('service'), audience: q.get('audience'), topic: a.getAttribute('data-topic') };
    var menu = document.getElementById('mobileMenu');
    if (menu && menu.classList.contains('show') && window.bootstrap) {            // close the mobile menu first, then open the form
      var inst = bootstrap.Offcanvas.getInstance(menu);
      if (inst) { menu.addEventListener('hidden.bs.offcanvas', function once() { menu.removeEventListener('hidden.bs.offcanvas', once); window.architiveEnquiry.open(opts); }); inst.hide(); return; }
    }
    window.architiveEnquiry.open(opts);
  });

  /* ---- Page transitions (curtain) ---------------------------------------- */
  safe(function () { sessionStorage.removeItem('ay-nav'); });
  setTimeout(function () { root.classList.remove('is-entering'); }, 1200);
  $(document).on('click', 'a[href]', function (e) {
    var a = this, href = a.getAttribute('href');
    if (e.isDefaultPrevented() || reduceMotion || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.which > 1) { return; }
    if (a.target && a.target !== '_self' || a.hasAttribute('download') || !href || href.charAt(0) === '#' || /^(mailto:|tel:|sms:|javascript:)/i.test(href)) { return; }
    if (a.hostname !== window.location.hostname || a.hasAttribute('data-bs-toggle') || a.hasAttribute('data-lightbox')) { return; }
    if (/\.(webp|jpe?g|png|gif|svg|mp4|webm|pdf|zip|xml|txt)(\?|$)/i.test(a.pathname + a.search)) { return; }   // files/media: normal browser handling
    if (a.pathname === window.location.pathname && a.search === window.location.search) { return; }
    e.preventDefault();
    root.classList.add('is-leaving');
    safe(function () { sessionStorage.setItem('ay-nav', '1'); });
    setTimeout(function () { window.location.href = a.href; }, 460);
    setTimeout(function () { root.classList.remove('is-leaving'); }, 4000);
  });
  window.addEventListener('pageshow', function (e) { if (e.persisted) { root.classList.remove('is-leaving', 'is-entering'); } });

  /* ---- Tawk.to live chat: hide its bubble while a full-screen overlay is open ---- */
  window.architiveTawk = function (show) {
    var t = window.Tawk_API;
    if (t && typeof t.hideWidget === 'function') { try { show ? t.showWidget() : t.hideWidget(); } catch (e) {} }
  };
  document.addEventListener('show.bs.offcanvas', function () { window.architiveTawk(false); });
  document.addEventListener('hidden.bs.offcanvas', function () { window.architiveTawk(true); });

  /* ---- FAQ search + category filter -------------------------------------- */
  $('[data-faq-tools]').each(function () {
    var $tools = $(this), $list = $('[data-faq]').first(), cat = '', term = '';
    function apply() {
      var shown = 0;
      $list.find('.faq__item').each(function () {
        var $i = $(this), txt = $i.text().toLowerCase();
        var ok = (!cat || $i.data('cat') === cat) && (!term || txt.indexOf(term) > -1);
        $i.prop('hidden', !ok); if (ok) { shown++; }
      });
      $list.find('.faq__empty').prop('hidden', shown > 0);
    }
    $tools.find('[data-faq-search]').on('input', function () { term = $.trim(this.value).toLowerCase(); apply(); });
    $tools.find('[data-faq-cat]').on('click', function () {
      cat = $(this).data('faq-cat') || ''; $tools.find('[data-faq-cat]').removeClass('is-active'); $(this).addClass('is-active'); apply();
    });
  });

  /* ---- Quote slider ------------------------------------------------------- */
  $('[data-quotes]').each(function () {
    var $q = $(this), $s = $q.find('.quotes__slide'), $d = $q.find('[data-q-dot]'), i = 0, timer = null, n = $s.length;
    function go(k) {
      i = (k + n) % n;
      $s.removeClass('is-active').attr('aria-hidden', true).eq(i).addClass('is-active').removeAttr('aria-hidden');
      $d.removeClass('is-active').eq(i).addClass('is-active');
    }
    function play() { if (reduceMotion || n < 2) { return; } stop(); timer = setInterval(function () { go(i + 1); }, 2000); }
    function stop() { clearInterval(timer); }
    $q.find('[data-q-next]').on('click', function () { go(i + 1); play(); });
    $q.find('[data-q-prev]').on('click', function () { go(i - 1); play(); });
    $d.on('click', function () { go($(this).data('q-dot')); play(); });
    $q.on('mouseenter focusin', stop).on('mouseleave focusout', play);
    var sx = 0;
    $q.on('touchstart', function (e) { sx = e.originalEvent.touches[0].clientX; }).on('touchend', function (e) {
      var dx = e.originalEvent.changedTouches[0].clientX - sx; if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); play(); } });
    $(document).on('visibilitychange', function () { document.hidden ? stop() : play(); });
    go(0); play();
  });

  /* ---- Init ----------------------------------------------------------------- */
  onScroll();
  $win.on('load', function () { parallax(); });

}(jQuery, window, document));
