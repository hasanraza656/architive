{{-- Sidebar for both portals; the menu items depend on who is signed in and, for team members, on their role's permissions. --}}
@php
    $u = auth()->user();
    $team = $u->isTeam();
    $inboxUnread = $team && $u->hasPermission('inbox.view') ? app(\App\Services\Chat\Inbox::class)->unreadCount($u) : 0;
    $seesOrders = $u->hasPermission('orders.view');
    $onReq = $team && request()->routeIs('admin.orders.index') && request('status') === 'request';
    $reqCount = $team && $seesOrders ? \App\Models\Order::where('status', 'request')->count() : 0;
    $openTasks = $team ? $u->openTaskCount() : 0;

    // [route, label, icon, active-match, permission needed (null = everyone on the team)]
    $links = $team
        ? [
            ['admin.dashboard', 'Dashboard', 'grid', 'admin.dashboard', 'dashboard.view'],
            ['admin.inbox', 'Inbox', 'mail', 'admin.inbox*', 'inbox.view'],
            ['admin.orders.index', $seesOrders ? 'Orders' : 'My chats', 'receipt', 'admin.orders.*', null],
            ['admin.projects.index', 'Projects', 'layers', 'admin.projects.*', null],
            ['admin.tasks.mine', 'Tasks', 'check-circle', 'admin.tasks.*', null],
            ['admin.blog.posts.index', 'Blog', 'file-text', 'admin.blog.*', 'blog.view'],
            ['admin.customers.index', 'Customers', 'users', 'admin.customers.*', 'customers.view'],
            ['admin.stripe.index', 'Invoices', 'credit-card', 'admin.stripe.*', 'stripe.view'],
            [$u->hasPermission('team.view') ? 'admin.team.users.index' : 'admin.team.roles.index', 'Team', 'shield', 'admin.team.*', ['team.view', 'team.manage_roles']],
            ['admin.settings', 'Settings', 'settings', 'admin.settings*', null],
            ...($u->isAdmin() ? [['admin.integrations.index', 'Integrations', 'zap', 'admin.integrations.*', null], ['admin.system.index', 'System', 'refresh', 'admin.system.*', null]] : []),
        ]
        : [
            ['customer.dashboard', 'My orders', 'receipt', ['customer.dashboard', 'customer.orders.*'], null],
            ['customer.invoices', 'Invoices', 'credit-card', 'customer.invoices', null],
            ['customer.profile', 'My profile', 'user', 'customer.profile*', null],
        ];
@endphp
<aside class="pside" aria-label="Main navigation">
    <a class="pside__brand" href="{{ $u->homeRoute() }}">
        <img src="{{ asset('assets/img/logo-light.png') }}" alt="Architive" width="198" height="34">
        <span class="pside__tag">{{ $u->isAdmin() ? 'Admin portal' : ($team ? 'Team portal' : 'Client area') }}</span>
    </a>

    <nav class="pside__nav">
        @if ($team)
            @if ($u->hasPermission('orders.create'))
                <a class="pbtn pbtn--primary pbtn--block" href="{{ route('admin.orders.create') }}" style="margin-bottom:.8rem"><x-icon name="plus" /> New order</a>
            @elseif ($u->hasPermission('tasks.create') || $u->hasPermission('projects.create'))
                <a class="pbtn pbtn--primary pbtn--block" href="{{ route('admin.projects.index') }}" style="margin-bottom:.8rem"><x-icon name="plus" /> New task</a>
            @endif
        @else
            <a class="pbtn pbtn--primary pbtn--block" href="{{ route('customer.requests.create') }}" style="margin-bottom:.8rem"><x-icon name="plus" /> New request</a>
        @endif
        <span class="pside__label">Menu</span>
        @foreach ($links as [$route, $label, $icon, $match, $perm])
            @continue($perm && ! $u->hasAnyPermission(...(array) $perm))
            @if ($team && $route === 'admin.orders.index' && $seesOrders)
                <a class="pnav {{ $onReq ? 'is-active' : '' }}" href="{{ route('admin.orders.index', ['status' => 'request']) }}"><x-icon name="message" /> Requests @if ($reqCount)<span class="pnav__count">{{ $reqCount }}</span>@endif</a>
            @endif
            @php $active = request()->routeIs($match) && ! ($team && $route === 'admin.orders.index' && $onReq); @endphp
            <a class="pnav {{ $active ? 'is-active' : '' }}" href="{{ route($route) }}" @if ($active) aria-current="page" @endif>
                <x-icon :name="$icon" /> {{ $label }}
                @if ($route === 'admin.inbox')<span class="pnav__count" data-inbox-count @if (! $inboxUnread) hidden @endif>{{ $inboxUnread }}</span>@endif
                @if ($route === 'admin.tasks.mine' && $openTasks)<span class="pnav__count pnav__count--soft">{{ $openTasks }}</span>@endif
            </a>
        @endforeach
        <span class="pside__label">Website</span>
        <a class="pnav" href="{{ pu('home') }}" target="_blank" rel="noopener"><x-icon name="external" /> architive.net</a>
    </nav>

    <div class="pside__foot">
        <div class="pside__me">
            <span class="pavatar">@if ($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" alt="" width="38" height="38">@else{{ $u->initials }}@endif</span>
            <div><b>{{ $u->name }}</b><small>{{ $u->isAdmin() ? 'Super admin' : ($u->teamRole?->name ?? $u->email) }}</small></div>
        </div>
        <form method="post" action="{{ route($team ? 'admin.logout' : 'customer.logout') }}">
            @csrf
            <button class="pside__logout" type="submit"><x-icon name="log-out" /> Sign out</button>
        </form>
    </div>
</aside>
