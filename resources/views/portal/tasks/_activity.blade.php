{{-- Comments and system lines of a task. Prop: $activity (collection of TaskComment with user) --}}
@forelse ($activity as $a)
    @if ($a->kind === 'event')
        <li class="act act--event"><span class="act__dot"></span><span><b>{{ $a->user?->name ?? 'Someone' }}</b> {{ $a->body }} <time data-dt="short" datetime="{{ $a->created_at->toIso8601String() }}">{{ $a->created_at->format('M j, H:i') }}</time></span></li>
    @else
        <li class="act act--comment">
            <span class="pavatar pavatar--sm">@if ($a->user?->avatarUrl())<img src="{{ $a->user->avatarUrl() }}" alt="">@else{{ $a->user?->initials ?? '?' }}@endif</span>
            <div class="act__bubble">
                <div class="act__head"><b>{{ $a->user?->name ?? 'Someone' }}</b><time data-dt="short" datetime="{{ $a->created_at->toIso8601String() }}">{{ $a->created_at->format('M j, H:i') }}</time></div>
                <p>{{ $a->body }}</p>
            </div>
        </li>
    @endif
@empty
    <li class="act act--empty">No activity yet.</li>
@endforelse
