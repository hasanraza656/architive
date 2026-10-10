{{-- The order page shared by admin and customer: header, progress, conversation / deliveries / invoice tabs, side cards.
     Props: $order, $feed, $isAdmin. Role-specific buttons live in admin/orders/_actions and customer/_actions. --}}
@php
    use App\Enums\OrderStatus as S;
    $defaultTab = match (true) {
        $order->status === S::Pending || $order->status === S::Draft => 'invoice',
        $order->status === S::Request => 'chat',
        ! $isAdmin && $order->status === S::Delivered => 'deliveries',
        default => 'chat',
    };
@endphp

<section class="pcard ohead">
    <div class="ohead__top">
        <div>
            <div class="ohead__num"><span>{{ $order->number }}</span><x-portal.badge :status="$order->status" /></div>
            <h1 class="ohead__title">{{ $order->title }}</h1>
            <div class="ohead__meta">
                @if ($isAdmin)<span><x-icon name="user" /> <a class="plink" href="{{ route('admin.customers.show', $order->customer) }}">{{ $order->customer->name }}</a> · {{ $order->customer->email }}</span>@endif
                <span><x-icon name="dollar" /> {{ money($order->total_cents, $order->currency) }}</span>
                @if ($order->due_at)<span><x-icon name="calendar" /> Due <time data-dt="date" datetime="{{ $order->due_at->toIso8601String() }}">{{ $order->due_at->format('M j, Y') }}</time></span>@endif
            </div>
        </div>
        <div class="ohead__actions no-print">
            @include($isAdmin ? 'portal.admin.orders._head-actions' : 'portal.customer._head-actions')
        </div>
    </div>

    @if ($order->status === S::Cancelled)
        <div class="banner banner--red"><x-icon name="x" /> <span>This order was cancelled{{ $order->cancel_reason ? ': ' . $order->cancel_reason : '.' }}</span></div>
    @endif
    @include('portal.shared.stepper')
</section>

<div class="pgrid pgrid--main" style="margin-top:1.1rem">
    <div style="min-width:0">
        @include($isAdmin ? 'portal.admin.orders._banners' : 'portal.customer._banners')

        <div class="tabs no-print" data-tabs data-default="{{ $defaultTab }}" role="tablist" aria-label="Order sections">
            <button class="tab" type="button" role="tab" data-tab="chat"><x-icon name="message" /> Conversation</button>
            @unless ($order->status->isLead())
                <button class="tab" type="button" role="tab" data-tab="deliveries"><x-icon name="package" /> Deliveries @if ($order->deliveries->count())<small>{{ $order->deliveries->count() }}</small>@endif</button>
                <button class="tab" type="button" role="tab" data-tab="invoice"><x-icon name="receipt" /> {{ $order->isRequestOrigin() && $order->status === S::Pending ? 'Offer' : 'Invoice' }}</button>
            @endunless
        </div>

        <div class="tabpane" data-pane="chat">@include('portal.shared.chat')</div>
        @unless ($order->status->isLead())
            <div class="tabpane" data-pane="deliveries">@include('portal.shared.deliveries')</div>
            <div class="tabpane" data-pane="invoice">
                @include('portal.shared.invoice')
                <p class="no-print" style="margin-top:.9rem;display:flex;justify-content:flex-end;gap:.5rem;flex-wrap:wrap">
                    <a class="pbtn pbtn--primary pbtn--sm" href="{{ route('portal.orders.invoice', $order) }}"><x-icon name="download" /> Download PDF</a>
                    <button class="pbtn pbtn--ghost pbtn--sm" type="button" onclick="window.print()"><x-icon name="receipt" /> Print</button>
                </p>
            </div>
        @endunless
    </div>

    <aside style="display:grid;gap:1.1rem;min-width:0;align-content:start" class="no-print">
        @include('portal.shared.summary')
        @include('portal.shared.timeline')
    </aside>
</div>

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-chat.js') }}"></script>
@endpush
