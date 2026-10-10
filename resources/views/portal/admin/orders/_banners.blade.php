@php use App\Enums\OrderStatus as S; @endphp
@if ($order->status === S::Request)
    <div class="banner banner--teal" style="margin-bottom:1rem"><x-icon name="message" /> <span><b>New request from {{ $order->customer->name }}.</b> Reply in the conversation, then use <b>Create offer</b>: the customer gets the offer as a card in this chat and can pay right away.</span></div>
@elseif ($order->status === S::Draft)
    <div class="banner banner--amber" style="margin-bottom:1rem"><x-icon name="info" /> <span>This is a draft. The customer cannot see it until you send the invoice.</span></div>
@elseif ($order->status === S::Pending)
    <div class="banner banner--amber" style="margin-bottom:1rem"><x-icon name="clock" /> <span>Waiting for payment. The order starts automatically once {{ $order->customer->first_name }} pays.</span></div>
@elseif ($order->status === S::Delivered)
    <div class="banner banner--violet" style="margin-bottom:1rem"><x-icon name="package" /> <span>Delivered. Waiting for the customer to accept or request a revision. You can also mark it completed yourself.</span></div>
@endif
