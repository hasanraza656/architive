@extends('portal.layouts.app')
@section('title', 'Tasks')
@section('heading', 'Tasks')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-tasks.css') }}">
@endpush

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $scope === 'all' ? 'All tasks' : 'My tasks' }}</h1>
            <p class="phead__sub">{{ $total }} open {{ \Illuminate\Support\Str::plural('task', $total) }}{{ $scope === 'mine' ? ' assigned to you' : '' }}. Tick the circle when something is done.</p>
        </div>
        @if ($addable->count())
            <div class="phead__actions"><button class="pbtn pbtn--primary" type="button" data-dialog-open="quickTask"><x-icon name="plus" /> New task</button></div>
        @endif
    </div>

    <div class="toolbar">
        <div class="pills" role="navigation" aria-label="Which tasks">
            <a class="pill {{ $scope === 'mine' ? 'is-on' : '' }}" href="{{ route('admin.tasks.mine', array_filter(['q' => $q, 'project' => $projectId])) }}">Assigned to me</a>
            @if ($canAll)<a class="pill {{ $scope === 'all' ? 'is-on' : '' }}" href="{{ route('admin.tasks.mine', array_filter(['scope' => 'all', 'q' => $q, 'project' => $projectId])) }}">Everyone's tasks</a>@endif
        </div>
        <form method="get" action="{{ route('admin.tasks.mine') }}" class="tfilter" role="search">
            @if ($scope === 'all')<input type="hidden" name="scope" value="all">@endif
            <select class="pselect" name="project" aria-label="Project" onchange="this.form.submit()">
                <option value="">All projects</option>
                @foreach ($projects as $p)<option value="{{ $p->id }}" @selected($projectId === $p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <div class="search"><x-icon name="search" /><input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search tasks" aria-label="Search tasks"></div>
        </form>
    </div>

    <div data-task-list>
        @php $any = false; @endphp
        @foreach ($groups as $key => $g)
            @continue($g['tasks']->isEmpty())
            @php $any = true; @endphp
            <section class="tgroup tgroup--{{ $g['tone'] }}" data-group="{{ $key }}">
                <h2 class="tgroup__head"><span>{{ $g['label'] }}</span><small>{{ $g['tasks']->count() }}</small></h2>
                <div class="pcard tlist">
                    @foreach ($g['tasks'] as $t)@include('portal.tasks._row', ['task' => $t])@endforeach
                </div>
            </section>
        @endforeach

        @unless ($any)
            <div class="pcard empty"><x-icon name="check-circle" /><b>{{ $q || $projectId ? 'No tasks match' : 'You are all caught up' }}</b><span>{{ $q || $projectId ? 'Try a different search.' : 'Nothing is waiting for you right now.' }}</span></div>
        @endunless

        @if ($done->count())
            <details class="tdone">
                <summary>Recently completed <small>{{ $done->count() }}</small></summary>
                <div class="pcard tlist">@foreach ($done as $t)@include('portal.tasks._row', ['task' => $t])@endforeach</div>
            </details>
        @endif
    </div>

    @if ($addable->count())
        <dialog class="pmodal" id="quickTask" aria-labelledby="quickTaskTitle">
            <form data-quick-task data-url-template="{{ route('admin.projects.tasks.store', ['project' => '__ID__']) }}" novalidate>
                <div class="pmodal__head"><h3 id="quickTaskTitle">New task</h3><p>Pick the project it belongs to.</p></div>
                <div class="pmodal__body pform">
                    <div class="pfield"><label for="qtProject">Project</label>
                        <select class="pselect" id="qtProject" name="project" required>@foreach ($addable as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                    <div class="pfield"><label for="qtTitle">Task</label><input class="pinput" id="qtTitle" name="title" maxlength="200" placeholder="What needs to be done?" required></div>
                    <div class="prow">
                        <div class="pfield"><label for="qtDue">Due date <span class="opt">(optional)</span></label><input class="pinput" type="date" id="qtDue" name="due_date"></div>
                        <div class="pfield"><label for="qtPrio">Priority</label>
                            <select class="pselect" id="qtPrio" name="priority">@foreach (\App\Models\Task::PRIORITIES as $k => $d)<option value="{{ $k }}" @selected($k === 'normal')>{{ $d['label'] }}</option>@endforeach</select></div>
                    </div>
                    <label class="bcheck"><input type="checkbox" name="mine" value="1" checked> Assign it to me</label>
                    <p class="pfield__error" data-quick-error hidden></p>
                </div>
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit"><x-icon name="plus" /> Add task</button></div>
            </form>
        </dialog>
    @endif
@endsection

@push('scripts')
    <script>window.PortalMe = {{ auth()->id() }};</script>
    <script src="{{ asset_v('assets/js/portal-tasks.js') }}"></script>
@endpush
