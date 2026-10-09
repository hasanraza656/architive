<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') · Architive</title>
    <link rel="icon" href="{{ asset('assets/img/favicon-32.png') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal.css') }}">
    @stack('head')
</head>
<body class="portal">
<a class="skip-link" href="#main">Skip to content</a>

<div class="pshell">
    @include('portal.layouts.sidebar')

    <div class="pmain">
        <header class="ptop">
            <button class="ptop__burger" type="button" data-nav-toggle aria-label="Open menu" aria-expanded="false"><x-icon name="menu" /></button>
            <span class="ptop__title">@yield('heading', 'Portal')</span>
            <span class="ptop__space"></span>
            <div class="ptop__actions">@yield('top-actions')</div>
        </header>

        <main id="main" class="pcontent">
            @include('portal.components.flash')
            @yield('content')
        </main>

        <footer class="pfoot no-print">© {{ date('Y') }} {{ config('site.legal_name') }} · <a href="{{ pu('privacy') }}" class="plink">Privacy</a></footer>
    </div>
</div>
<div class="pscrim" aria-hidden="true"></div>

<script src="{{ asset_v('assets/js/portal.js') }}"></script>
@stack('scripts')
</body>
</html>
