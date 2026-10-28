@extends('portal.layouts.app')
@section('title', 'Invoices')
@section('heading', 'Invoices')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Invoices</h1>
            <p class="phead__sub">Invoices sent to <b>{{ auth()->user()->email }}</b>. Pay open ones securely on Stripe and download a PDF any time.</p>
        </div>
    </div>

    @if ($error)
        <div class="pflash pflash--error" role="alert"><x-icon name="alert" /><span>{{ $error }}</span></div>
    @else
        <div class="pcard">
            @if ($rows)
                <table class="ptable ptable--cards">
                    <thead><tr><th>Invoice</th><th>Status</th><th class="num">Amount</th><th>Issued</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($rows as $r)
                        <tr>
                            <td class="cell-main"><b>{{ $r['number'] ?: 'Invoice' }}</b>@if ($r['description'])<small>{{ \Illuminate\Support\Str::limit($r['description'], 70) }}</small>@endif</td>
                            <td data-label="Status">@include('portal.stripe._status', ['status' => $r['status']])</td>
                            <td class="num strong" data-label="Amount">{{ money($r['total'], $r['currency']) }}</td>
                            <td data-label="Issued">{{ $r['created']?->format('M j, Y') ?? '-' }}</td>
                            <td data-label="Due">{{ $r['due']?->format('M j, Y') ?? '-' }}</td>
                            <td class="inv-links">
                                @if ($r['status'] === 'open' && $r['hosted_url'])<a class="pbtn pbtn--primary pbtn--sm" href="{{ $r['hosted_url'] }}" target="_blank" rel="noopener"><x-icon name="credit-card" /> Pay now</a>
                                @elseif ($r['hosted_url'])<a class="pbtn pbtn--ghost pbtn--sm" href="{{ $r['hosted_url'] }}" target="_blank" rel="noopener"><x-icon name="external" /> View</a>@endif
                                @if ($r['pdf_url'])<a class="pbtn pbtn--ghost pbtn--sm" href="{{ $r['pdf_url'] }}" rel="noopener"><x-icon name="download" /> PDF</a>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty"><x-icon name="receipt" /><b>No invoices yet</b><span>Invoices we send to {{ auth()->user()->email }} will appear here. Payments for your orders are in <a class="plink" href="{{ route('customer.dashboard') }}">My orders</a>.</span></div>
            @endif
        </div>
    @endif

    <style>
        .inv-links { white-space: nowrap; text-align: right; } .inv-links .pbtn { margin-left: .25rem; }
        .ptable td small { display: block; opacity: .65; font-size: 12px; }
    </style>
@endsection
