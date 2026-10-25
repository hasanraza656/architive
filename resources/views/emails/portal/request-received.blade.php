@extends('emails.portal.layout')
@section('title', 'We received your request')
@section('preheader', 'Request ' . $order->number . ' received. Here is what happens next.')
@section('kicker', 'Request received')
@section('heading', 'Thanks ' . $order->customer->first_name . ', we have your request')
@section('content')
    <p style="margin:0 0 14px;">Your request <strong>{{ $order->number }}</strong>@if ($order->serviceLabel()) for <strong>{{ $order->serviceLabel() }}</strong>@endif is in. A member of our team will read it and reply, usually within one business day.</p>

    <div style="margin:0 0 16px;padding:14px 16px;background:#fafaf7;border:1px solid #e6e6e0;border-radius:10px;font-size:14px;white-space:pre-line;color:#2b2b28;">{{ \Illuminate\Support\Str::limit($order->brief, 500) }}</div>

    @php
        // requests from an ad landing page carry the free 3-day trial offer (title set in RequestService::create)
        $steps = str_starts_with((string) $order->title, 'Free trial')
            ? [['We confirm your free trial', 'within one business day: what we will deliver and when, or what we still need.'],
               ['We work on your task free', 'for up to 3 working days, with one contact person for questions.'],
               ['You decide after seeing it.', 'If you continue, you get a written offer with price and timing first.']]
            : [['We review your brief', 'and may ask a few questions in your portal conversation.'],
               ['You get a custom offer', 'with price and timing, right inside that conversation.'],
               ['Pay securely and we start.', 'No commitment until you accept the offer.']];
    @endphp
    <p style="margin:0 0 8px;font-weight:bold;">What happens next</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px;font-size:14px;">
        @foreach ($steps as [$b, $t])
        <tr><td style="padding:6px 0;width:34px;vertical-align:top;"><span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#FFD60A;font-weight:bold;font-size:12px;">{{ $loop->iteration }}</span></td><td style="padding:6px 0;"><strong>{{ $b }}</strong> {{ $t }}</td></tr>
        @endforeach
    </table>
    <p style="margin:0 0 14px;font-size:14px;">Questions before then? E-mail <a href="mailto:{{ config('site.email') }}" style="color:#141414;font-weight:bold;">{{ config('site.email') }}</a> or message us on <a href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" style="color:#141414;font-weight:bold;">WhatsApp</a>.</p>
@endsection
@section('button', 'Follow my request')
@section('button_url', route('customer.login', ['email' => $order->customer->email]))
@section('footnote')
    <p style="margin:0 0 10px;">Your client area is created for you: just enter {{ $order->customer->email }} and we e-mail you a one-time code. No password needed.</p>
@endsection
