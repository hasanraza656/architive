{{-- Google Analytics 4 with Consent Mode v2. ID and switch: config/services.php -> GA4_MEASUREMENT_ID / GA4_ENABLED in .env.
     Visitors in the EEA, UK and Switzerland start with analytics storage DENIED until they accept the banner;
     everywhere else it starts granted and a visitor can decline. Ads storage is always denied (no ads run here).
     The tag loads after the page has finished loading so it never delays rendering. --}}
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
        gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
        try {
            var c = JSON.parse(localStorage.getItem('architive-consent') || 'null');
            if (c && typeof c.analytics === 'boolean') { gtag('consent', 'update', { analytics_storage: c.analytics ? 'granted' : 'denied' }); }
        } catch (e) {}
        gtag('js', new Date());
        gtag('config', '{{ $ga['ga4_id'] }}');
        window.architiveTrack = function (name, params) { try { gtag('event', name, params || {}); } catch (e) {} };
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
