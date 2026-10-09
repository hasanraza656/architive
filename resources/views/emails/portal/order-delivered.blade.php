@extends('emails.portal.layout')
@section('title', 'Your delivery is ready')
@section('preheader', 'Your files for ' . $order->number . ' are ready to download.')
@section('kicker', 'Delivery')
@section('heading', 'Your work is ready, ' . $order->customer->first_name)
@section('content')
    <p style="margin:0 0 12px;">We have delivered <strong>{{ $order->title }}</strong> ({{ $order->number }}). {{ $delivery->files->count() }} {{ \Illuminate\Support\Str::plural('file', $delivery->files->count()) }} {{ $delivery->files->count() === 1 ? 'is' : 'are' }} waiting for you.</p>
    @if ($delivery->note)<div style="margin:0 0 14px;padding:14px 16px;background:#fafaf7;border:1px solid #e6e6e0;border-radius:10px;white-space:pre-line;font-size:14px;">{{ $delivery->note }}</div>@endif
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">Please review the files. You can accept the delivery, or ask for changes, from your order page.</p>
@endsection
@section('button', 'View delivery')
@section('button_url', $order->customerUrl() . '#deliveries')
