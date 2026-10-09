<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · Architive</title>
    <link rel="icon" href="{{ asset('assets/img/favicon-32.png') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal.css') }}">
</head>
<body class="portal portal-auth">
<section class="auth__art" style="--bg-img: url('{{ asset('assets/img/hero.webp') }}')" aria-hidden="false">
    <a href="{{ pu('home') }}" aria-label="Architive website"><img class="logo" src="{{ asset('assets/img/logo-light.png') }}" alt="Architive" width="220" height="38"></a>
    <div>
        @yield('art')
    </div>
    <small>© {{ date('Y') }} {{ config('site.legal_name') }}</small>
</section>

<main class="auth__panel" id="main">
    <div class="auth__card">
        <a class="auth__mini" href="{{ pu('home') }}" aria-label="Architive website"><img src="{{ asset('assets/img/logo.png') }}" alt="Architive" width="198" height="34"></a>
        @include('portal.components.flash')
        @yield('content')
        <div class="auth__links">
            <a href="{{ pu('home') }}">← Back to website</a>
            @yield('links')
        </div>
    </div>
</main>

<script src="{{ asset_v('assets/js/portal.js') }}"></script>
</body>
</html>
