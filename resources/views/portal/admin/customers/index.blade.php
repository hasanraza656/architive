@extends('portal.layouts.app')
@section('title', 'Customers')
@section('heading', 'Customers')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Customers</h1>
            <p class="phead__sub">People you invoice. They sign in with an e-mailed code, no password needed.</p>
        </div>
        <div class="phead__actions"><a class="pbtn pbtn--primary" href="{{ route('admin.customers.create') }}"><x-icon name="plus" /> New customer</a></div>
    </div>

    <div class="toolbar">
        <form class="search" method="get" action="{{ route('admin.customers.index') }}" role="search">
            <x-icon name="search" />
            <input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search name, e-mail or phone" aria-label="Search customers">
        </form>
        <span class="muted">{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }}</span>
    </div>

    <div class="pcard">
        @if ($customers->count())
            <table class="ptable ptable--cards">
                <thead><tr><th>Customer</th><th>Phone</th><th class="num">Orders</th><th class="num">Paid</th><th>Joined</th></tr></thead>
                <tbody>
                @foreach ($customers as $c)
                    <tr>
                        <td class="cell-main"><a class="who" href="{{ route('admin.customers.show', $c) }}"><span class="pavatar pavatar--soft">{{ $c->initials }}</span><div><b>{{ $c->name }} @unless ($c->is_active)<span class="pbadge pbadge--grey">Disabled</span>@endunless</b><small>{{ $c->email }}</small></div></a></td>
                        <td data-label="Phone">{{ $c->phone ?: '-' }}</td>
                        <td class="num" data-label="Orders">{{ $c->orders_count }}</td>
                        <td class="num strong" data-label="Paid">{{ money($c->paid_cents ?? 0) }}</td>
                        <td data-label="Joined"><time data-dt="date" datetime="{{ $c->created_at->toIso8601String() }}">{{ $c->created_at->format('M j, Y') }}</time></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($customers->hasPages())<div class="pager">{{ $customers->links() }}</div>@endif
        @else
            <div class="empty"><x-icon name="users" /><b>No customers found</b><span>{{ $q ? 'Try a different search.' : 'Add your first customer, or create one while building an order.' }}</span><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.customers.create') }}"><x-icon name="plus" /> New customer</a></div>
        @endif
    </div>
@endsection
