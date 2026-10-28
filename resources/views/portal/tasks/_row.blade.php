{{-- One task row in "My tasks" and the project list view. Prop: $task (project + assignees loaded), optional $showProject --}}
@php
    $showProject = $showProject ?? true;
    $people = $task->relationLoaded('assignees') ? $task->assignees : $task->assignees()->get();
@endphp
<div class="trow {{ $task->isDone() ? 'is-done' : '' }}" data-task-row data-task-id="{{ $task->id }}" data-status="{{ $task->status }}" data-panel="{{ route('admin.tasks.panel', $task) }}">
    <button class="tcheck" type="button" data-complete aria-pressed="{{ $task->isDone() ? 'true' : 'false' }}" aria-label="{{ $task->isDone() ? 'Mark as not done' : 'Mark as done' }}"><x-icon name="check" /></button>
    <button class="trow__main" type="button" data-open-task>
        <span class="trow__title">{{ $task->title }}</span>
        @if ($showProject && $task->project)<span class="trow__project"><i style="background:{{ $task->project->color }}"></i>{{ $task->project->name }}</span>@endif
    </button>
    <span class="trow__chips">
        @if (auth()->user()?->can('updateStatus', $task))
            <select class="pbadge pbadge--{{ $task->statusTone() }} trow__status" data-row-status aria-label="Change status of {{ $task->title }}">
                @foreach (\App\Models\Task::STATUSES as $k => $def)<option value="{{ $k }}" @selected($k === $task->status)>{{ $def['label'] }}</option>@endforeach
            </select>
        @else
            <span class="pbadge pbadge--{{ $task->statusTone() }}">{{ $task->statusLabel() }}</span>
        @endif
        @if ($task->priority === 'high' || $task->priority === 'urgent')<span class="prio-chip prio-chip--{{ $task->priority }}">{{ $task->priorityLabel() }}</span>@endif
        @if ($task->due_date)<span class="due {{ $task->isOverdue() ? 'is-late' : ($task->due_date->isToday() ? 'is-today' : '') }}"><x-icon name="calendar" /> {{ $task->due_date->format('M j') }}</span>@endif
    </span>
    <span class="avs trow__avs">
        @foreach ($people->take(3) as $p)<span class="pavatar pavatar--xs" title="{{ $p->name }}">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>@endforeach
        @if ($people->count() > 3)<span class="pavatar pavatar--xs pavatar--soft">+{{ $people->count() - 3 }}</span>@endif
    </span>
</div>
