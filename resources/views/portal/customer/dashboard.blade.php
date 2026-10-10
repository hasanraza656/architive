@extends('portal.layouts.app')
@section('title', 'My orders')
@section('heading', 'My orders')

@section('content')
    @php use App\Enums\OrderStatus as S; @endphp
    @php $empty = $orders->isEmpty() && $requests->isEmpty(); @endphp

    @if ($empty)
        {{-- brand-new customer: one clear next step, nothing else to get lost in --}}
        <section class="hero-cta">
            <div>
                <h2>Welcome, <em>{{ auth()->user()->first_name }}</em>. Let's start your project.</h2>
                <p>Tell us what you need in a couple of minutes. We reply with questions or a custom offer, you pay securely, and we get to work.</p>
            </div>
            <a class="pbtn pbtn--primary pbtn--lg" href="{{ route('customer.requests.create') }}"><x-icon name="plus" /> Start a new request</a>
        </section>

        <div class="how" aria-label="How it works">
            <div class="how__s"><span class="how__n">1</span><b>Tell us what you need</b><span>Pick a service, describe the project and attach any files. It takes about two minutes.</span></div>
            <div class="how__s"><span class="how__n">2</span><b>We reply with an offer</b><span>We discuss details in a private conversation and send you a clear custom offer with price and timing.</span></div>
            <div class="how__s"><span class="how__n">3</span><b>Pay and we start</b><span>Pay securely online. Follow progress, chat with the team and download your files right here.</span></div>
        </div>
    @else
        <div class="phead">
            <div>
                <h1 class="phead__title">Welcome, <em>{{ auth()->user()->first_name }}</em></h1>
                <p class="phead__sub">Your requests, invoices, projects and deliveries from Architive.</p>
            </div>
            <div class="phead__actions"><a class="pbtn pbtn--primary" href="{{ route('customer.requests.create') }}"><x-icon name="plus" /> New request</a></div>
        </div>

        <div class="stats" style="grid-template-columns:repeat(4,minmax(0,1fr))">
            <div class="stat"><span class="stat__ic"><x-icon name="message" /></span><span class="stat__num">{{ $stats['requests'] }}</span><span class="stat__label">Open requests</span></div>
            <div class="stat stat--amber"><span class="stat__ic"><x-icon name="credit-card" /></span><span class="stat__num">{{ $stats['awaiting'] }}</span><span class="stat__label">Awaiting payment</span></div>
            <div class="stat stat--blue"><span class="stat__ic"><x-icon name="zap" /></span><span class="stat__num">{{ $stats['running'] }}</span><span class="stat__label">In progress</span></div>
            <div class="stat stat--green"><span class="stat__ic"><x-icon name="check-circle" /></span><span class="stat__num">{{ $stats['completed'] }}</span><span class="stat__label">Completed</span></div>
        </div>

        @if ($requests->count())
            <section class="pcard" style="margin-bottom:1.1rem" aria-label="Your requests">
                <div class="pcard__head"><h2 class="pcard__title"><x-icon name="message" /> Your requests</h2></div>
                @foreach ($requests as $o)
                    <a class="list-row" href="{{ route('customer.orders.show', $o) }}">
                        <span class="list-row__main">
                            <b>{{ $o->serviceLabel() ?? 'Project' }} request</b>
                            <small>{{ $o->number }} · <time data-dt="date" datetime="{{ $o->created_at->toIso8601String() }}">{{ $o->created_at->format('M j, Y') }}</time> · {{ \Illuminate\Support\Str::limit($o->brief, 70) }}</small>
                        </span>
                        @if ($o->unread_count)<span class="dot-unread" title="New reply">{{ $o->unread_count }}</span>@endif
                        <span class="chip">We are reviewing</span>
                        <x-portal.badge :status="$o->status" />
                    </a>
                @endforeach
            </section>
        @endif

        @if ($orders->count())
            <section class="pcard" aria-label="Your orders">
                <div class="pcard__head"><h2 class="pcard__title"><x-icon name="receipt" /> Orders</h2></div>
                @foreach ($orders as $o)
                    @php $needs = $o->status === S::Pending ? ($o->isRequestOrigin() ? 'Review offer' : 'Pay now') : ($o->status === S::Delivered ? 'Review delivery' : null); @endphp
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
                @endforeach
            </section>
        @endif
    @endif
@endsection
