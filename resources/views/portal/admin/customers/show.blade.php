@extends('portal.layouts.app')
@section('title', $customer->name)
@section('heading', 'Customers')

@section('content')
    <a class="crumb" href="{{ route('admin.customers.index') }}"><x-icon name="arrow-left" /> All customers</a>
    <div class="phead">
        <div class="who">
            <span class="pavatar" style="width:56px;height:56px;font-size:1rem">{{ $customer->initials }}</span>
            <div>
                <h1 class="phead__title" style="font-size:clamp(1.6rem,3vw,2.2rem)">{{ $customer->name }}</h1>
                <p class="phead__sub" style="margin-top:.1rem">{{ $customer->email }} @unless ($customer->is_active)<span class="pbadge pbadge--grey">Disabled</span>@endunless</p>
            </div>
        </div>
        <div class="phead__actions">
            <a class="pbtn pbtn--ghost" href="{{ route('admin.customers.edit', $customer) }}"><x-icon name="edit" /> Edit</a>
            <a class="pbtn pbtn--primary" href="{{ route('admin.orders.create', ['customer' => $customer->id]) }}"><x-icon name="plus" /> New order</a>
        </div>
    </div>

    <div class="pgrid pgrid--main">
        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="receipt" /> Orders ({{ $customer->orders->count() }})</h2></div>
            @forelse ($customer->orders as $o)
                <a class="list-row" href="{{ route('admin.orders.show', $o) }}">
                    <span class="list-row__main"><b>{{ $o->title }}</b><small>{{ $o->number }} · <time data-dt="date" datetime="{{ $o->created_at->toIso8601String() }}">{{ $o->created_at->format('M j, Y') }}</time></small></span>
                    <span class="strong">{{ money($o->total_cents, $o->currency) }}</span>
                    <x-portal.badge :status="$o->status" />
                </a>
            @empty
                <div class="empty"><x-icon name="receipt" /><b>No orders yet</b><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.orders.create', ['customer' => $customer->id]) }}"><x-icon name="plus" /> Create the first order</a></div>
            @endforelse
        </section>

        <aside class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="user" /> Details</h2></div>
            <div class="pcard__body">
                <dl class="kv">
                    <div><dt>E-mail</dt><dd>{{ $customer->email }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $customer->phone ?: '-' }}</dd></div>
                    <div><dt>Customer since</dt><dd><time data-dt="date" datetime="{{ $customer->created_at->toIso8601String() }}">{{ $customer->created_at->format('M j, Y') }}</time></dd></div>
                    <div><dt>Last sign-in</dt><dd>@if ($customer->last_login_at)<time data-dt="datetime" datetime="{{ $customer->last_login_at->toIso8601String() }}">{{ $customer->last_login_at->format('M j, Y H:i') }}</time>@else Never @endif</dd></div>
                    <div><dt>Total paid</dt><dd>{{ money($customer->orders->whereNotNull('paid_at')->sum('total_cents')) }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
@endsection
