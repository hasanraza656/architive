{{-- One article card. Props: $post, optional $big (featured layout) --}}
@php $big = $big ?? false; @endphp
<article class="bcard {{ $big ? 'bcard--big' : '' }}" data-reveal>
    <a class="bcard__img" href="{{ $post->url() }}" tabindex="-1" aria-hidden="true">
        @if ($post->imageUrl())
            <img src="{{ $big ? $post->imageUrl() : $post->thumbUrl() }}" alt="" width="{{ $big ? 1600 : 800 }}" height="{{ $big ? 900 : 450 }}" loading="{{ $big ? 'eager' : 'lazy' }}" decoding="async">
        @else
            <span class="bcard__ph"><x-icon name="image" /></span>
        @endif
        @if ($post->category)<span class="bcard__chip">{{ $post->category->name }}</span>@endif
    </a>
    <div class="bcard__body">
        @if ($big)<span class="bcard__tag">Featured</span>@endif
        <h{{ $big ? '2' : '3' }} class="bcard__title"><a href="{{ $post->url() }}">{{ $post->title }}</a></h{{ $big ? '2' : '3' }}>
        <p class="bcard__text">{{ $post->summary($big ? 220 : 150) }}</p>
        <div class="bcard__meta">
            <span class="bavatar bavatar--sm" aria-hidden="true">@if ($post->author?->avatarUrl())<img src="{{ $post->author->avatarUrl() }}" alt="" width="32" height="32" loading="lazy">@else{{ $post->author?->initials ?? 'A' }}@endif</span>
            <span class="bcard__by">{{ $post->author?->name ?? config('site.name') }}</span>
            <span class="bcard__dot" aria-hidden="true"></span>
            <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('M j, Y') }}</time>
            <span class="bcard__dot" aria-hidden="true"></span>
            <span>{{ $post->reading_minutes }} min read</span>
        </div>
    </div>
</article>
