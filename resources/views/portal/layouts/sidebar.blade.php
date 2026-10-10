{{-- Sidebar for both portals; the menu items depend on who is signed in. --}}
@php
    $u = auth()->user();
    $admin = $u->isAdmin();
    $inboxUnread = $admin ? app(\App\Services\Chat\Inbox::class)->unreadCount() : 0;
    $links = $admin
        ? [
            ['admin.dashboard', 'Dashboard', 'grid', 'admin.dashboard'],
            ['admin.inbox', 'Inbox', 'mail', 'admin.inbox*'],
            ['admin.orders.index', 'Orders', 'receipt', 'admin.orders.*'],
            ['admin.blog.posts.index', 'Blog', 'file-text', 'admin.blog.*'],
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
        @else
            <a class="pbtn pbtn--primary pbtn--block" href="{{ route('customer.requests.create') }}" style="margin-bottom:.8rem"><x-icon name="plus" /> New request</a>
        @endif
        <span class="pside__label">Menu</span>
        @if ($admin)
            @php $reqCount = \App\Models\Order::where('status', 'request')->count(); $onReq = request()->routeIs('admin.orders.index') && request('status') === 'request'; @endphp
        @endif
        @foreach ($links as [$route, $label, $icon, $match])
            @if ($admin && $route === 'admin.orders.index')
                <a class="pnav {{ $onReq ? 'is-active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'request']) }}"><x-icon name="message" /> Requests @if ($reqCount)<span class="pnav__count">{{ $reqCount }}</span>@endif</a>
            @endif
            @php $active = request()->routeIs($match) && ! ($admin && $route === 'admin.orders.index' && $onReq); @endphp
            <a class="pnav {{ $active ? 'is-active' : '' }}" href="{{ route($route) }}" @if ($active) aria-current="page" @endif>
                <x-icon :name="$icon" /> {{ $label }}
                @if ($admin && $route === 'admin.inbox')<span class="pnav__count" data-inbox-count @if (! $inboxUnread) hidden @endif>{{ $inboxUnread }}</span>@endif
            </a>
        @endforeach
        <span class="pside__label">Website</span>
        <a class="pnav" href="{{ pu('home') }}" target="_blank" rel="noopener"><x-icon name="external" /> architive.net</a>
    </nav>

    <div class="pside__foot">
        <div class="pside__me">
            <span class="pavatar">@if ($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" alt="" width="38" height="38">@else{{ $u->initials }}@endif</span>
            <div><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div>
        </div>
        <form method="post" action="{{ route($admin ? 'admin.logout' : 'customer.logout') }}">
            @csrf
            <button class="pside__logout" type="submit"><x-icon name="log-out" /> Sign out</button>
        </form>
    </div>
</aside>
