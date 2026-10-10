@extends('portal.layouts.auth')
@section('title', 'Welcome')

@section('art')
    <h2>Your client area <em>is ready.</em></h2>
    <p>One quick step: tell us who you are, then send your first request. No password, ever.</p>
    <ul>
        <li><x-icon name="message" /> Talk to the team in a private conversation</li>
        <li><x-icon name="receipt" /> Receive a clear custom offer and pay securely</li>
        <li><x-icon name="package" /> Follow progress and download your files</li>
    </ul>
@endsection

@section('content')
    <div>
        <h1>Welcome to <em>Architive</em></h1>
    </div>
    <p>We created your account for <b>{{ $user->email }}</b>. What should we call you?</p>

    <form class="pform" method="post" action="{{ route('customer.welcome.save') }}" data-loading novalidate>
        @csrf
        <div class="prow">
            <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" autofocus required></x-portal.field>
            <x-portal.field name="last_name" label="Last name"><input class="pinput" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required></x-portal.field>
        </div>
        @include('portal.shared.phone-field', ['countries' => $countries, 'country' => null, 'phone' => null])
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Continue <x-icon name="arrow-right" /></button>
    </form>
@endsection

@section('links')
    <form method="post" action="{{ route('customer.logout') }}">@csrf<button type="submit" class="plink" style="border:0;background:none;padding:0;cursor:pointer;color:inherit">Not you? Sign out</button></form>
@endsection
