@extends('portal.layouts.app')
@php
    $isNew = ! $role->exists;
    $selected = old('permissions', $role->permissionList());
@endphp
@section('title', $isNew ? 'New role' : 'Edit role')
@section('heading', 'Team')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-team.css') }}">
@endpush

@section('content')
    <a class="crumb" href="{{ route('admin.team.roles.index') }}"><x-icon name="arrow-left" /> All roles</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $isNew ? 'New role' : $role->name }}</h1>
            <p class="phead__sub">Tick what people with this role are allowed to do.</p>
        </div>
    </div>

    <form id="roleForm" method="post" action="{{ $isNew ? route('admin.team.roles.store') : route('admin.team.roles.update', $role) }}" data-loading novalidate>
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="pcard" style="margin-bottom:1.1rem">
            <div class="pcard__body pform">
                <div class="prow">
                    <x-portal.field name="name" label="Role name"><input class="pinput" id="name" name="name" maxlength="80" value="{{ old('name', $role->name) }}" placeholder="e.g. Designer" required></x-portal.field>
                    <x-portal.field name="color" label="Colour" hint="Shown on the team list."><input class="pinput pinput--color" type="color" id="color" name="color" value="{{ old('color', $role->color ?: '#FFD60A') }}"></x-portal.field>
                </div>
                <x-portal.field name="description" label="Short description" optional><input class="pinput" id="description" name="description" maxlength="255" value="{{ old('description', $role->description) }}" placeholder="What does this role do?"></x-portal.field>
            </div>
        </div>

        <div class="permbar">
            <div>
                <b>Permissions</b>
                <span class="permbar__count"><span data-perm-count>0</span> selected</span>
            </div>
            <div class="permbar__tools">
                <span class="muted">Start from</span>
                @foreach ($presets as $slug => $p)
                    <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-preset='@json($p['permissions'])'>{{ $p['name'] }}</button>
                @endforeach
                <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-preset="[]">Nothing</button>
            </div>
        </div>

        <div class="permgrid">
            @foreach (config('permissions.modules') as $key => $mod)
                <section class="pcard permmod" data-module>
                    <header class="permmod__head">
                        <span class="permmod__ic"><x-icon :name="$mod['icon']" /></span>
                        <div><h2>{{ $mod['label'] }}</h2><p>{{ $mod['hint'] }}</p></div>
                        <label class="permswitch" title="Select all in {{ $mod['label'] }}"><input type="checkbox" data-module-all aria-label="Select all {{ $mod['label'] }} permissions"><span></span></label>
                    </header>
                    <ul class="permlist">
                        @foreach ($mod['actions'] as $action => $label)
                            @php $k = $key . '.' . $action; @endphp
                            <li><label class="bcheck"><input type="checkbox" name="permissions[]" value="{{ $k }}" data-perm @checked(in_array($k, $selected, true))> {{ $label }}</label></li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <div class="pactions" style="margin-top:1.2rem">
            <button class="pbtn pbtn--primary" type="submit">{{ $isNew ? 'Create role' : 'Save role' }}</button>
            <a class="pbtn pbtn--ghost" href="{{ route('admin.team.roles.index') }}">Cancel</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-team.js') }}"></script>
@endpush
