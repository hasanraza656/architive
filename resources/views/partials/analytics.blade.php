{{-- Google Analytics 4 + optional Google Ads tag, with Consent Mode v2. IDs and switches: config/services.php -> GA4_* / GOOGLE_ADS_* in .env.
     Visitors in the EEA, UK and Switzerland start with everything DENIED until they accept the banner;
     everywhere else analytics and ads measurement start granted and a visitor can decline.
     The tag loads after the page has finished loading so it never delays rendering.
     Events (window.architiveTrack): generate_lead (form sent, also the Ads lead conversion) and contact_click (WhatsApp / phone / email tap).
     Ad-click details (utm_*, gclid, landing page, referrer) are kept per visitor and sent with the enquiry form (public/assets/js/attribution.js). --}}
@php $ga = config('services.analytics'); @endphp
@if (! empty($ga['enabled']) && ! empty($ga['ga4_id']))
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('consent', 'default', {
            analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
            region: ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','IS','LI','NO','GB','CH'],
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
            var h = l.getAttribute('href') || '', m = /^tel:/i.test(h) ? 'phone' : (/^mailto:/i.test(h) ? 'email' : (/(wa\.me|api\.whatsapp\.com)\//i.test(h) ? 'whatsapp' : null));
            if (m) { window.architiveTrack('contact_click', { method: m, link_location: l.closest('header') ? 'header' : (l.closest('footer') ? 'footer' : 'page') }); }
        }, true);
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
