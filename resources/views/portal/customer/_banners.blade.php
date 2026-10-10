@php use App\Enums\OrderStatus as S; @endphp
@if ($order->status === S::Request)
    <div class="banner banner--teal" style="margin-bottom:1.1rem"><x-icon name="message" /> <span><b>We have your request.</b> We are reviewing it now and will reply right here, usually within one business day. You will get an e-mail as soon as we do. Your custom offer will appear in this conversation.</span></div>
@elseif ($order->status === S::Pending)
    <div class="paybox" style="margin-bottom:1.1rem">
        <span class="mono" style="font-size:.66rem;letter-spacing:.14em;text-transform:uppercase;color:#B5B5AD">Invoice {{ $order->number }}</span>
        <div class="paybox__amt">{{ money($order->total_cents, $order->currency) }}</div>
        <small>Your project starts the moment the payment goes through.@if ($order->due_at) The delivery countdown then begins, due <time data-dt="date" datetime="{{ $order->due_at->toIso8601String() }}">{{ $order->due_at->format('M j, Y') }}</time>.@endif</small>
        <form method="post" action="{{ route('customer.orders.pay', $order) }}" data-loading>@csrf
            <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit"><x-icon name="lock" /> Pay securely with card</button>
        </form>
        <span class="secure"><x-icon name="shield" /> Payments are processed by Stripe. We never see your card details.</span>
    </div>
@elseif ($order->status === S::Delivered)
    <div class="banner banner--violet" style="margin-bottom:1.1rem"><x-icon name="package" /> <span><b>Your work has been delivered.</b> Open the Deliveries tab to download your files, then accept the delivery or ask for changes.</span></div>
@elseif ($order->status === S::Completed)
    <div class="pflash pflash--success" style="margin-bottom:1.1rem"><x-icon name="check-circle" /><span>This order is complete. Thank you for working with us! Your files stay available under Deliveries.</span></div>
@elseif ($order->status === S::Active)
    <div class="banner banner--amber" style="margin-bottom:1.1rem;background:var(--blue-bg);color:#1E3A8A"><x-icon name="zap" /> <span>Payment received, we are working on your order. Use the conversation to share anything we should know.</span></div>
@endif
