{{-- Google Analytics 4 + optional Google Ads tag, with Consent Mode v2. IDs and switches: config/services.php -> GA4_* / GOOGLE_ADS_* in .env.
     Visitors in the EU and the UK start with everything DENIED until they accept the banner;
     everywhere else analytics and ads measurement start granted and a visitor can decline.
     The tag loads after the page has finished loading so it never delays rendering.
     Events (window.architiveTrack): generate_lead (form sent, also the Ads lead conversion) and contact_click (WhatsApp / phone / email / chat tap, also an Ads conversion).
     GA4-only events: cta_click, form_start, scroll_depth, file_download, outbound_click, view_work_click.
     Ad-click details (utm_*, gclid, landing page, referrer) are kept per visitor and sent with the enquiry form (public/assets/js/attribution.js). --}}
@php $ga = config('services.analytics'); @endphp
@if (! empty($ga['enabled']) && ! empty($ga['ga4_id']))
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('consent', 'default', {
            analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
            region: ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB'],
            wait_for_update: 500
        });
        gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted' });
        try {
            var c = JSON.parse(localStorage.getItem('architive-consent') || 'null');
            if (c && typeof c.analytics === 'boolean') {
                var v = c.analytics ? 'granted' : 'denied';
                gtag('consent', 'update', { analytics_storage: v, ad_storage: v, ad_user_data: v, ad_personalization: v });
            }
        } catch (e) {}
        gtag('js', new Date());
        gtag('config', '{{ $ga['ga4_id'] }}');
        @if (! empty($ga['ads_id']))
        gtag('config', '{{ $ga['ads_id'] }}', { allow_enhanced_conversions: true });
        @endif
        window.architiveAds = { id: @json($ga['ads_id'] ?: null), lead: @json($ga['ads_lead_label'] ?: null), contact: @json($ga['ads_contact_label'] ?: null) };
        window.architiveTrack = function (name, params) {
            try {
                gtag('event', name, params || {});
                var a = window.architiveAds, label = name === 'generate_lead' ? a.lead : (name === 'contact_click' ? a.contact : null);
                if (a.id && label) { gtag('event', 'conversion', { send_to: a.id + '/' + label }); }
            } catch (e) {}
        };
        // Taps on WhatsApp, phone and email links anywhere on the site
        document.addEventListener('click', function (e) {
            var l = e.target.closest && e.target.closest('a[href]');
            if (!l) { return; }
            var h = l.getAttribute('href') || '', m = /^tel:/i.test(h) ? 'phone' : (/^mailto:/i.test(h) ? 'email' : (/(wa\.me\/\d|api\.whatsapp\.com\/send\?phone=|whatsapp:\/\/send\?phone=)/i.test(h) ? 'whatsapp' : null));
            if (m) { window.architiveTrack('contact_click', { method: m, link_location: l.closest('header') ? 'header' : (l.closest('footer') ? 'footer' : 'page') }); }
        }, true);
        // Every other useful click: buttons and quote links (cta_click), files (file_download), other sites (outbound_click), sample work (view_work_click). GA4 only, never an Ads conversion.
        document.addEventListener('click', function (e) {
            var l = e.target.closest && e.target.closest('a[href], button[data-bs-target], [data-enquiry], .btn-ay');
            if (!l) { return; }
            var h = l.getAttribute('href') || '', txt = (l.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 60);
            var where = l.closest('header') ? 'header' : (l.closest('footer') ? 'footer' : 'page');
            if (/^(tel:|mailto:)/i.test(h) || /wa\.me|whatsapp/i.test(h)) { return; }
            if (/\.(pdf|docx?|xlsx?|zip|pptx?)(\?|$)/i.test(h)) { window.architiveTrack('file_download', { file_url: h, link_text: txt }); return; }
            if (/^https?:\/\//i.test(h) && h.indexOf(location.host) === -1) { window.architiveTrack('outbound_click', { link_url: h, link_text: txt }); return; }
            if (l.matches('.btn-ay, [data-enquiry], button[data-bs-target]') || /\/(contact|quote|request)/i.test(h)) {
                window.architiveTrack('cta_click', { link_text: txt, link_url: h, link_location: where, page_path: location.pathname });
            } else if (/\/(portfolio|work|sample|case-stud)/i.test(h)) {
                window.architiveTrack('view_work_click', { link_text: txt, link_url: h, link_location: where });
            }
        }, true);
        // Someone starts typing in an enquiry form, and how far down the page they read
        (function () {
            var started = false, marks = { 50: false, 90: false };
            document.addEventListener('focusin', function (e) {
                var f = e.target.closest && e.target.closest('form[data-contact-form]');
                if (f && !started) { started = true; window.architiveTrack('form_start', { form_name: f.getAttribute('data-form-name') || 'enquiry', page_path: location.pathname }); }
            });
            window.addEventListener('scroll', function () {
                var d = document.documentElement, p = Math.round((window.scrollY + window.innerHeight) / Math.max(d.scrollHeight, 1) * 100);
                [50, 90].forEach(function (m) { if (!marks[m] && p >= m) { marks[m] = true; window.architiveTrack('scroll_depth', { percent: m, page_path: location.pathname }); } });
            }, { passive: true });
        })();
        (function () {
            function load() {
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id={{ $ga['ga4_id'] }}';
                document.head.appendChild(s);
            }
            if (document.readyState === 'complete') { load(); } else { window.addEventListener('load', load); }
        })();
    </script>
@endif
