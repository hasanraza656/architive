@extends('portal.layouts.app')
@section('title', 'My orders')
@section('heading', 'My orders')

@section('content')
    @php use App\Enums\OrderStatus as S; @endphp
    <div class="phead">
        <div>
            <h1 class="phead__title">Welcome, <em>{{ auth()->user()->first_name }}</em></h1>
            <p class="phead__sub">Your invoices, projects and deliveries from Architive.</p>
        </div>
        <div class="phead__actions"><a class="pbtn pbtn--ghost" href="{{ pu('contact') }}"><x-icon name="message" /> Start a new project</a></div>
    </div>

    <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div class="stat stat--amber"><span class="stat__ic"><x-icon name="credit-card" /></span><span class="stat__num">{{ $stats['awaiting'] }}</span><span class="stat__label">Awaiting payment</span></div>
        <div class="stat stat--blue"><span class="stat__ic"><x-icon name="zap" /></span><span class="stat__num">{{ $stats['running'] }}</span><span class="stat__label">In progress</span></div>
        <div class="stat stat--green"><span class="stat__ic"><x-icon name="check-circle" /></span><span class="stat__num">{{ $stats['completed'] }}</span><span class="stat__label">Completed</span></div>
    </div>

    <section class="pcard" aria-label="Your orders">
        @forelse ($orders as $o)
            @php $needs = $o->status === S::Pending ? 'Pay now' : ($o->status === S::Delivered ? 'Review delivery' : null); @endphp
            <a class="list-row" href="{{ route('customer.orders.show', $o) }}">
                <span class="list-row__main">
                    <b>{{ $o->title }}</b>
                    <small>{{ $o->number }} · <time data-dt="date" datetime="{{ $o->created_at->toIso8601String() }}">{{ $o->created_at->format('M j, Y') }}</time>@if ($o->due_at && $o->status->isRunning()) · due <time data-dt="date" datetime="{{ $o->due_at->toIso8601String() }}">{{ $o->due_at->format('M j, Y') }}</time>@endif</small>
                </span>
                @if ($o->unread_count)<span class="dot-unread" title="Unread messages">{{ $o->unread_count }}</span>@endif
                @if ($needs)<span class="chip chip--soon">{{ $needs }}</span>@endif
                <span class="strong">{{ money($o->total_cents, $o->currency) }}</span>
                <x-portal.badge :status="$o->status" />
            </a>
        @empty
            <div class="empty"><x-icon name="receipt" /><b>No orders yet</b><span>When we send you an invoice, it will appear here.</span></div>
        @endforelse
    </section>
@endsection
