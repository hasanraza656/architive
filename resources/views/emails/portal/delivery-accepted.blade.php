@extends('emails.portal.layout')
@section('title', 'Delivery accepted')
@section('preheader', $order->customer->name . ' accepted the delivery of ' . $order->number)
@section('kicker', 'Order completed')
@section('heading', $order->customer->name . ' accepted the delivery')
@section('content')
    <p style="margin:0 0 12px;">Order <strong>{{ $order->number }}</strong> ({{ $order->title }}) is now <strong>completed</strong>.</p>
@endsection
@section('button', 'Open order')
@section('button_url', $order->adminUrl())
