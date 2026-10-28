@extends('portal.layouts.auth')
@section('title', $invite ? 'Set your password' : 'Choose a new password')

@section('art')
    <h2>{!! $invite ? 'Welcome to <em>the team.</em>' : 'A fresh <em>start.</em>' !!}</h2>
    <p>Choose a password you do not use anywhere else. At least 8 characters with letters and numbers.</p>
@endsection

@section('content')
    <div><h1>{!! $invite ? 'Set your <em>password</em>' : 'New <em>password</em>' !!}</h1></div>
    <form class="pform" method="post" action="{{ route('admin.password.update') }}" data-loading novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-portal.field name="email" label="E-mail"><input class="pinput" type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required></x-portal.field>
        <x-portal.field name="password" label="New password" hint="At least 8 characters, with letters and numbers."><input class="pinput" type="password" id="password" name="password" autocomplete="new-password" autofocus required></x-portal.field>
        <x-portal.field name="password_confirmation" label="Repeat the password"><input class="pinput" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></x-portal.field>
        <button class="pbtn pbtn--primary pbtn--lg pbtn--block" type="submit">Save password <x-icon name="arrow-right" /></button>
    </form>
@endsection

@section('links')
    <a href="{{ route('admin.login') }}">Back to sign in →</a>
@endsection
