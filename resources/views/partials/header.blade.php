<header class="site-header" id="siteHeader">
    <div class="wrap wrap--wide header-inner">
        <a class="header-logo" href="{{ pu('home') }}" aria-label="Architive – home">
            <x-logo />
        </a>

        <nav class="main-nav d-none d-lg-block" aria-label="Primary">
            <ul class="main-nav__list">
                @foreach (config('site.nav') as $item)
                    @php
                        $isActive = $item['route'] === 'home'
                            ? is_page('home')
                            : (isset($item['children']) ? is_page('services.*') : is_page($item['route'], $item['route'] . '.*'));
                    @endphp
                    @if (! empty($item['children']))
                        <li class="main-nav__item has-mega {{ $isActive ? 'is-active' : '' }}">
                            <a class="main-nav__link" href="{{ pu($item['route']) }}" @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                            <button class="mega-toggle" type="button" aria-expanded="false" aria-controls="mega-services" aria-label="Show services menu">
                                <x-icon name="chevron-down" />
                            </button>
                            <div class="mega" id="mega-services">
                                <div class="mega__grid">
                                    @foreach ($item['children'] as $child)
                                        <a class="mega__link" href="{{ pu($child['route']) }}">
                                            <span class="mega__icon"><x-icon :name="$child['icon']" /></span>
                                            <span>
                                                <strong>{{ $child['label'] }}</strong>
                                                <small>{{ $child['text'] }}</small>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                                <div class="mega__more">
                                    @foreach ($item['more'] ?? [] as $more)
                                        <a href="{{ pu($more['route']) }}">{{ $more['label'] }} <x-icon name="arrow-right" /></a>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @else
                        <li class="main-nav__item {{ $isActive ? 'is-active' : '' }}">
                            <a class="main-nav__link" href="{{ pu($item['route']) }}" @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>

        <div class="header-actions">
            <button class="theme-toggle" type="button" aria-label="Switch to dark mode" data-theme-toggle>
                <x-icon name="moon" class="ic theme-toggle__moon" />
                <x-icon name="sun" class="ic theme-toggle__sun" />
            </button>
            <a class="btn-ay btn-ay--sm d-none d-sm-inline-flex" href="{{ pu('contact') }}">Let's talk <x-icon name="arrow-right" /></a>
            <button class="menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-end mobile-menu" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="mobile-menu__top">
        <span class="eyebrow" id="mobileMenuLabel">Menu</span>
        <button type="button" class="menu-close" data-bs-dismiss="offcanvas" aria-label="Close menu"><x-icon name="x" /></button>
    </div>
    <nav class="mobile-menu__nav" aria-label="Mobile">
        <ul>
            @foreach (config('site.nav') as $item)
                <li style="--i: {{ $loop->index }}">
                    <a href="{{ pu($item['route']) }}">{{ $item['label'] }}</a>
                    @if (! empty($item['children']))
                        <ul class="mobile-menu__sub">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ pu($child['route']) }}"><x-icon :name="$child['icon']" /> {{ $child['label'] }}</a></li>
                            @endforeach
                            @foreach ($item['more'] ?? [] as $more)
                                @if ($more['route'] !== 'services.index')
                                    <li><a href="{{ pu($more['route']) }}"><x-icon name="users" /> {{ $more['label'] }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="mobile-menu__foot">
        <a class="btn-ay btn-ay--block" href="{{ pu('contact') }}">Start your project <x-icon name="arrow-right" /></a>
        <a class="mobile-menu__mail" href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>
        <p class="mono-note">Free consultation or a small paid pilot. No commitment until you approve the scope.</p>
    </div>
</div>
