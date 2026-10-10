@extends('emails.portal.layout')
@section('title', 'We received your request')
@section('preheader', 'Request ' . $order->number . ' received. Here is what happens next.')
@section('kicker', 'Request received')
@section('heading', 'Thanks ' . $order->customer->first_name . ', we have your request')
@section('content')
    <p style="margin:0 0 14px;">Your request <strong>{{ $order->number }}</strong>@if ($order->serviceLabel()) for <strong>{{ $order->serviceLabel() }}</strong>@endif is in. A member of our team will read it and reply, usually within one business day.</p>

    <div style="margin:0 0 16px;padding:14px 16px;background:#fafaf7;border:1px solid #e6e6e0;border-radius:10px;font-size:14px;white-space:pre-line;color:#2b2b28;">{{ \Illuminate\Support\Str::limit($order->brief, 500) }}</div>

    <p style="margin:0 0 8px;font-weight:bold;">What happens next</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px;font-size:14px;">
        <tr><td style="padding:6px 0;width:34px;vertical-align:top;"><span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#FFD60A;font-weight:bold;font-size:12px;">1</span></td><td style="padding:6px 0;"><strong>We review your brief</strong> and may ask a few questions in your portal conversation.</td></tr>
        <tr><td style="padding:6px 0;vertical-align:top;"><span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#FFD60A;font-weight:bold;font-size:12px;">2</span></td><td style="padding:6px 0;"><strong>You get a custom offer</strong> with price and timing, right inside that conversation.</td></tr>
        <tr><td style="padding:6px 0;vertical-align:top;"><span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#FFD60A;font-weight:bold;font-size:12px;">3</span></td><td style="padding:6px 0;"><strong>Pay securely and we start.</strong> No commitment until you accept the offer.</td></tr>
    </table>
@endsection
@section('button', 'Follow my request')
@section('button_url', route('customer.login', ['email' => $order->customer->email]))
@section('footnote')
    <p style="margin:0 0 10px;">Your client area is created for you: just enter {{ $order->customer->email }} and we e-mail you a one-time code. No password needed.</p>
@endsection
