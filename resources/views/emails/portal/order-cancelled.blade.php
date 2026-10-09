@extends('emails.portal.layout')
@section('title', 'Order cancelled')
@section('preheader', 'Order ' . $order->number . ' was cancelled.')
@section('kicker', 'Order update')
@section('heading', 'Order ' . $order->number . ' was cancelled')
@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->customer->first_name }}, your order <strong>{{ $order->title }}</strong> has been cancelled{!! $order->cancel_reason ? ': <em>' . e($order->cancel_reason) . '</em>' : '.' !!}</p>
    @if ($order->paid_at)<p style="margin:0 0 12px;color:#55554f;font-size:14px;">If you already paid, we will arrange your refund. It usually appears on your statement within 5 to 10 business days.</p>@endif
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">Questions? Just reply to this e-mail.</p>
@endsection
