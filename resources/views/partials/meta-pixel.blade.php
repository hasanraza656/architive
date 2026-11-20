{{-- Meta (Facebook) Pixel. ID and switch: config/services.php -> META_PIXEL_ID / META_PIXEL_ENABLED in .env.
     Respects the cookie banner: visitors in the EU / UK (guessed the same way as public/assets/js/consent.js) and anyone who declined
     start with consent revoked, and consent.js grants it when they accept. --}}
@php $mp = config('services.meta_pixel'); @endphp
@if (! empty($mp['enabled']) && ! empty($mp['id']))
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
(function () {
    var ask = false, c = null;
    try {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        ask = /^(Europe\/(Amsterdam|Athens|Berlin|Bratislava|Brussels|Bucharest|Budapest|Busingen|Copenhagen|Dublin|Helsinki|Lisbon|Ljubljana|London|Belfast|Guernsey|Isle_of_Man|Jersey|Gibraltar|Luxembourg|Madrid|Malta|Mariehamn|Paris|Prague|Riga|Rome|Sofia|Stockholm|Tallinn|Vienna|Vilnius|Warsaw|Zagreb|Nicosia)|Asia\/(Nicosia|Famagusta)|Atlantic\/(Canary|Madeira|Azores))$/.test(tz);
        var EU = 'AT BE BG HR CY CZ DK EE FI FR DE GR HU IE IT LV LT LU MT NL PL PT RO SK SI ES SE GB'.split(' ');
        var langs = navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language || ''];
        for (var i = 0; i < langs.length && !ask; i++) { var m = /[-_]([A-Za-z]{2})$/.exec(langs[i]); if (m && EU.indexOf(m[1].toUpperCase()) !== -1) { ask = true; } }
        c = JSON.parse(localStorage.getItem('architive-consent') || 'null');
    } catch (e) {}
    var chosen = c && typeof c.analytics === 'boolean';
    if (chosen ? !c.analytics : ask) { fbq('consent', 'revoke'); }
})();
fbq('init', '{{ $mp['id'] }}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $mp['id'] }}&ev=PageView&noscript=1" alt=""/></noscript>
<!-- End Meta Pixel Code -->
@endif
