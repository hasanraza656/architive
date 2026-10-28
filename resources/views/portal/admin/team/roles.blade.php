@extends('portal.layouts.app')
@section('title', 'Roles and permissions')
@section('heading', 'Team')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-team.css') }}">
@endpush

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Roles and permissions</h1>
            <p class="phead__sub">A role is a named set of permissions. Give a role to a team member and they can do exactly that, nothing more.</p>
        </div>
        <div class="phead__actions"><a class="pbtn pbtn--primary" href="{{ route('admin.team.roles.create') }}"><x-icon name="plus" /> New role</a></div>
    </div>

    @include('portal.admin.team._tabs', ['active' => 'roles'])

    <div class="rolegrid">
        @forelse ($roles as $r)
            @php $count = count($r->permissionList()); @endphp
            <article class="pcard rolecard" style="--rc:{{ $r->color }}">
                <div class="rolecard__bar"></div>
                <div class="rolecard__body">
                    <h2>{{ $r->name }}</h2>
                    <p class="rolecard__desc">{{ $r->description ?: 'No description yet.' }}</p>
                    <div class="rolecard__stats">
                        <span><x-icon name="users" /> {{ $r->users_count }} {{ \Illuminate\Support\Str::plural('person', $r->users_count) }}</span>
                        <span><x-icon name="shield" /> {{ $count }} {{ \Illuminate\Support\Str::plural('permission', $count) }}</span>
                    </div>
                    <div class="rolecard__mods">
                        @foreach (config('permissions.modules') as $key => $mod)
                            @php $n = collect($r->permissionList())->filter(fn ($p) => str_starts_with($p, $key . '.'))->count(); @endphp
                            @if ($n)<span title="{{ $n }} of {{ count($mod['actions']) }}">{{ $mod['label'] }}</span>@endif
                        @endforeach
                        @if (! $count)<span class="muted">Can only use chats and tasks they are added to</span>@endif
                    </div>
                </div>
                <div class="rolecard__act">
                    <a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.team.roles.edit', $r) }}"><x-icon name="edit" /> Edit</a>
                    <form method="post" action="{{ route('admin.team.roles.destroy', $r) }}" data-confirm="Delete the role “{{ $r->name }}”?">@csrf @method('DELETE')
                        <button class="pbtn pbtn--ghost pbtn--sm" type="submit" aria-label="Delete {{ $r->name }}" @disabled($r->users_count)><x-icon name="trash" /></button>
                    </form>
                </div>
            </article>
        @empty
            <div class="pcard empty"><x-icon name="shield" /><b>No roles yet</b><span>Create your first role, for example “Designer” or “Manager”.</span><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.team.roles.create') }}"><x-icon name="plus" /> New role</a></div>
        @endforelse
    </div>

    <p class="muted" style="margin-top:1.2rem;font-size:.88rem"><x-icon name="info" /> The super admin always has full access and is not affected by roles. Team members can always sign in, edit their own profile, work on tasks assigned to them and use the chats they were added to.</p>
@endsection
