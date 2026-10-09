@extends('portal.layouts.auth')
@section('title', 'Admin sign in')

@section('art')
    <h2>Run every order <em>from one place.</em></h2>
    <p>Invoices, payments, conversations and deliveries for every client, in a single calm workspace.</p>
    <ul>
        <li><x-icon name="receipt" /> Build and send invoices in minutes</li>
        <li><x-icon name="credit-card" /> Get paid securely with Stripe</li>
        <li><x-icon name="message" /> Chat and deliver work inside each order</li>
    </ul>
@endsection

@section('content')
    <div>
        <h1>Admin <em>sign in</em></h1>
    </div>
    <p>Use your administrator account to continue.</p>

    <form class="pform" method="post" action="{{ route('admin.login.submit') }}" data-loading novalidate>
        @csrf
        <x-portal.field name="email" label="E-mail">
            <input class="pinput" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
        </x-portal.field>
        <x-portal.field name="password" label="Password">
            <input class="pinput" type="password" id="password" name="password" autocomplete="current-password" required>
        </x-portal.field>
        <label class="pcheck"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Sign in <x-icon name="arrow-right" /></button>
    </form>
@endsection

@section('links')
    <a href="{{ route('customer.login') }}">I am a client →</a>
@endsection
