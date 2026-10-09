@extends('emails.portal.layout')
@section('title', 'Revision requested')
@section('preheader', $order->customer->name . ' asked for changes on ' . $order->number)
@section('kicker', 'Revision requested')
@section('heading', $order->customer->name . ' asked for changes')
@section('content')
    <p style="margin:0 0 12px;">On order <strong>{{ $order->number }}</strong> ({{ $order->title }}):</p>
    <div style="margin:0 0 14px;padding:14px 16px;background:#fafaf7;border:1px solid #e6e6e0;border-radius:10px;white-space:pre-line;font-size:14px;">{{ $reason }}</div>
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">The order is back to <strong>in progress</strong>.</p>
@endsection
@section('button', 'Open order')
@section('button_url', $order->adminUrl() . '#chat')
