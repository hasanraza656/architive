@extends('portal.layouts.auth')
@section('title', 'Enter your code')

@section('art')
    <h2>Check your <em>inbox.</em></h2>
    <p>The code is valid for {{ $ttl }} minutes. If you cannot see it, look in your spam folder.</p>
@endsection

@section('content')
    <div>
        <h1>Enter your <em>code</em></h1>
    </div>
    <p>We sent a 6-digit code to <b>{{ $email }}</b>.</p>

    <form class="pform" method="post" action="{{ route('customer.login.verify.submit') }}" data-loading novalidate>
        @csrf
        <div class="pfield {{ $errors->has('code') ? 'has-error' : '' }}">
            <label for="otp1">Sign-in code</label>
            <div class="otp {{ $errors->has('code') ? 'is-error' : '' }}" data-otp>
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" @if ($i === 0) id="otp1" autocomplete="one-time-code" @endif inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="Digit {{ $i + 1 }}">
                @endfor
                <input type="hidden" name="code">
            </div>
            @error('code')<span class="pfield__error">{{ $message }}</span>@enderror
        </div>
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Sign in <x-icon name="arrow-right" /></button>
    </form>

    <form class="resend" method="post" action="{{ route('customer.login.send') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        Did not get it? <button type="submit" data-resend="{{ $resendAfter }}"><span data-resend-label>Send a new code</span></button>
    </form>
@endsection

@section('links')
    <a href="{{ route('customer.login') }}">Use a different e-mail</a>
@endsection
