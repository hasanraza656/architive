@extends('emails.portal.layout')
@section('title', 'Invoice ' . $order->number)
@section('preheader', 'Your invoice ' . $order->number . ' for ' . money($order->total_cents, $order->currency) . ' is ready.')
@section('kicker', $order->isRequestOrigin() ? 'Your custom offer' : 'New invoice')
@section('heading', 'Hi ' . $order->customer->first_name . ($order->isRequestOrigin() ? ', your custom offer is ready' : ', your invoice is ready'))
@section('content')
    @php $intro = $order->isRequestOrigin() ? 'Based on our conversation, here is the custom offer for your project.' : 'Thank you for choosing Architive. Here is the invoice for your project.'; $dueNote = $order->due_at ? ' and the delivery countdown begins (due ' . $order->due_at->format('j F Y') . ')' : ''; @endphp
    <p style="margin:0 0 12px;">{{ $intro }} Once it is paid, we start working straight away{{ $dueNote }}.</p>
    @include('emails.portal._summary', ['order' => $order])
    @if ($order->notes)<p style="margin:0 0 12px;color:#55554f;font-size:14px;white-space:pre-line;"><strong>Note:</strong> {{ $order->notes }}</p>@endif
@endsection
@section('button', $order->isRequestOrigin() ? 'View offer & pay' : 'View invoice & pay')
@section('button_url', $order->customerUrl())
@section('footnote')
    <p style="margin:0 0 10px;">For your security you will be asked to enter your e-mail address ({{ $order->customer->email }}) and a one-time code that we e-mail you. No password is needed.</p>
@endsection
