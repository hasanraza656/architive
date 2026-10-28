{{-- Task details: used on the task page, in the side drawer (board, My tasks, chat) and read-only for customers.
     Props: $task, $mode (page|drawer), $canEdit, $canStatus, $canComment, $canDelete, $readonly, $people, $activity --}}
@php
    $mode = $mode ?? 'page';
    $assigned = $task->assignees->pluck('id')->all();
    $team = ! $readonly;
@endphp
<div class="tpanel tpanel--{{ $mode }}" data-task-panel data-task-id="{{ $task->id }}"
     @if ($team)data-update-url="{{ route('admin.tasks.update', $task) }}" data-comment-url="{{ route('admin.tasks.comment', $task) }}" data-delete-url="{{ route('admin.tasks.destroy', $task) }}"@endif>
    <div class="tpanel__top">
        <span class="tpanel__proj">
            <i style="background:{{ $task->project->color }}"></i>
            @if ($team && $mode === 'drawer')<a href="{{ route('admin.projects.show', $task->project) }}">{{ $task->project->name }}</a>@else{{ $task->project->name }}@endif
        </span>
        <span class="tpanel__no">{{ $task->number() }}</span>
        <span class="pbadge pbadge--{{ $task->statusTone() }}" data-status-badge>{{ $task->statusLabel() }}</span>
    </div>

    @if ($canEdit)
        <label class="visually-hidden" for="tt{{ $task->id }}">Title</label>
        <textarea class="tpanel__title" id="tt{{ $task->id }}" rows="1" maxlength="200" data-field="title" data-autosize>{{ $task->title }}</textarea>
    @else
        <h2 class="tpanel__title tpanel__title--static">{{ $task->title }}</h2>
    @endif

    <div class="tfields">
        <div class="tfield">
            <span class="tfield__k"><x-icon name="refresh" /> Status</span>
            @if ($canStatus)
                <select class="pselect pselect--sm" data-field="status" aria-label="Status">
                    @foreach (\App\Models\Task::STATUSES as $key => $def)<option value="{{ $key }}" @selected($task->status === $key)>{{ $def['label'] }}</option>@endforeach
                </select>
            @else<span class="tfield__v">{{ $task->statusLabel() }}</span>@endif
        </div>
        <div class="tfield">
            <span class="tfield__k"><x-icon name="zap" /> Priority</span>
            @if ($canEdit)
                <select class="pselect pselect--sm" data-field="priority" aria-label="Priority">
                    @foreach (\App\Models\Task::PRIORITIES as $key => $def)<option value="{{ $key }}" @selected($task->priority === $key)>{{ $def['label'] }}</option>@endforeach
                </select>
            @else<span class="tfield__v">{{ $task->priorityLabel() }}</span>@endif
        </div>
        <div class="tfield">
            <span class="tfield__k"><x-icon name="calendar" /> Due date</span>
            @if ($canEdit)
                <input class="pinput pinput--sm" type="date" data-field="due_date" value="{{ $task->due_date?->format('Y-m-d') }}" aria-label="Due date">
            @else<span class="tfield__v {{ $task->isOverdue() ? 'is-late' : '' }}">{{ $task->due_date ? $task->due_date->format('M j, Y') : 'No due date' }}</span>@endif
        </div>
        <div class="tfield tfield--wide">
            <span class="tfield__k"><x-icon name="users" /> Assigned to</span>
            @if ($canEdit)
                <details class="pickr" data-assignees>
                    <summary>
                        <span class="pickr__chips" data-assignee-chips>
                            @foreach ($task->assignees as $p)<span class="achip"><span class="pavatar pavatar--xs">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>{{ $p->first_name }}</span>@endforeach
                        </span>
                        <span class="pickr__add" data-assign-label>{{ $task->assignees->isEmpty() ? 'Assign people' : 'Change' }}</span>
                    </summary>
                    <div class="pickr__menu">
                        @foreach ($people as $p)
                            <label class="pickr__opt"><input type="checkbox" value="{{ $p->id }}" data-name="{{ $p->first_name }}" data-initials="{{ $p->initials }}" data-avatar="{{ $p->avatarUrl() }}" @checked(in_array($p->id, $assigned, true))>
                                <span class="pavatar pavatar--xs">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>
                                <span>{{ $p->name }}<small>{{ $p->job_title ?: ($p->isAdmin() ? 'Super admin' : $p->teamRole?->name) }}</small></span></label>
                        @endforeach
                    </div>
                </details>
            @else
                <span class="tfield__v">{{ $task->assignees->isEmpty() ? 'Nobody yet' : $task->assignees->map->name->implode(', ') }}</span>
            @endif
        </div>
    </div>

    <div class="tsection">
        <h3>Description</h3>
        @if ($canEdit)
            <textarea class="ptextarea tpanel__desc" rows="4" maxlength="6000" data-field="description" data-autosize placeholder="Add details, links or what “done” looks like…">{{ $task->description }}</textarea>
        @else
            <p class="tpanel__text">{!! $task->description ? nl2br(e($task->description)) : '<span class="muted">No description.</span>' !!}</p>
        @endif
    </div>

    @if ($team)
        <div class="tsection">
            <h3>Activity</h3>
            <ul class="acts" data-activity>@include('portal.tasks._activity', ['activity' => $activity])</ul>
            @if ($canComment)
                <form class="tcomment" data-comment-form>
                    <textarea class="ptextarea" rows="2" maxlength="3000" placeholder="Write a comment…  (Ctrl+Enter to send)" aria-label="Comment" data-autosize></textarea>
                    <button class="pbtn pbtn--primary pbtn--sm" type="submit"><x-icon name="send" /> Comment</button>
                </form>
            @endif
        </div>
    @endif

    <div class="tpanel__foot">
        <span class="tpanel__saved" data-saved aria-live="polite"></span>
        @if ($team && $mode === 'drawer')<a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.tasks.show', $task) }}"><x-icon name="external" /> Open full page</a>@endif
        @if ($canDelete)<button class="pbtn pbtn--ghost pbtn--sm tpanel__del" type="button" data-delete><x-icon name="trash" /> Delete</button>@endif
    </div>
</div>
