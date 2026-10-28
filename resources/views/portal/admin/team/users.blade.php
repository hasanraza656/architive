@extends('portal.layouts.app')
@section('title', 'Team')
@section('heading', 'Team')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-team.css') }}">
@endpush

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Team</h1>
            <p class="phead__sub">The people who work in the portal. Each one has a role that decides what they can see and do.</p>
        </div>
        @can('team.manage_users')
            <div class="phead__actions"><a class="pbtn pbtn--primary" href="{{ route('admin.team.users.create') }}"><x-icon name="plus" /> Add team member</a></div>
        @endcan
    </div>

    @include('portal.admin.team._tabs', ['active' => 'users'])

    <div class="toolbar">
        <form class="blog-filter" method="get" action="{{ route('admin.team.users.index') }}" role="search" style="display:flex;gap:.6rem;flex-wrap:wrap;flex:1">
            <select class="pselect" name="role" aria-label="Role" onchange="this.form.submit()" style="width:auto;min-width:180px">
                <option value="">All roles</option>
                @foreach ($roles as $r)<option value="{{ $r->id }}" @selected($roleId === $r->id)>{{ $r->name }}</option>@endforeach
            </select>
            <div class="search"><x-icon name="search" /><input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search name or e-mail" aria-label="Search team"></div>
        </form>
        <span class="muted">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('person', $users->total()) }}</span>
    </div>

    <div class="pcard">
        <table class="ptable ptable--cards">
            <thead><tr><th>Person</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td class="cell-main">
                        <div class="who"><span class="pavatar">@if ($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" alt="" width="38" height="38">@else{{ $u->initials }}@endif</span>
                            <div><b>{{ $u->name }}</b><small>{{ $u->job_title ?: $u->email }}@if ($u->job_title) · {{ $u->email }}@endif</small></div></div>
                    </td>
                    <td data-label="Role">
                        @if ($u->isAdmin())<span class="rolechip" style="--rc:#141414">Super admin</span>
                        @elseif ($u->teamRole)<span class="rolechip" style="--rc:{{ $u->teamRole->color }}">{{ $u->teamRole->name }}</span>
                        @else<span class="muted">No role</span>@endif
                    </td>
                    <td data-label="Status">
                        @if (! $u->is_active)<span class="pbadge pbadge--grey">Switched off</span>
                        @elseif ($u->invited_at && ! $u->last_login_at)<span class="pbadge pbadge--amber">Invited</span>
                        @else<span class="pbadge pbadge--green">Active</span>@endif
                    </td>
                    <td data-label="Last sign-in">@if ($u->last_login_at)<time data-dt="datetime" datetime="{{ $u->last_login_at->toIso8601String() }}">{{ $u->last_login_at->format('M j, Y H:i') }}</time>@else<span class="muted">Never</span>@endif</td>
                    <td class="num">
                        @can('team.manage_users')
                            <a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.team.users.edit', $u) }}"><x-icon name="edit" /> Edit</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if ($users->hasPages())<div class="pager">{{ $users->links() }}</div>@endif
    </div>
@endsection
