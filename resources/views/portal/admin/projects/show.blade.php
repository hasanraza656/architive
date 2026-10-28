@extends('portal.layouts.app')
@section('title', $project->name)
@section('heading', 'Projects')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-tasks.css') }}">
@endpush

@section('content')
    <a class="crumb" href="{{ route('admin.projects.index') }}"><x-icon name="arrow-left" /> All projects</a>

    <section class="pcard prohead" style="--pc:{{ $project->color }}">
        <span class="prohead__bar"></span>
        <div class="prohead__main">
            <div class="prohead__titlebar">
                <h1 class="prohead__title">{{ $project->name }}</h1>
                <span class="pbadge pbadge--{{ $project->statusTone() }}">{{ $project->statusLabel() }}</span>
            </div>
            <div class="prohead__meta">
                @if ($project->due_date)<span class="due {{ $project->isOverdue() ? 'is-late' : '' }}"><x-icon name="calendar" /> Due {{ $project->due_date->format('M j, Y') }}</span>@endif
                @if ($project->order)
                    <span><x-icon name="receipt" /> @can('view', $project->order)<a class="plink" href="{{ route('admin.orders.show', $project->order) }}">{{ $project->order->number }}</a>@else{{ $project->order->number }}@endcan
                        @if ($project->customer) · {{ $project->customer->name }}@endif
                        @if ($project->visible_to_customer) <span class="pbadge pbadge--teal">Visible to customer</span>@endif</span>
                @endif
                <span class="avs avs--lg">
                    @foreach ($project->members as $m)<span class="pavatar pavatar--sm" title="{{ $m->name }}{{ $m->pivot->role === 'lead' ? ' (lead)' : '' }}">@if ($m->avatarUrl())<img src="{{ $m->avatarUrl() }}" alt="">@else{{ $m->initials }}@endif</span>@endforeach
                </span>
            </div>
            <div class="prohead__prog">
                <div class="projcard__prog" data-prog-bar role="img" aria-label="{{ $progress }}% done"><span style="width:{{ $progress }}%"></span></div>
                <small data-prog-text>{{ $progress }}% · {{ $tasks->where('status', 'done')->count() }} of {{ $tasks->count() }} tasks done</small>
            </div>
        </div>
        <div class="prohead__act">
            @if ($canAddTask)<button class="pbtn pbtn--primary pbtn--sm" type="button" data-dialog-open="newTask"><x-icon name="plus" /> New task</button>@endif
            @if ($canEdit)<button class="pbtn pbtn--ghost pbtn--sm" type="button" data-dialog-open="editProject"><x-icon name="edit" /> Edit</button>@endif
            @if ($canDelete)<button class="pbtn pbtn--ghost pbtn--sm" type="button" data-dialog-open="deleteProject"><x-icon name="trash" /></button>@endif
        </div>
    </section>

    <div class="tabs no-print" data-tabs data-default="board" role="tablist" aria-label="Project views" style="margin:1.1rem 0 .9rem">
        <button class="tab" type="button" role="tab" data-tab="board"><x-icon name="layers" /> Board</button>
        <button class="tab" type="button" role="tab" data-tab="list"><x-icon name="file-text" /> List <small data-list-count>{{ $tasks->count() }}</small></button>
        <button class="tab" type="button" role="tab" data-tab="details"><x-icon name="info" /> Details</button>
    </div>

    {{-- BOARD --}}
    <div class="tabpane" data-pane="board">
        <div class="board" data-board data-project="{{ $project->id }}" data-live-url="{{ route('admin.projects.live', $project) }}" data-v="{{ $version }}" data-move-url-template="{{ route('admin.tasks.move', ['task' => '__ID__']) }}" data-add-url="{{ route('admin.projects.tasks.store', $project) }}" data-can-add="{{ $canAddTask ? 1 : 0 }}">
            @foreach (\App\Models\Task::STATUSES as $key => $def)
                <section class="bcol bcol--{{ $def['tone'] }}" data-col="{{ $key }}">
                    <header class="bcol__head"><h2>{{ $def['label'] }}</h2><span class="bcol__count" data-count>{{ $columns[$key]->count() }}</span></header>
                    <div class="bcol__list" data-col-list>
                        @foreach ($columns[$key] as $t)@include('portal.tasks._card', ['task' => $t])@endforeach
                    </div>
                    @if ($canAddTask)
                        <form class="bcol__add" data-quick-add novalidate>
                            <button class="bcol__addbtn" type="button" data-add-open><x-icon name="plus" /> Add a task</button>
                            <div class="bcol__addbox" hidden>
                                <textarea class="ptextarea" rows="2" maxlength="200" placeholder="Task title…" aria-label="New task title"></textarea>
                                <div><button class="pbtn pbtn--primary pbtn--sm" type="submit">Add</button><button class="pbtn pbtn--ghost pbtn--sm" type="button" data-add-cancel>Cancel</button></div>
                            </div>
                        </form>
                    @endif
                </section>
            @endforeach
        </div>
    </div>

    {{-- LIST --}}
    <div class="tabpane" data-pane="list" data-list-pane>@include('portal.admin.projects._list', ['columns' => $columns])</div>

    {{-- DETAILS --}}
    <div class="tabpane" data-pane="details">
        <div class="pgrid pgrid--main">
            <section class="pcard"><div class="pcard__head"><h2 class="pcard__title"><x-icon name="file-text" /> About this project</h2></div>
                <div class="pcard__body"><p style="margin:0;white-space:pre-line">{{ $project->description ?: 'No description yet.' }}</p></div></section>
            <aside class="pcard"><div class="pcard__head"><h2 class="pcard__title"><x-icon name="users" /> People</h2></div>
                <div class="pcard__body" style="display:grid;gap:.7rem">
                    @foreach ($project->members as $m)
                        <div class="who"><span class="pavatar pavatar--sm">@if ($m->avatarUrl())<img src="{{ $m->avatarUrl() }}" alt="">@else{{ $m->initials }}@endif</span><div><b>{{ $m->name }}</b><small>{{ $m->pivot->role === 'lead' ? 'Project lead' : ($m->job_title ?: 'Member') }}</small></div></div>
                    @endforeach
                    <dl class="kv" style="margin-top:.4rem">
                        <div><dt>Start</dt><dd>{{ $project->start_date?->format('M j, Y') ?? '-' }}</dd></div>
                        <div><dt>Due</dt><dd>{{ $project->due_date?->format('M j, Y') ?? '-' }}</dd></div>
                        <div><dt>Created by</dt><dd>{{ $project->creator?->name ?? '-' }}</dd></div>
                    </dl>
                </div></aside>
        </div>
    </div>

    {{-- dialogs --}}
    @if ($canAddTask)
        <dialog class="pmodal pmodal--wide" id="newTask" aria-labelledby="newTaskTitle">
            <form method="post" action="{{ route('admin.projects.tasks.store', $project) }}" data-loading novalidate>
                @csrf
                <div class="pmodal__head"><h3 id="newTaskTitle">New task</h3><p>in {{ $project->name }}</p></div>
                <div class="pmodal__body pform">
                    <x-portal.field name="title" label="Task"><input class="pinput" name="title" maxlength="200" placeholder="What needs to be done?" required></x-portal.field>
                    <x-portal.field name="description" label="Details" optional><textarea class="ptextarea" name="description" rows="3" maxlength="6000" data-autosize></textarea></x-portal.field>
                    <div class="prow">
                        <x-portal.field name="status" label="Status"><select class="pselect" name="status">@foreach (\App\Models\Task::STATUSES as $k => $d)<option value="{{ $k }}" @selected($k === 'todo')>{{ $d['label'] }}</option>@endforeach</select></x-portal.field>
                        <x-portal.field name="priority" label="Priority"><select class="pselect" name="priority">@foreach (\App\Models\Task::PRIORITIES as $k => $d)<option value="{{ $k }}" @selected($k === 'normal')>{{ $d['label'] }}</option>@endforeach</select></x-portal.field>
                        <x-portal.field name="due_date" label="Due" optional><input class="pinput" type="date" name="due_date"></x-portal.field>
                    </div>
                    <div class="pfield"><label>Assign to</label>
                        <div class="memberpick memberpick--compact">
                            @foreach ($project->members as $p)<label class="memberpick__opt"><input type="checkbox" name="assignee_ids[]" value="{{ $p->id }}"><span class="pavatar pavatar--xs">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span><span>{{ $p->name }}</span></label>@endforeach
                        </div></div>
                </div>
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit">Add task</button></div>
            </form>
        </dialog>
    @endif
    @if ($canEdit)
        <dialog class="pmodal pmodal--wide" id="editProject" aria-labelledby="editProjectTitle" @if ($errors->any() && old('_form') === 'edit') data-open-on-load @endif>
            <form method="post" action="{{ route('admin.projects.update', $project) }}" data-loading novalidate>
                @csrf @method('PUT') <input type="hidden" name="_form" value="edit">
                <div class="pmodal__head"><h3 id="editProjectTitle">Edit project</h3></div>
                @include('portal.admin.projects._form', ['project' => $project, 'form' => $form])
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit">Save project</button></div>
            </form>
        </dialog>
    @endif
    @if ($canDelete)
        <dialog class="pmodal" id="deleteProject" aria-labelledby="delProjTitle">
            <form method="post" action="{{ route('admin.projects.destroy', $project) }}" data-loading>
                @csrf @method('DELETE')
                <div class="pmodal__head"><h3 id="delProjTitle">Delete this project?</h3><p>“{{ $project->name }}” and its {{ $tasks->count() }} {{ \Illuminate\Support\Str::plural('task', $tasks->count()) }} with all comments are erased for good. To keep a record, set the status to Archived instead.</p></div>
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Keep it</button><button class="pbtn pbtn--danger" type="submit"><x-icon name="trash" /> Delete project</button></div>
            </form>
        </dialog>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-tasks.js') }}"></script>
    <script>(function () { var d = document.querySelector('dialog[data-open-on-load]'); if (d && d.showModal) { d.showModal(); } })();</script>
@endpush
