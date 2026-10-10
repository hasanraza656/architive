{{-- Order summary + countdown card. Props: $order, $isAdmin --}}
@php use App\Enums\OrderStatus as S; @endphp
@if ($order->brief)
    <section class="pcard">
        <div class="pcard__head"><h2 class="pcard__title"><x-icon name="message" /> {{ $isAdmin ? 'Request details' : 'Your request' }}</h2></div>
        <div class="pcard__body reqbox">
            <dl>
                @if ($order->serviceLabel())<div><dt>Service</dt><dd>{{ $order->serviceLabel() }}</dd></div>@endif
                @if ($order->audienceLabel())<div><dt>Client type</dt><dd>{{ $order->audienceLabel() }}</dd></div>@endif
                @if ($order->company)<div><dt>Company</dt><dd>{{ $order->company }}</dd></div>@endif
                @if ($order->requested_deadline)<div><dt>Wanted by</dt><dd><time data-dt="date" datetime="{{ $order->requested_deadline->toIso8601String() }}">{{ $order->requested_deadline->format('M j, Y') }}</time></dd></div>@endif
                <div><dt>Came from</dt><dd>{{ ['website' => 'Website form', 'portal' => 'Client area'][$order->source] ?? 'Admin' }}</dd></div>
            </dl>
            <p>{{ \Illuminate\Support\Str::limit($order->brief, 600) }}</p>
        </div>
    </section>
@endif

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
            @unless ($order->status->isLead())<div><span class="k">Items</span><span class="v">{{ $order->items->count() }}</span></div>@endunless
            <div><span class="k">Created</span><span class="v"><time data-dt="date" datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('M j, Y') }}</time></span></div>
            @if ($order->paid_at)<div><span class="k">Paid</span><span class="v"><time data-dt="date" datetime="{{ $order->paid_at->toIso8601String() }}">{{ $order->paid_at->format('M j, Y') }}</time></span></div>@endif
            @if ($order->delivered_at)<div><span class="k">Delivered</span><span class="v"><time data-dt="date" datetime="{{ $order->delivered_at->toIso8601String() }}">{{ $order->delivered_at->format('M j, Y') }}</time></span></div>@endif
            @if ($order->status->isLead())
                <div class="total"><span class="k">Offer</span><span class="v" style="font:600 .95rem var(--f-body)">{{ $isAdmin ? 'Not sent yet' : 'Coming soon' }}</span></div>
            @else
                <div class="total"><span class="k">Total</span><span class="v">{{ money($order->total_cents, $order->currency) }}</span></div>
            @endif
        </div>
    </div>
</section>
