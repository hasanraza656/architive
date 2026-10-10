@extends('portal.layouts.auth')
@section('title', 'Client sign in')

@section('art')
    <h2>Your projects, <em>always at hand.</em></h2>
    <p>View invoices, pay securely, follow progress and talk to the studio in one place.</p>
    <ul>
        <li><x-icon name="lock" /> No password needed: we e-mail you a one-time code</li>
        <li><x-icon name="package" /> Download your deliveries any time</li>
        <li><x-icon name="message" /> Message the team about any order</li>
    </ul>
@endsection

@section('content')
    <div>
        <h1>Client <em>area</em></h1>
    </div>
    <p>Enter your e-mail address and we will send you a 6-digit code. <b>New here?</b> No problem: we create your account automatically, no password needed.</p>

    <form class="pform" method="post" action="{{ route('customer.login.send') }}" data-loading novalidate>
        @csrf
        <x-portal.field name="email" label="E-mail address">
            <input class="pinput" type="email" id="email" name="email" value="{{ $email }}" autocomplete="email" placeholder="you@company.com" autofocus required>
        </x-portal.field>
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Continue with e-mail <x-icon name="arrow-right" /></button>
    </form>
@endsection

@section('links')
    <span>Need help? <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></span>
@endsection
