@extends('emails.portal.layout')
@section('title', 'Welcome to Architive')
@section('preheader', 'Your Architive client area is ready. Sign in with your e-mail, no password needed.')
@section('kicker', 'Welcome')
@section('heading', 'Welcome' . ($customer->first_name ? ', ' . $customer->first_name : '') . ', your client area is ready')
@section('content')
    <p style="margin:0 0 14px;">We have created your Architive client area. It is where your orders, invoices, files and conversations with our team live, all in one place.</p>

    <p style="margin:0 0 8px;font-weight:bold;">What you can do there</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px;font-size:14px;">
        @foreach ([
            ['Follow every order', 'and see what stage it is at.'],
            ['Message our team', 'and share files, pictures and PDFs in one conversation.'],
            ['Pay invoices securely', 'and download the PDF receipt.'],
            ['Receive your finished work', 'and request changes if something needs adjusting.'],
        ] as [$b, $t])
        <tr><td style="padding:6px 0;width:34px;vertical-align:top;"><span style="display:inline-block;width:24px;height:24px;line-height:24px;text-align:center;border-radius:12px;background:#FFD60A;font-weight:bold;font-size:12px;">{{ $loop->iteration }}</span></td><td style="padding:6px 0;"><strong>{{ $b }}</strong> {{ $t }}</td></tr>
        @endforeach
    </table>

    <p style="margin:0 0 14px;"><strong>How to sign in:</strong> there is no password. Open the link below, enter <strong>{{ $customer->email }}</strong> and we e-mail you a one-time code.</p>
    <p style="margin:0 0 14px;font-size:14px;">Questions? E-mail <a href="mailto:{{ config('site.email') }}" style="color:#141414;font-weight:bold;">{{ config('site.email') }}</a> or message us on <a href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" style="color:#141414;font-weight:bold;">WhatsApp</a>.</p>
@endsection
@section('button', 'Open my client area')
@section('button_url', route('customer.login', ['email' => $customer->email]))
@section('footnote')
    <p style="margin:0 0 10px;">If you were not expecting this e-mail you can ignore it. Nobody can enter your client area without a code sent to this address.</p>
@endsection
