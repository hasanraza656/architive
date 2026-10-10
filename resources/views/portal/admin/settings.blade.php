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
        <form class="pcard" method="post" action="{{ route('admin.settings.profile') }}" enctype="multipart/form-data" data-loading novalidate>
            @csrf @method('PUT')
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="user" /> Profile</h2></div>
            <div class="pcard__body pform">
                <div class="avatar-edit" data-avatar-edit>
                    <span class="avatar-edit__pic">
                        @if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="" data-avatar-img width="96" height="96">@else<span data-avatar-initials>{{ $user->initials }}</span><img src="" alt="" data-avatar-img width="96" height="96" hidden>@endif
                    </span>
                    <div class="avatar-edit__body">
                        <b>Profile photo</b>
                        <p>Shown on the blog next to the articles you publish. A square picture of your face works best.</p>
                        <div class="avatar-edit__act">
                            <label class="pbtn pbtn--ghost pbtn--sm" for="avatar"><x-icon name="upload" /> {{ $user->avatar ? 'Change photo' : 'Upload photo' }}</label>
                            <input class="visually-hidden" type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" data-avatar-input>
                            @if ($user->avatar)<label class="avatar-edit__remove"><input type="checkbox" name="remove_avatar" value="1"> Remove photo</label>@endif
                        </div>
                        @error('avatar')<p class="pfield__error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="prow">
                    <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></x-portal.field>
                    <x-portal.field name="last_name" label="Last name" optional><input class="pinput" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}"></x-portal.field>
                </div>
                <x-portal.field name="email" label="Sign-in e-mail"><input class="pinput" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required></x-portal.field>
                <x-portal.field name="job_title" label="Job title" optional hint="Shown under your name on blog articles, e.g. Founder and Architectural Engineer."><input class="pinput" id="job_title" name="job_title" maxlength="120" value="{{ old('job_title', $user->job_title) }}"></x-portal.field>
                <x-portal.field name="bio" label="Short bio" optional hint="One or two sentences for the author box under each article."><textarea class="ptextarea" id="bio" name="bio" rows="3" maxlength="600">{{ old('bio', $user->bio) }}</textarea></x-portal.field>
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

@push('scripts')
    <script>
        (function () {
            var input = document.querySelector('[data-avatar-input]'), img = document.querySelector('[data-avatar-img]'), ini = document.querySelector('[data-avatar-initials]');
            if (!input || !img) { return; }
            input.addEventListener('change', function () {
                var f = input.files && input.files[0];
                if (!f) { return; }
                img.src = URL.createObjectURL(f); img.hidden = false; if (ini) { ini.hidden = true; }
            });
        })();
    </script>
@endpush
