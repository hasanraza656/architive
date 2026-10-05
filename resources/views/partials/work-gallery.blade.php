{{-- Filterable masonry gallery of the studio's visualization work. Items open in the lightbox. --}}
@php
    $cats  = \App\Support\Portfolio::vizCategories();
    $items = \App\Support\Portfolio::viz();
@endphp
@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ImageGallery',
        'name' => 'Architectural visualization work by Architive',
        'url' => rtrim(url()->current(), '/') . '/',
        'publisher' => ['@id' => url('/') . '/#organization'],
        'image' => collect($items)->map(fn ($i) => ['@type' => 'ImageObject', 'contentUrl' => $i['src'], 'name' => $i['title'], 'caption' => $i['alt'], 'creator' => ['@id' => url('/') . '/#organization']])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush
<div data-gallery>
    <div class="work-toolbar">
        <div class="filters" role="group" aria-label="Filter visualization work">
            <button type="button" class="is-active" data-gfilter="all" aria-pressed="true">All</button>
            @foreach ($cats as $k => $label)
                <button type="button" data-gfilter="{{ $k }}" aria-pressed="false">{{ $label }}</button>
            @endforeach
        </div>
        <p class="work-count" aria-live="polite"><b data-gcount>{{ count($items) }}</b> visuals shown</p>
    </div>
    <ul class="work-grid">
        @foreach ($items as $it)
            <li class="work-item" data-gcat="{{ $it['cat'] }}">
                <a href="{{ $it['src'] }}" data-lightbox="viz" data-title="{{ $it['title'] }}" data-sub="{{ $cats[$it['cat']] }}" data-alt="{{ $it['alt'] }}">
                    <img src="{{ $it['thumb'] }}" width="{{ $it['tw'] }}" height="{{ $it['th'] }}" alt="{{ $it['alt'] }}" loading="lazy" decoding="async">
                    <span class="work-item__cap"><span><small>{{ $cats[$it['cat']] }}</small><strong>{{ $it['title'] }}</strong></span><i><x-icon name="search" /></i></span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
