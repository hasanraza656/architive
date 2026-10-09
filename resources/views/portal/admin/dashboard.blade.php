@extends('portal.layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Hello, <em>{{ auth()->user()->first_name }}</em></h1>
            <p class="phead__sub">Here is what is happening across your studio today.</p>
        </div>
        <div class="phead__actions">
            <a class="pbtn pbtn--ghost" href="{{ route('admin.customers.create') }}"><x-icon name="user" /> New customer</a>
            <a class="pbtn pbtn--primary" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> New order</a>
        </div>
    </div>

    <div class="stats">
        <a class="stat stat--accent" href="{{ route('admin.customers.index') }}">
            <span class="stat__ic"><x-icon name="users" /></span>
            <span class="stat__num">{{ number_format($stats['customers']) }}</span>
            <span class="stat__label">Customers</span>
        </a>
        <a class="stat" href="{{ route('admin.orders.index') }}">
            <span class="stat__ic"><x-icon name="receipt" /></span>
            <span class="stat__num">{{ number_format($stats['orders']) }}</span>
            <span class="stat__label">Total orders</span>
        </a>
        <a class="stat stat--blue" href="{{ route('admin.orders.index', ['status' => 'active']) }}">
            <span class="stat__ic"><x-icon name="zap" /></span>
            <span class="stat__num">{{ number_format($stats['active']) }}</span>
            <span class="stat__label">Active orders</span>
        </a>
        <a class="stat stat--green" href="{{ route('admin.orders.index', ['status' => 'completed']) }}">
            <span class="stat__ic"><x-icon name="check-circle" /></span>
            <span class="stat__num">{{ number_format($stats['completed']) }}</span>
            <span class="stat__label">Completed</span>
        </a>
        <a class="stat stat--red" href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}">
            <span class="stat__ic"><x-icon name="x" /></span>
            <span class="stat__num">{{ number_format($stats['cancelled']) }}</span>
            <span class="stat__label">Cancelled</span>
        </a>
        <a class="stat stat--amber" href="{{ route('admin.orders.index', ['status' => 'pending']) }}">
            <span class="stat__ic"><x-icon name="clock" /></span>
            <span class="stat__num">{{ number_format($stats['awaiting']) }}</span>
            <span class="stat__label">Awaiting payment</span>
        </a>
        <div class="stat" style="grid-column: span 2">
            <span class="stat__ic"><x-icon name="dollar" /></span>
            <span class="stat__num">{{ money($stats['revenue']) }}</span>
            <span class="stat__label">Paid revenue</span>
        </div>
    </div>

    <div class="pgrid pgrid--2">
        <section class="pcard" aria-labelledby="due-h">
            <div class="pcard__head"><h2 class="pcard__title" id="due-h"><x-icon name="calendar" /> Upcoming due</h2><a class="plink" href="{{ route('admin.orders.index', ['status' => 'active']) }}">All active</a></div>
            @forelse ($upcoming as $o)
                @php $late = $o->due_at->isPast(); $soon = ! $late && $o->due_at->lt(now()->addDays(2)); @endphp
                <a class="list-row" href="{{ route('admin.orders.show', $o) }}">
                    <span class="pavatar pavatar--soft">{{ $o->customer->initials }}</span>
                    <span class="list-row__main"><b>{{ $o->title }}</b><small>{{ $o->number }} · {{ $o->customer->name }}</small></span>
                    <span class="chip {{ $late ? 'chip--late' : ($soon ? 'chip--soon' : '') }}"><x-icon name="clock" /> {{ $late ? 'Overdue ' : '' }}<time data-dt="short" datetime="{{ $o->due_at->toIso8601String() }}">{{ $o->due_at->format('M j, H:i') }}</time></span>
                </a>
            @empty
                <div class="empty"><x-icon name="calendar" /><b>Nothing due soon</b><span>Orders with a due date will show up here once they are paid.</span></div>
            @endforelse
        </section>

        <section class="pcard" aria-labelledby="unread-h">
            <div class="pcard__head"><h2 class="pcard__title" id="unread-h"><x-icon name="message" /> Needs your reply</h2></div>
            @forelse ($unread as $o)
                <a class="list-row" href="{{ route('admin.orders.show', $o) }}#chat">
                    <span class="pavatar">{{ $o->customer->initials }}</span>
                    <span class="list-row__main"><b>{{ $o->customer->name }}</b><small>{{ $o->number }} · {{ $o->title }}</small></span>
                    <span class="dot-unread" aria-label="Unread messages">●</span>
                </a>
            @empty
                <div class="empty"><x-icon name="check-circle" /><b>All caught up</b><span>No unread customer messages.</span></div>
            @endforelse
        </section>
    </div>

    <div class="pgrid pgrid--2" style="margin-top:1.1rem">
        <section class="pcard" aria-labelledby="recent-h">
            <div class="pcard__head"><h2 class="pcard__title" id="recent-h"><x-icon name="receipt" /> Recent orders</h2><a class="plink" href="{{ route('admin.orders.index') }}">View all</a></div>
            @forelse ($recent as $o)
                <a class="list-row" href="{{ route('admin.orders.show', $o) }}">
                    <span class="list-row__main"><b>{{ $o->title }}</b><small>{{ $o->number }} · {{ $o->customer->name }}</small></span>
                    <span class="strong">{{ money($o->total_cents, $o->currency) }}</span>
                    <x-portal.badge :status="$o->status" />
                </a>
            @empty
                <div class="empty"><x-icon name="receipt" /><b>No orders yet</b><span>Create your first order to get started.</span><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> New order</a></div>
            @endforelse
        </section>

        <section class="pcard" aria-labelledby="act-h">
            <div class="pcard__head"><h2 class="pcard__title" id="act-h"><x-icon name="bell" /> Latest activity</h2></div>
            <div class="timeline">
                @forelse ($activity as $e)
                    <a class="tl tl--{{ $e->type }}" href="{{ route('admin.orders.show', $e->order) }}">
                        <span class="tl__dot"></span>
                        <span><b>{{ $e->message }}</b><small>{{ $e->order->number }} · <time data-dt="short" datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->format('M j, H:i') }}</time></small></span>
                    </a>
                @empty
                    <div class="empty"><x-icon name="bell" /><b>No activity yet</b></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
