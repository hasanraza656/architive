@extends('portal.layouts.app')
@section('title', 'Orders')
@section('heading', 'Orders')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Orders</h1>
            <p class="phead__sub">Every invoice and project, from first draft to delivery.</p>
        </div>
        <div class="phead__actions"><a class="pbtn pbtn--primary" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> New order</a></div>
    </div>

    <div class="toolbar">
        <div class="pills" role="navigation" aria-label="Filter by status">
            <a class="pill {{ ! $status ? 'is-on' : '' }}" href="{{ route('admin.orders.index', array_filter(['q' => $q])) }}">All <small>{{ $counts->sum() }}</small></a>
            @foreach (\App\Enums\OrderStatus::cases() as $s)
                <a class="pill {{ $status === $s->value ? 'is-on' : '' }}" href="{{ route('admin.orders.index', array_filter(['status' => $s->value, 'q' => $q])) }}">{{ $s->label() }} <small>{{ $counts[$s->value] ?? 0 }}</small></a>
            @endforeach
        </div>
        <form class="search" method="get" action="{{ route('admin.orders.index') }}" role="search">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <x-icon name="search" />
            <input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search order, title or customer" aria-label="Search orders">
        </form>
    </div>

    <div class="pcard">
        @if ($orders->count())
            <table class="ptable ptable--cards">
                <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Due</th><th class="num">Total</th></tr></thead>
                <tbody>
                @foreach ($orders as $o)
                    <tr>
                        <td class="cell-main"><a href="{{ route('admin.orders.show', $o) }}"><b class="strong">{{ $o->title }}</b><br><small class="muted mono">{{ $o->number }} · <time data-dt="date" datetime="{{ $o->created_at->toIso8601String() }}">{{ $o->created_at->format('M j, Y') }}</time></small></a></td>
                        <td data-label="Customer"><div class="who"><span class="pavatar pavatar--sm pavatar--soft">{{ $o->customer->initials }}</span><div><b>{{ $o->customer->name }}</b></div></div></td>
                        <td data-label="Status"><x-portal.badge :status="$o->status" /></td>
                        <td data-label="Due">@if ($o->due_at)<time data-dt="date" datetime="{{ $o->due_at->toIso8601String() }}">{{ $o->due_at->format('M j, Y') }}</time>@else<span class="muted">-</span>@endif</td>
                        <td class="num strong" data-label="Total">{{ money($o->total_cents, $o->currency) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($orders->hasPages())<div class="pager">{{ $orders->links() }}</div>@endif
        @else
            <div class="empty"><x-icon name="receipt" /><b>No orders found</b><span>{{ $q || $status ? 'Try a different filter or search.' : 'Create your first order to get started.' }}</span><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> New order</a></div>
        @endif
    </div>
@endsection
