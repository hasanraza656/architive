@extends('portal.layouts.app')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Settings</h1>
            <p class="phead__sub">Your admin profile and password.</p>
        </div>
    </div>

    <div class="pgrid pgrid--2">
        <form class="pcard" method="post" action="{{ route('admin.settings.profile') }}" data-loading novalidate>
            @csrf @method('PUT')
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="user" /> Profile</h2></div>
            <div class="pcard__body pform">
                <div class="prow">
                    <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></x-portal.field>
                    <x-portal.field name="last_name" label="Last name" optional><input class="pinput" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}"></x-portal.field>
                </div>
                <x-portal.field name="email" label="Sign-in e-mail"><input class="pinput" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required></x-portal.field>
                <p class="pfield__hint">Order notifications go to <b>{{ config('site.admin_email') }}</b> (the ADMIN_EMAIL setting in the server configuration).</p>
                <div class="pactions"><button class="pbtn pbtn--primary" type="submit">Save profile</button></div>
            </div>
        </form>

        <form class="pcard" method="post" action="{{ route('admin.settings.password') }}" data-loading novalidate>
            @csrf @method('PUT')
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="lock" /> Change password</h2></div>
            <div class="pcard__body pform">
                <x-portal.field name="current_password" label="Current password" bag="password"><input class="pinput" type="password" id="current_password" name="current_password" autocomplete="current-password" required></x-portal.field>
                <x-portal.field name="password" label="New password" bag="password" hint="At least 8 characters, with letters and numbers."><input class="pinput" type="password" id="password" name="password" autocomplete="new-password" required></x-portal.field>
                <x-portal.field name="password_confirmation" label="Repeat new password" bag="password"><input class="pinput" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></x-portal.field>
                <div class="pactions"><button class="pbtn pbtn--dark" type="submit">Update password</button></div>
            </div>
        </form>
    </div>
@endsection
