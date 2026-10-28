{{-- One task card on the project board. Prop: $task (with assignees loaded) --}}
@php
    $people = $task->relationLoaded('assignees') ? $task->assignees : $task->assignees()->get();
    $commentCount = $task->comments_count ?? $task->comments()->where('kind', 'comment')->count();
@endphp
<article class="tcard {{ $task->isDone() ? 'is-done' : '' }}" data-task-card data-task-id="{{ $task->id }}" data-status="{{ $task->status }}" data-panel="{{ route('admin.tasks.panel', $task) }}" tabindex="0" aria-label="{{ $task->title }}">
    <div class="tcard__top">
        <span class="prio prio--{{ $task->priority }}" title="{{ $task->priorityLabel() }} priority"></span>
        <span class="tcard__no">{{ $task->number() }}</span>
    </div>
    <h4 class="tcard__title">{{ $task->title }}</h4>
    <div class="tcard__meta">
        @if ($task->due_date)
            <span class="due {{ $task->isOverdue() ? 'is-late' : ($task->due_date->isToday() ? 'is-today' : '') }}"><x-icon name="calendar" /> {{ $task->due_date->format('M j') }}</span>
        @endif
        @if ($commentCount)<span class="tcard__cm"><x-icon name="message" /> {{ $commentCount }}</span>@endif
        <span class="avs">
            @foreach ($people->take(3) as $p)<span class="pavatar pavatar--xs" title="{{ $p->name }}">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>@endforeach
            @if ($people->count() > 3)<span class="pavatar pavatar--xs pavatar--soft">+{{ $people->count() - 3 }}</span>@endif
        </span>
    </div>
</article>
