{{-- App icons for browser tabs, "Add to home screen" / "Install app" (Android, iPhone) and the web app manifest.
     Included in the <head> of the website layout and of the admin / customer portal layouts.
     Icon files: public/assets/img/icon-192.png, icon-512.png, icon-maskable-512.png, apple-touch-icon.png (180x180), favicon-32/48.png.
     They are solid (no transparent areas), so phones never fall back to a letter. --}}
<link rel="icon" href="{{ asset_v('assets/img/favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset_v('assets/img/favicon-48.png') }}" type="image/png" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset_v('site.webmanifest') }}">
<meta name="application-name" content="Architive">
<meta name="apple-mobile-web-app-title" content="Architive">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
