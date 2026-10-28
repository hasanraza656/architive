{{-- Project summary for the side drawer (opened from a #project tag in a chat). Props: $project, $tasks, $team, $progress --}}
<div class="tpanel tpanel--drawer pcardview" style="--pc:{{ $project->color }}">
    <div class="tpanel__top">
        <span class="tpanel__proj"><i style="background:{{ $project->color }}"></i> Project</span>
        <span class="pbadge pbadge--{{ $project->statusTone() }}" style="margin-left:auto">{{ $project->statusLabel() }}</span>
    </div>
    <h2 class="tpanel__title tpanel__title--static">{{ $project->name }}</h2>
    @if ($project->description)<p class="tpanel__text">{{ \Illuminate\Support\Str::limit($project->description, 320) }}</p>@endif

    <div class="tfields">
        <div class="tfield"><span class="tfield__k"><x-icon name="calendar" /> Due</span><span class="tfield__v {{ $project->isOverdue() ? 'is-late' : '' }}">{{ $project->due_date ? $project->due_date->format('M j, Y') : 'No due date' }}</span></div>
        <div class="tfield"><span class="tfield__k"><x-icon name="check-circle" /> Progress</span><span class="tfield__v">{{ $progress }}%</span></div>
        <div class="tfield"><span class="tfield__k"><x-icon name="layers" /> Tasks</span><span class="tfield__v">{{ $tasks->where('status', 'done')->count() }}/{{ $tasks->count() }} done</span></div>
        <div class="tfield tfield--wide"><div class="projcard__prog" role="img" aria-label="{{ $progress }}% done"><span style="width:{{ $progress }}%"></span></div></div>
        @if ($team)
            <div class="tfield tfield--wide"><span class="tfield__k"><x-icon name="users" /> People</span>
                <span class="tfield__v">{{ $project->members->map->name->implode(', ') ?: 'Nobody yet' }}</span></div>
        @endif
    </div>

    <div class="tsection">
        <h3>Tasks</h3>
        @forelse ($tasks->sortBy(fn ($t) => $t->status === 'done' ? 1 : 0)->take(10) as $t)
            <button class="trow trow--mini" type="button" data-open-task data-panel="{{ route('portal.cards.task', $t) }}">
                <span class="tcheck tcheck--static" aria-pressed="{{ $t->isDone() ? 'true' : 'false' }}"><x-icon name="check" /></span>
                <span class="trow__title {{ $t->isDone() ? 'is-done' : '' }}">{{ $t->title }}</span>
                <span class="pbadge pbadge--{{ $t->statusTone() }}">{{ $t->statusLabel() }}</span>
            </button>
        @empty
            <p class="muted" style="margin:0">No tasks yet.</p>
        @endforelse
        @if ($tasks->count() > 10)<p class="muted" style="margin:.6rem 0 0;font-size:.85rem">and {{ $tasks->count() - 10 }} more</p>@endif
    </div>

    @if ($team)
        <div class="tpanel__foot"><a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.projects.show', $project) }}"><x-icon name="external" /> Open project page</a></div>
    @endif
</div>
