<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        @page { margin: 34px 38px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #141414; line-height: 1.5; }
        .bar { height: 6px; background: #FFD60A; margin-bottom: 22px; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #8a8a83; }
        .small { font-size: 9.5px; }
        .r { text-align: right; }
        h1 { font-size: 26px; margin: 0; font-weight: normal; letter-spacing: -.5px; }
        .ref { font-size: 12px; font-weight: bold; color: #55554f; letter-spacing: 1px; }
        .status { display: inline-block; padding: 4px 12px; border: 2px solid #15803d; color: #15803d; font-size: 12px; font-weight: bold; letter-spacing: 3px; }
        .status.void { border-color: #b91c1c; color: #b91c1c; }
        .status.due { border-color: #b45309; color: #b45309; }
        .meta { margin: 22px 0 18px; border-top: 1px solid #e6e6e0; border-bottom: 1px solid #e6e6e0; }
        .meta td { padding: 12px 8px 12px 0; vertical-align: top; width: 33%; }
        .lab { font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase; color: #8a8a83; font-weight: bold; margin-bottom: 4px; }
        .title { font-size: 15px; margin: 0 0 10px; }
        .items th { padding: 8px 6px; text-align: left; font-size: 8px; letter-spacing: 1.4px; text-transform: uppercase; color: #8a8a83; border-bottom: 2px solid #141414; }
        .items td { padding: 10px 6px; border-bottom: 1px solid #ececE6; vertical-align: top; }
        .totals { width: 46%; margin-left: 54%; margin-top: 14px; }
        .totals td { padding: 4px 0; }
        .totals .grand td { border-top: 2px solid #141414; padding-top: 9px; font-size: 16px; font-weight: bold; }
        .notes { margin-top: 24px; padding: 12px 14px; background: #f6f6f2; color: #55554f; }
        .foot { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e6e6e0; font-size: 9.5px; color: #8a8a83; }
    </style>
</head>
<body>
@php
    $paid = $order->paid_at && $order->status !== \App\Enums\OrderStatus::Cancelled;
    $void = $order->status === \App\Enums\OrderStatus::Cancelled;
    $issued = $order->sent_at ?? $order->created_at;
@endphp
<div class="bar"></div>
<table>
    <tr>
        <td style="width:58%;vertical-align:top;">
            @if ($logo)<img src="{{ $logo }}" style="height:34px;" alt="Architive">@else<strong style="font-size:18px;">ARCHITIVE</strong>@endif
            <div class="small muted" style="margin-top:8px;">
                {{ config('site.address.corporate.value') }}<br>
                {{ config('site.email') }}<br>
                WhatsApp / phone: {{ config('site.phone_display') }}
            </div>
        </td>
        <td class="r" style="vertical-align:top;">
            <h1>Invoice</h1>
            <div class="ref">{{ $order->number }}</div>
            <div style="margin-top:10px;"><span class="status {{ $void ? 'void' : ($paid ? '' : 'due') }}">{{ $void ? 'VOID' : ($paid ? 'PAID' : 'UNPAID') }}</span></div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><div class="lab">Billed to</div>{{ $order->customer->name }}<br><span class="muted">{{ $order->customer->email }}</span>@if ($order->customer->phone)<br><span class="muted">{{ $order->customer->phone }}</span>@endif</td>
        <td><div class="lab">Issued</div>{{ $issued->format('j F Y') }}@if ($paid)<br><span class="muted">Paid {{ $order->paid_at->format('j F Y') }}</span>@endif</td>
        <td><div class="lab">Due date</div>{{ $order->due_at ? $order->due_at->format('j F Y, H:i') . ' UTC' : 'Not set' }}</td>
    </tr>
</table>

<p class="title">{{ $order->title }}</p>
<table class="items">
    <thead><tr><th>Description</th><th class="r" style="width:50px;">Qty</th><th class="r" style="width:80px;">Price</th><th class="r" style="width:90px;">Amount</th></tr></thead>
    <tbody>
    @foreach ($order->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="r">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
            <td class="r">{{ money($item->unit_price_cents, $order->currency) }}</td>
            <td class="r">{{ money($item->lineCents(), $order->currency) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="r">{{ money($order->subtotal_cents, $order->currency) }}</td></tr>
    @if ($order->discount_cents > 0)<tr><td>Discount</td><td class="r">- {{ money($order->discount_cents, $order->currency) }}</td></tr>@endif
    @if ($order->tax_cents > 0)<tr><td>Tax ({{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%)</td><td class="r">{{ money($order->tax_cents, $order->currency) }}</td></tr>@endif
    <tr class="grand"><td>Total</td><td class="r">{{ money($order->total_cents, $order->currency) }}</td></tr>
    @if ($paid)<tr><td class="muted small">Amount paid</td><td class="r muted small">{{ money($order->total_cents, $order->currency) }}</td></tr>@endif
</table>

@if ($order->notes)<div class="notes"><div class="lab">Notes</div>{{ $order->notes }}</div>@endif

<div class="foot">
    Thank you for your business. Questions about this invoice? WhatsApp or call {{ config('site.phone_display') }}, or e-mail {{ config('site.email') }}.
</div>
</body>
</html>
