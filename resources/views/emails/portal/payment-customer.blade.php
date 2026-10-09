@extends('emails.portal.layout')
@section('title', 'Payment received')
@section('preheader', 'We received your payment of ' . money($order->total_cents, $order->currency) . '.')
@section('kicker', 'Payment confirmed')
@section('heading', 'Thank you, ' . $order->customer->first_name . '! Payment received')
@section('content')
    @php $dueNote = $order->due_at ? ' The delivery is due on ' . $order->due_at->format('j F Y, H:i') . ' UTC.' : ''; @endphp
    <p style="margin:0 0 12px;">We have received your payment and your order is now <strong>in progress</strong>.{{ $dueNote }}</p>
    @include('emails.portal._summary', ['order' => $order])
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">You can follow progress, chat with us and download your files from your order page. A receipt from Stripe is also on its way to your inbox.</p>
@endsection
@section('button', 'Open my order')
@section('button_url', $order->customerUrl())
