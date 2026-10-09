@extends('emails.portal.layout')
@section('title', 'Payment received')
@section('preheader', $order->customer->name . ' paid ' . money($order->total_cents, $order->currency))
@section('kicker', 'Payment received')
@section('heading', money($order->total_cents, $order->currency) . ' from ' . $order->customer->name)
@section('content')
    @php $dueNote = $order->due_at ? ' and is due ' . $order->due_at->format('j F Y, H:i') . ' UTC' : ''; @endphp
    <p style="margin:0 0 12px;"><strong>{{ $order->customer->name }}</strong> ({{ $order->customer->email }}) paid invoice <strong>{{ $order->number }}</strong>. The order is now in progress{{ $dueNote }}.</p>
    @include('emails.portal._summary', ['order' => $order])
@endsection
@section('button', 'Open order')
@section('button_url', $order->adminUrl())
