{{-- Activity timeline. Prop: $order (events.user loaded) --}}
<section class="pcard">
    <div class="pcard__head"><h2 class="pcard__title"><x-icon name="clock" /> Activity</h2></div>
    <div class="timeline">
        @foreach ($order->events as $e)
            <div class="tl tl--{{ $e->type }}">
                <span class="tl__dot"></span>
                <span><b>{{ $e->message }}</b><small>{{ $e->user?->name ?? 'System' }} · <time data-dt="short" datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->format('M j, H:i') }}</time></small></span>
            </div>
        @endforeach
    </div>
</section>
