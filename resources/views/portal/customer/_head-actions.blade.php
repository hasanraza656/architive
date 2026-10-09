{{-- Buttons in the order header (customer). Prop: $order --}}
@php use App\Enums\OrderStatus as S; @endphp
@if ($order->status === S::Pending)
    <form method="post" action="{{ route('customer.orders.pay', $order) }}" data-loading>@csrf
        <button class="pbtn pbtn--primary" type="submit"><x-icon name="lock" /> Pay {{ money($order->total_cents, $order->currency) }}</button>
    </form>
@endif
@if ($order->status === S::Delivered)
    <form method="post" action="{{ route('customer.orders.accept', $order) }}" data-loading data-confirm="Accept the delivery and complete this order?">@csrf
        <button class="pbtn pbtn--primary" type="submit"><x-icon name="check" /> Accept delivery</button>
    </form>
    <button class="pbtn pbtn--ghost" type="button" data-dialog-open="revisionDialog"><x-icon name="refresh" /> Request revision</button>
@endif
