@extends('portal.layouts.app')
@section('title', 'Projects')
@section('heading', 'Projects')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-tasks.css') }}">
@endpush

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Projects</h1>
            <p class="phead__sub">Plan the work, split it into tasks and see what is due.</p>
        </div>
        @if ($canCreate)<div class="phead__actions"><button class="pbtn pbtn--primary" type="button" data-dialog-open="newProject"><x-icon name="plus" /> New project</button></div>@endif
    </div>

    <div class="toolbar">
        <div class="pills" role="navigation" aria-label="Filter by status">
            @php $open = ($counts['active'] ?? 0) + ($counts['on_hold'] ?? 0); @endphp
            <a class="pill {{ $status === 'open' ? 'is-on' : '' }}" href="{{ route('admin.projects.index', array_filter(['q' => $q, 'mine' => $mine ? 1 : null])) }}">Open <small>{{ $open }}</small></a>
            @foreach (\App\Models\Project::STATUSES as $k => $d)
                <a class="pill {{ $status === $k ? 'is-on' : '' }}" href="{{ route('admin.projects.index', array_filter(['status' => $k, 'q' => $q, 'mine' => $mine ? 1 : null])) }}">{{ $d['label'] }} <small>{{ $counts[$k] ?? 0 }}</small></a>
            @endforeach
            <a class="pill {{ $status === 'all' ? 'is-on' : '' }}" href="{{ route('admin.projects.index', array_filter(['status' => 'all', 'q' => $q, 'mine' => $mine ? 1 : null])) }}">All <small>{{ $counts->sum() }}</small></a>
        </div>
        <form method="get" action="{{ route('admin.projects.index') }}" class="tfilter" role="search">
            @if ($status !== 'open')<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="bcheck bcheck--pill"><input type="checkbox" name="mine" value="1" @checked($mine) onchange="this.form.submit()"> Only mine</label>
            <div class="search"><x-icon name="search" /><input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search projects" aria-label="Search projects"></div>
        </form>
    </div>

    @if ($projects->count())
        <div class="pgrid-cards">
            @foreach ($projects as $p)
                @php $pct = $p->progress(); @endphp
                <a class="pcard projcard" href="{{ $p->url() }}" style="--pc:{{ $p->color }}">
                    <span class="projcard__bar"></span>
                    <div class="projcard__body">
                        <div class="projcard__top">
                            <span class="pbadge pbadge--{{ $p->statusTone() }}">{{ $p->statusLabel() }}</span>
                            @if ($p->due_date)<span class="due {{ $p->isOverdue() ? 'is-late' : '' }}"><x-icon name="calendar" /> {{ $p->due_date->format('M j, Y') }}</span>@endif
                        </div>
                        <h2 class="projcard__title">{{ $p->name }}</h2>
                        <p class="projcard__desc">{{ $p->description ? \Illuminate\Support\Str::limit($p->description, 110) : 'No description yet.' }}</p>
                        <div class="projcard__prog" role="img" aria-label="{{ $pct }}% done"><span style="width:{{ $pct }}%"></span></div>
                        <div class="projcard__foot">
                            <span class="muted">{{ $p->done_tasks_count }}/{{ $p->tasks_count }} tasks done</span>
                            <span class="avs">
                                @foreach ($p->members->take(4) as $m)<span class="pavatar pavatar--xs" title="{{ $m->name }}">@if ($m->avatarUrl())<img src="{{ $m->avatarUrl() }}" alt="">@else{{ $m->initials }}@endif</span>@endforeach
                                @if ($p->members->count() > 4)<span class="pavatar pavatar--xs pavatar--soft">+{{ $p->members->count() - 4 }}</span>@endif
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        @if ($projects->hasPages())<div class="pager">{{ $projects->links() }}</div>@endif
    @else
        <div class="pcard empty"><x-icon name="layers" /><b>{{ $q || $mine || $status !== 'open' ? 'No projects match' : 'No projects yet' }}</b><span>{{ $canCreate ? 'Create a project, then add tasks and people to it.' : 'You will see projects here when you are added to one.' }}</span>
            @if ($canCreate)<button class="pbtn pbtn--primary pbtn--sm" type="button" data-dialog-open="newProject"><x-icon name="plus" /> New project</button>@endif</div>
    @endif

    @if ($canCreate)
        <dialog class="pmodal pmodal--wide" id="newProject" aria-labelledby="newProjectTitle" @if ($errors->any() && old('_form') === 'new') data-open-on-load @endif>
            <form method="post" action="{{ route('admin.projects.store') }}" data-loading novalidate>
                @csrf <input type="hidden" name="_form" value="new">
                <div class="pmodal__head"><h3 id="newProjectTitle">New project</h3><p>You can change everything later.</p></div>
                @include('portal.admin.projects._form', ['project' => new \App\Models\Project, 'form' => $form])
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit">Create project</button></div>
            </form>
        </dialog>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-tasks.js') }}"></script>
    <script>
        (function () { var d = document.querySelector('dialog[data-open-on-load]'); if (d && d.showModal) { d.showModal(); } })();
    </script>
@endpush
