{{-- The invoice "paper": used on the order page for both admin and customer (and printable). Prop: $order (customer + items loaded) --}}
@php
    $issued = $order->sent_at ?? $order->created_at;
    $c = $order->customer;
@endphp
<article class="paper" id="invoice">
    @if ($order->paid_at && $order->status !== \App\Enums\OrderStatus::Cancelled)
        <span class="paper__stamp" aria-label="Paid">PAID</span>
    @elseif ($order->status === \App\Enums\OrderStatus::Cancelled)
        <span class="paper__stamp paper__stamp--void" aria-label="Cancelled">VOID</span>
    @endif

    <div class="paper__top">
        <div class="paper__brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Architive" width="209" height="36">
            <small>{{ config('site.address.corporate.value') }}<br>{{ config('site.email') }}<br>WhatsApp / phone: {{ config('site.phone_display') }}</small>
        </div>
        <div class="paper__ref"><h3>Invoice</h3><span>{{ $order->number }}</span></div>
    </div>

    <div class="paper__meta">
        <div><h4>Billed to</h4><p>{{ $c->name }}<small>{{ $c->email }}</small>@if ($c->phone)<small>{{ $c->phone }}</small>@endif</p></div>
        <div><h4>Issued</h4><p><time data-dt="date" datetime="{{ $issued->toIso8601String() }}">{{ $issued->format('M j, Y') }}</time></p></div>
        <div><h4>Due date</h4><p>@if ($order->due_at)<time data-dt="datetime" datetime="{{ $order->due_at->toIso8601String() }}">{{ $order->due_at->format('M j, Y H:i') }}</time>@else<span class="muted">Not set</span>@endif</p></div>
    </div>

    <h3 class="paper__title">{{ $order->title }}</h3>
    <div class="paper__tablewrap">
        <table>
            <thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Amount</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="d">{{ $item->description }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td class="r">{{ money($item->unit_price_cents, $order->currency) }}</td>
                    <td class="r">{{ money($item->lineCents(), $order->currency) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="paper__totals">
        <div><span>Subtotal</span><span>{{ money($order->subtotal_cents, $order->currency) }}</span></div>
        @if ($order->discount_cents > 0)<div><span>Discount</span><span>− {{ money($order->discount_cents, $order->currency) }}</span></div>@endif
        @if ($order->tax_cents > 0)<div><span>Tax ({{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%)</span><span>{{ money($order->tax_cents, $order->currency) }}</span></div>@endif
        <div class="t"><span>Total</span><b>{{ money($order->total_cents, $order->currency) }}</b></div>
    </div>

    @if ($order->notes)
        <div class="paper__notes"><h4>Notes</h4>{{ $order->notes }}</div>
    @endif
</article>
