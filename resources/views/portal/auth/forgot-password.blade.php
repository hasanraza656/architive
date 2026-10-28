@extends('portal.layouts.auth')
@section('title', 'Forgot password')

@section('art')
    <h2>Back in <em>a minute.</em></h2>
    <p>Enter the e-mail of your team account and we will send you a link to choose a new password.</p>
@endsection

@section('content')
    <div><h1>Forgot your <em>password?</em></h1></div>
    <p>We will e-mail you a link. It works for 7 days.</p>
    <form class="pform" method="post" action="{{ route('admin.password.email') }}" data-loading novalidate>
        @csrf
        <x-portal.field name="email" label="E-mail"><input class="pinput" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required></x-portal.field>
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Send me the link <x-icon name="arrow-right" /></button>
    </form>
@endsection

@section('links')
    <a href="{{ route('admin.login') }}">Back to sign in →</a>
@endsection
