@extends('portal.layouts.app')
@section('title', 'Invoices')
@section('heading', 'Invoices')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Invoices</h1>
            <p class="phead__sub">Invoices from your Stripe account, newest first. Customers whose account e-mail matches the invoice e-mail also see theirs under “Invoices”.</p>
        </div>
        <div class="phead__actions"><a class="pbtn pbtn--ghost" href="{{ route('admin.stripe.index', array_filter($f, fn ($v, $k) => $k !== 'after' && $v, ARRAY_FILTER_USE_BOTH) + ['fresh' => 1]) }}"><x-icon name="refresh" /> Refresh</a></div>
    </div>

    <form class="pcard stx-filters" method="get" action="{{ route('admin.stripe.index') }}" role="search" aria-label="Filter invoices">
        <div class="pfield">
            <label for="stxStatus">Status</label>
            <select class="pselect" id="stxStatus" name="status" data-autosubmit>
                <option value="">All statuses</option>
                @foreach (\App\Services\Payments\StripeInvoices::STATUSES as $k => $label)
                    <option value="{{ $k }}" @selected($f['status'] === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="pfield stx-wide">
            <label for="stxCustomer">Customer</label>
            <div class="search" style="max-width:none"><x-icon name="search" /><input class="pinput" id="stxCustomer" type="search" name="customer" value="{{ $f['customer'] }}" placeholder="Name or e-mail (part is enough)"></div>
        </div>
        <div class="pfield">
            <label for="stxNumber">Invoice number</label>
            <input class="pinput" id="stxNumber" type="text" name="number" value="{{ $f['number'] }}" placeholder="e.g. 4F2A1B-0001">
        </div>
        <div class="pfield">
            <label for="stxFrom">From</label>
            <input class="pinput" id="stxFrom" type="date" name="from" value="{{ $f['from'] }}">
        </div>
        <div class="pfield">
            <label for="stxTo">To</label>
            <input class="pinput" id="stxTo" type="date" name="to" value="{{ $f['to'] }}">
        </div>
        <div class="stx-actions">
            <button class="pbtn pbtn--primary" type="submit"><x-icon name="search" /> Filter</button>
            @if ($filtered)<a class="pbtn pbtn--ghost" href="{{ route('admin.stripe.index') }}">Clear</a>@endif
        </div>
    </form>

    @if ($error)
        <div class="pflash pflash--error" role="alert"><x-icon name="alert" /><span>{{ $error }}</span></div>
    @else
        @if ($rows)
            <div class="stx-sums">
                <span><b>{{ count($rows) }}</b> {{ \Illuminate\Support\Str::plural('invoice', count($rows)) }} shown{{ $has_more ? ' (more on the next page)' : '' }}</span>
                @foreach ($sums as $cur => $s)
                    <span>Paid <b>{{ money($s['paid'], $cur) }}</b></span>
                    @if ($s['open'])<span>Waiting for payment <b>{{ money($s['open'], $cur) }}</b></span>@endif
                @endforeach
            </div>
        @endif

        <div class="pcard">
            @if ($rows)
                <table class="ptable ptable--cards">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><th class="num">Amount</th><th>Issued</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($rows as $r)
                        @php $mine = $r['customer_email'] ? ($local[mb_strtolower($r['customer_email'])] ?? null) : null; @endphp
                        <tr>
                            <td class="cell-main"><a class="plink" href="{{ route('admin.stripe.show', $r['id']) }}"><b>{{ $r['number'] ?: 'Draft invoice' }}</b></a>@if ($r['description'])<small>{{ \Illuminate\Support\Str::limit($r['description'], 60) }}</small>@endif</td>
                            <td data-label="Customer">
                                <b>{{ $r['customer_name'] ?: '-' }}</b>
                                <small>{{ $r['customer_email'] }}@if ($mine) &middot; <a class="plink" href="{{ route('admin.customers.show', $mine) }}">portal account</a>@endif</small>
                            </td>
                            <td data-label="Status">@include('portal.stripe._status', ['status' => $r['status']])</td>
                            <td class="num strong" data-label="Amount">{{ money($r['total'], $r['currency']) }}@if ($r['status'] === 'open' && $r['amount_paid'])<small>{{ money($r['amount_due'], $r['currency']) }} due</small>@endif</td>
                            <td data-label="Issued">{{ $r['created']?->format('M j, Y') ?? '-' }}</td>
                            <td data-label="Due">{{ $r['due']?->format('M j, Y') ?? '-' }}</td>
                            <td class="stx-links">
                                @if ($r['hosted_url'])<a class="pbtn pbtn--ghost pbtn--sm" href="{{ $r['hosted_url'] }}" target="_blank" rel="noopener" title="Open the invoice page"><x-icon name="external" /></a>@endif
                                @if ($r['pdf_url'])<a class="pbtn pbtn--ghost pbtn--sm" href="{{ $r['pdf_url'] }}" rel="noopener" title="Download PDF"><x-icon name="download" /></a>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if ($mode === 'all' && ($has_more || $f['after']))
                    <div class="pager stx-pager">
                        @if ($f['after'])<a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.stripe.index', array_filter($f, fn ($v, $k) => $k !== 'after' && $v, ARRAY_FILTER_USE_BOTH)) }}"><x-icon name="arrow-left" /> First page</a>@endif
                        @if ($has_more)<a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.stripe.index', array_filter($f, fn ($v, $k) => $k !== 'after' && $v, ARRAY_FILTER_USE_BOTH) + ['after' => $next]) }}">Next page <x-icon name="arrow-right" /></a>@endif
                    </div>
                @endif
            @else
                <div class="empty"><x-icon name="credit-card" /><b>No invoices found</b><span>{{ $note ?: ($filtered ? 'Try different filters.' : 'Invoices you create in Stripe appear here.') }}</span></div>
            @endif
        </div>
        <p class="muted stx-foot">Read-only view. Create and edit invoices in the Stripe dashboard. Results are refreshed every minute.</p>
    @endif

    <style>
        .stx-filters { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .9rem; padding: 1.1rem 1.2rem; margin-bottom: 1rem; align-items: end; }
        .stx-filters > .pfield { grid-column: span 2; } .stx-filters > .stx-wide { grid-column: span 4; }
        .stx-actions { grid-column: span 2; display: flex; gap: .5rem; }
        .stx-sums { display: flex; flex-wrap: wrap; gap: .4rem 1.4rem; margin: 0 0 .8rem; font-size: 13px; }
        .stx-links { white-space: nowrap; text-align: right; } .stx-links .pbtn { margin-left: .25rem; }
        .stx-pager { display: flex; justify-content: space-between; padding: .8rem 1rem; }
        .stx-foot { margin-top: .8rem; font-size: 12px; }
        .ptable td small { display: block; opacity: .65; font-size: 12px; }
        @media (max-width: 900px) { .stx-filters > .pfield, .stx-filters > .stx-wide, .stx-actions { grid-column: span 12; } }
    </style>
@endsection
