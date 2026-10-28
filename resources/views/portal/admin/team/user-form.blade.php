@extends('portal.layouts.app')
@php $isNew = ! $member->exists; @endphp
@section('title', $isNew ? 'Add team member' : 'Edit team member')
@section('heading', 'Team')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-team.css') }}">
@endpush

@section('content')
    <a class="crumb" href="{{ route('admin.team.users.index') }}"><x-icon name="arrow-left" /> All team members</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $isNew ? 'Add team member' : $member->name }}</h1>
            <p class="phead__sub">{{ $isNew ? 'They get an e-mail to choose their own password.' : $member->email }}</p>
        </div>
        @unless ($isNew)
            @if ($member->isStaff())
                <div class="phead__actions">
                    <form method="post" action="{{ route('admin.team.users.invite', $member) }}" data-loading>@csrf
                        <button class="pbtn pbtn--ghost" type="submit"><x-icon name="mail" /> Send invitation again</button>
                    </form>
                </div>
            @endif
        @endunless
    </div>

    <form class="pcard" method="post" action="{{ $isNew ? route('admin.team.users.store') : route('admin.team.users.update', $member) }}" data-loading novalidate>
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <div class="pcard__body pform">
            <div class="prow">
                <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" maxlength="80" value="{{ old('first_name', $member->first_name) }}" required></x-portal.field>
                <x-portal.field name="last_name" label="Last name" optional><input class="pinput" id="last_name" name="last_name" maxlength="80" value="{{ old('last_name', $member->last_name) }}"></x-portal.field>
            </div>
            <div class="prow">
                <x-portal.field name="email" label="Sign-in e-mail"><input class="pinput" type="email" id="email" name="email" maxlength="190" value="{{ old('email', $member->email) }}" required></x-portal.field>
                <x-portal.field name="phone" label="Phone" optional><input class="pinput" id="phone" name="phone" maxlength="40" value="{{ old('phone', $member->phone) }}"></x-portal.field>
            </div>
            <x-portal.field name="job_title" label="Job title" optional hint="Shown next to their name, for example “3D artist”."><input class="pinput" id="job_title" name="job_title" maxlength="120" value="{{ old('job_title', $member->job_title) }}"></x-portal.field>

            @if ($member->isAdmin())
                <p class="pfield__hint"><b>Super admin.</b> This account can do everything and cannot be restricted.</p>
            @else
                <x-portal.field name="role_id" label="Role" hint="The role decides what this person can see and do. You can change it any time.">
                    <select class="pselect" id="role_id" name="role_id" required>
                        <option value="">Choose a role…</option>
                        @foreach ($roles as $r)<option value="{{ $r->id }}" @selected((int) old('role_id', $member->role_id) === $r->id)>{{ $r->name }}</option>@endforeach
                    </select>
                </x-portal.field>
                @can('team.manage_roles')<p class="pfield__hint" style="margin-top:-.4rem">Need a different set of permissions? <a class="plink" href="{{ route('admin.team.roles.create') }}">Create a new role</a>.</p>@endcan
            @endif

            @if ($isNew)
                <label class="bcheck"><input type="checkbox" name="send_invite" value="1" data-invite @checked(old('send_invite', true))> E-mail an invitation so they can choose their own password <small>(recommended)</small></label>
                <div data-password-box hidden>
                    <x-portal.field name="password" label="Password" hint="At least 8 characters, with letters and numbers."><input class="pinput" type="password" id="password" name="password" autocomplete="new-password"></x-portal.field>
                </div>
            @else
                <x-portal.field name="password" label="New password" optional hint="Leave empty to keep the current password."><input class="pinput" type="password" id="password" name="password" autocomplete="new-password"></x-portal.field>
                @if ($member->isStaff())
                    <label class="bcheck"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->is_active))> Account is active <small>(switch off to stop them signing in, nothing is deleted)</small></label>
                @endif
            @endif

            <div class="pactions">
                <button class="pbtn pbtn--primary" type="submit">{{ $isNew ? 'Add team member' : 'Save changes' }}</button>
                <a class="pbtn pbtn--ghost" href="{{ route('admin.team.users.index') }}">Cancel</a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var box = document.querySelector('[data-invite]'), pw = document.querySelector('[data-password-box]');
            if (!box || !pw) { return; }
            function sync() { pw.hidden = box.checked; }
            box.addEventListener('change', sync); sync();
        })();
    </script>
@endpush
