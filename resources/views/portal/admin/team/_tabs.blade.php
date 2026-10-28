{{-- Members | Roles tabs for the Team area. Prop: $active = users|roles --}}
<div class="pills" role="navigation" aria-label="Team sections" style="margin-bottom:1rem">
    @can('team.view')<a class="pill {{ $active === 'users' ? 'is-on' : '' }}" href="{{ route('admin.team.users.index') }}"><x-icon name="users" /> Members</a>@endcan
    @can('team.manage_roles')<a class="pill {{ $active === 'roles' ? 'is-on' : '' }}" href="{{ route('admin.team.roles.index') }}"><x-icon name="shield" /> Roles and permissions</a>@endcan
</div>
