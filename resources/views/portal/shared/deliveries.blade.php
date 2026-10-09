{{-- Everything delivered so far. Prop: $order (deliveries.files + user loaded) --}}
<section class="pcard">
    @forelse ($order->deliveries as $d)
        <div class="deliv">
            <div class="deliv__head">
                <span class="pcard__title"><x-icon name="package" /> Delivery {{ $order->deliveries->count() - $loop->index }}</span>
                <span class="muted">by {{ $d->user->name }} · <time data-dt="datetime" datetime="{{ $d->created_at->toIso8601String() }}">{{ $d->created_at->format('M j, Y H:i') }}</time></span>
            </div>
            @if ($d->note)<div class="deliv__note">{{ $d->note }}</div>@endif
            <div class="files">
                @foreach ($d->files as $f)
                    <a class="file" href="{{ route('portal.files.show', $f) }}" download>
                        <span class="file__ic">{{ \Illuminate\Support\Str::limit($f->extension() ?: 'file', 4, '') }}</span>
                        <span class="file__t"><b>{{ $f->original_name }}</b><small>{{ $f->humanSize() }}</small></span>
                        <x-icon name="download" />
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty"><x-icon name="package" /><b>No deliveries yet</b><span>Delivered files will appear here as soon as the work is ready.</span></div>
    @endforelse
</section>
