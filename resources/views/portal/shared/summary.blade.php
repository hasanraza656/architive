{{-- Order summary + countdown card. Props: $order, $isAdmin --}}
@php use App\Enums\OrderStatus as S; @endphp
@if ($order->due_at && $order->status !== S::Cancelled)
    <section class="pcard">
        <div class="pcard__head"><h2 class="pcard__title"><x-icon name="clock" /> {{ $order->status === S::Active ? 'Time remaining' : 'Due date' }}</h2></div>
        <div class="pcard__body">
            @if ($order->status === S::Active)
                <div class="cd" data-countdown="{{ $order->due_at->toIso8601String() }}" role="timer" aria-label="Time remaining">
                    <div class="cd__u"><b data-u="d">0</b><small>days</small></div>
                    <div class="cd__u"><b data-u="h">00</b><small>hours</small></div>
                    <div class="cd__u"><b data-u="m">00</b><small>mins</small></div>
                    <div class="cd__u"><b data-u="s">00</b><small>secs</small></div>
                </div>
                <p class="cd__note" data-cd-note></p>
            @elseif ($order->status === S::Pending || $order->status === S::Draft)
                <p><time data-dt="datetime" datetime="{{ $order->due_at->toIso8601String() }}" class="strong">{{ $order->due_at->format('M j, Y H:i') }}</time></p>
                <p class="muted" style="margin-top:.4rem;font-size:.85rem">The countdown starts as soon as the payment is received.</p>
            @else
                @php $ref = $order->delivered_at ?? $order->completed_at; $late = $ref && $ref->gt($order->due_at); @endphp
                <p><b>Due</b> <time data-dt="datetime" datetime="{{ $order->due_at->toIso8601String() }}">{{ $order->due_at->format('M j, Y H:i') }}</time></p>
                @if ($ref)<p class="muted" style="margin-top:.3rem;font-size:.85rem">{{ $late ? 'Delivered after the due date.' : 'Delivered on time.' }}</p>@endif
            @endif
        </div>
    </section>
@endif

<section class="pcard">
    <div class="pcard__head"><h2 class="pcard__title"><x-icon name="receipt" /> Summary</h2></div>
    <div class="pcard__body">
        <div class="kv">
            @if ($isAdmin)<div><span class="k">Customer</span><span class="v"><a class="plink" href="{{ route('admin.customers.show', $order->customer) }}">{{ $order->customer->name }}</a></span></div>@endif
            <div><span class="k">Items</span><span class="v">{{ $order->items->count() }}</span></div>
            <div><span class="k">Created</span><span class="v"><time data-dt="date" datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('M j, Y') }}</time></span></div>
            @if ($order->paid_at)<div><span class="k">Paid</span><span class="v"><time data-dt="date" datetime="{{ $order->paid_at->toIso8601String() }}">{{ $order->paid_at->format('M j, Y') }}</time></span></div>@endif
            @if ($order->delivered_at)<div><span class="k">Delivered</span><span class="v"><time data-dt="date" datetime="{{ $order->delivered_at->toIso8601String() }}">{{ $order->delivered_at->format('M j, Y') }}</time></span></div>@endif
            <div class="total"><span class="k">Total</span><span class="v">{{ money($order->total_cents, $order->currency) }}</span></div>
        </div>
    </div>
</section>
