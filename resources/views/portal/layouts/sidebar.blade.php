{{-- Sidebar for both portals; the menu items depend on who is signed in. --}}
@php
    $u = auth()->user();
    $admin = $u->isAdmin();
    $links = $admin
        ? [
            ['admin.dashboard', 'Dashboard', 'grid', 'admin.dashboard'],
            ['admin.orders.index', 'Orders', 'receipt', 'admin.orders.*'],
            ['admin.customers.index', 'Customers', 'users', 'admin.customers.*'],
            ['admin.settings', 'Settings', 'settings', 'admin.settings*'],
        ]
        : [
            ['customer.dashboard', 'My orders', 'receipt', ['customer.dashboard', 'customer.orders.*']],
            ['customer.profile', 'My profile', 'user', 'customer.profile*'],
        ];
@endphp
<aside class="pside" aria-label="Main navigation">
    <a class="pside__brand" href="{{ $u->homeRoute() }}">
        <img src="{{ asset('assets/img/logo-light.png') }}" alt="Architive" width="198" height="34">
        <span class="pside__tag">{{ $admin ? 'Admin portal' : 'Client area' }}</span>
    </a>

    <nav class="pside__nav">
        @if ($admin)
            <a class="pbtn pbtn--primary pbtn--block" href="{{ route('admin.orders.create') }}" style="margin-bottom:.8rem"><x-icon name="plus" /> New order</a>
        @endif
        <span class="pside__label">Menu</span>
        @foreach ($links as [$route, $label, $icon, $match])
            <a class="pnav {{ request()->routeIs($match) ? 'is-active' : '' }}" href="{{ route($route) }}" @if (request()->routeIs($match)) aria-current="page" @endif>
                <x-icon :name="$icon" /> {{ $label }}
            </a>
        @endforeach
        <span class="pside__label">Website</span>
        <a class="pnav" href="{{ pu('home') }}" target="_blank" rel="noopener"><x-icon name="external" /> architive.net</a>
    </nav>

    <div class="pside__foot">
        <div class="pside__me">
            <span class="pavatar">{{ $u->initials }}</span>
            <div><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div>
        </div>
        <form method="post" action="{{ route($admin ? 'admin.logout' : 'customer.logout') }}">
            @csrf
            <button class="pside__logout" type="submit"><x-icon name="log-out" /> Sign out</button>
        </form>
    </div>
</aside>
