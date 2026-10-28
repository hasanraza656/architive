<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $seoMeta['title'] }}</title>
    <meta name="description" content="{{ $seoMeta['description'] }}">
    <meta name="robots" content="{{ $seoMeta['robots'] }}">
    @php
        $canonical = $seoMeta['canonical'] ?? (rtrim(url()->current(), '/') . '/');
        $ogImage   = asset($seoMeta['image']);
    @endphp
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="author" content="{{ config('site.legal_name') }}">
    <meta name="application-name" content="{{ config('site.name') }}">
    <meta name="theme-color" content="#FBFBF9" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0E0E0E" media="(prefers-color-scheme: dark)">
    <meta name="color-scheme" content="light dark">

    {{-- Open Graph / Twitter --}}
    <meta property="og:site_name" content="{{ config('site.name') }}">
    <meta property="og:locale" content="{{ config('site.locale') }}">
    <meta property="og:type" content="{{ $seoMeta['type'] }}">
    <meta property="og:title" content="{{ $seoMeta['title'] }}">
    <meta property="og:description" content="{{ $seoMeta['description'] }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="{{ $seoMeta['image_w'] ?? 1200 }}">
    <meta property="og:image:height" content="{{ $seoMeta['image_h'] ?? 630 }}">
    <meta property="og:image:alt" content="{{ $seoMeta['image_alt'] ?? $seoMeta['title'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoMeta['title'] }}">
    <meta name="twitter:description" content="{{ $seoMeta['description'] }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Icons / manifest --}}
    <link rel="icon" href="{{ asset('assets/img/favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    {{-- Theme + JS flag before first paint: no flash, and reveal-animations only hide content when JS runs --}}
    <script>
        (function () {
            var d = document.documentElement;
            d.classList.add('js');
            try {
                var t = localStorage.getItem('architive-theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                d.setAttribute('data-theme', t);
               
            } catch (e) {}
        })();
    </script>

    {{-- Self-hosted variable fonts (no third-party request, swap = no invisible text) --}}
    <link rel="preload" href="{{ asset('assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('assets/fonts/playfair-display-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @stack('preload')

    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/base.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/widgets.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/portfolio.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/home.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/consent.css') }}">

    @include('partials.schema')
    @include('partials.analytics')
    @stack('head')
</head>
<body class="page-{{ str_replace('.', '-', Route::currentRouteName() ?? 'error') }}{{ ($overlay ?? false) ? ' has-overlay' : '' }}">
    <a class="skip-link" href="#main">Skip to main content</a>
    <div class="scroll-progress" aria-hidden="true"><span></span></div>
    {{-- The page loading screen was removed on purpose: pages open straight away. (To bring it back, restore the .curtain block and the loader code in app.js.) --}}

    @if ($lp ?? false)
        @include('partials.lp-header')   {{-- ad landing pages: no menu, so the visitor stays on the offer --}}
    @else
        @include('partials.header')
    @endif

    <main id="main" tabindex="-1">
        @yield('content')
    </main>

    @if ($lp ?? false)
        @include('partials.lp-footer')
    @else
        @include('partials.footer')
    @endif
    @unless (is_page('contact') || ($lp ?? false))
        @include('partials.enquiry-modal')
    @endunless
    @include('partials.consent-banner')
    @include('partials.tawk')

    <button class="to-top" type="button" aria-label="Back to top"><x-icon name="arrow-up" /></button>

    <noscript><style>.js [data-reveal],.js [data-split]{opacity:1!important;transform:none!important}</style></noscript>

    <script src="{{ asset('assets/vendor/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset_v('assets/js/app.js') }}"></script>
    <script src="{{ asset_v('assets/js/interactive.js') }}"></script>
    <script src="{{ asset_v('assets/js/lightbox.js') }}"></script>
    <script src="{{ asset_v('assets/js/consent.js') }}" defer></script>
    <script src="{{ asset_v('assets/js/attribution.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
