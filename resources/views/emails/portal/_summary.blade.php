{{-- Order summary table used in several e-mails. Prop: $order (items loaded) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border:1px solid #e6e6e0;border-radius:10px;font-size:14px;">
    <tr><td style="padding:12px 16px;background:#fafaf7;border-bottom:1px solid #e6e6e0;"><strong>{{ $order->title }}</strong><br><span style="color:#8a8a83;font-size:12px;">{{ $order->number }}</span></td></tr>
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 16px;border-bottom:1px solid #f0f0ea;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td style="font-size:14px;">{{ $item->description }}<span style="color:#8a8a83;"> × {{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</span></td>
                    <td align="right" style="font-size:14px;white-space:nowrap;">{{ money($item->lineCents(), $order->currency) }}</td>
                </tr></table>
            </td>
        </tr>
    @endforeach
    @if ($order->discount_cents > 0)<tr><td style="padding:8px 16px;color:#55554f;"><table role="presentation" width="100%"><tr><td>Discount</td><td align="right">− {{ money($order->discount_cents, $order->currency) }}</td></tr></table></td></tr>@endif
    @if ($order->tax_cents > 0)<tr><td style="padding:8px 16px;color:#55554f;"><table role="presentation" width="100%"><tr><td>Tax ({{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%)</td><td align="right">{{ money($order->tax_cents, $order->currency) }}</td></tr></table></td></tr>@endif
    <tr><td style="padding:14px 16px;background:#141414;color:#ffffff;border-radius:0 0 9px 9px;"><table role="presentation" width="100%"><tr><td style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#FFD60A;">Total</td><td align="right" style="font-size:20px;font-weight:bold;">{{ money($order->total_cents, $order->currency) }}</td></tr></table></td></tr>
</table>
