@extends('portal.layouts.app')
@section('title', 'Invoice ' . ($inv['number'] ?: ''))
@section('heading', 'Invoices')

@section('content')
    <a class="back" href="{{ route('admin.stripe.index') }}"><x-icon name="arrow-left" /> All invoices</a>

    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $inv['number'] ?: 'Draft invoice' }} @include('portal.stripe._status', ['status' => $inv['status']])</h1>
            <p class="phead__sub">{{ $inv['customer_name'] ?: $inv['customer_email'] ?: 'No customer' }} &middot; {{ money($inv['total'], $inv['currency']) }}</p>
        </div>
        <div class="phead__actions">
            @if ($inv['hosted_url'])<a class="pbtn pbtn--ghost" href="{{ $inv['hosted_url'] }}" target="_blank" rel="noopener"><x-icon name="external" /> Open invoice page</a>@endif
            @if ($inv['pdf_url'])<a class="pbtn pbtn--primary" href="{{ $inv['pdf_url'] }}" rel="noopener"><x-icon name="download" /> PDF</a>@endif
        </div>
    </div>

    <div class="pgrid pgrid--2">
        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="receipt" /> Lines</h2></div>
            <div class="pcard__body">
                <table class="ptable">
                    <thead><tr><th>Description</th><th class="num">Qty</th><th class="num">Amount</th></tr></thead>
                    <tbody>
                    @forelse ($inv['lines'] as $l)
                        <tr><td>{{ $l['description'] ?: '-' }}</td><td class="num">{{ $l['quantity'] }}</td><td class="num">{{ money($l['amount'], $inv['currency']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">No lines.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2" class="num">Subtotal</td><td class="num">{{ money($inv['subtotal'], $inv['currency']) }}</td></tr>
                        @if ($inv['tax'])<tr><td colspan="2" class="num">Tax</td><td class="num">{{ money($inv['tax'], $inv['currency']) }}</td></tr>@endif
                        <tr><td colspan="2" class="num strong">Total</td><td class="num strong">{{ money($inv['total'], $inv['currency']) }}</td></tr>
                        <tr><td colspan="2" class="num">Paid</td><td class="num">{{ money($inv['amount_paid'], $inv['currency']) }}</td></tr>
                        <tr><td colspan="2" class="num">Amount due</td><td class="num">{{ money($inv['amount_due'], $inv['currency']) }}</td></tr>
                    </tfoot>
                </table>
                @if ($inv['memo'])<p class="muted" style="margin-top:1rem">{{ $inv['memo'] }}</p>@endif
            </div>
        </section>

        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="user" /> Customer and dates</h2></div>
            <div class="pcard__body">
                <dl class="stx-dl">
                    <dt>Customer</dt><dd>{{ $inv['customer_name'] ?: '-' }}</dd>
                    <dt>E-mail</dt><dd>{{ $inv['customer_email'] ?: '-' }}</dd>
                    <dt>Portal account</dt><dd>@if ($local)<a class="plink" href="{{ route('admin.customers.show', $local) }}">{{ $local->name }}</a> (can see this invoice under “Invoices”)@else<span class="muted">No portal account with this e-mail yet</span>@endif</dd>
                    <dt>Issued</dt><dd>{{ $inv['created']?->format('M j, Y') ?? '-' }}</dd>
                    <dt>Due</dt><dd>{{ $inv['due']?->format('M j, Y') ?? '-' }}</dd>
                    <dt>Stripe id</dt><dd><code>{{ $inv['id'] }}</code></dd>
                </dl>
            </div>
        </section>
    </div>

    <style>
        .stx-dl { display: grid; grid-template-columns: max-content 1fr; gap: .55rem 1.2rem; margin: 0; }
        .stx-dl dt { font: 11px "JetBrains Mono", monospace; text-transform: uppercase; letter-spacing: .06em; opacity: .65; align-self: center; }
        .stx-dl dd { margin: 0; }
    </style>
@endsection
