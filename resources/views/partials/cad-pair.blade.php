{{-- One AutoCAD before / after card cut from the client's CAD PDFs. Prop: $pair (App\Support\Portfolio::cadPairs() entry).
     The "View complete sheet" button opens the client's own link (config/samples.php) and is left out until a link is set. --}}
@once
    @push('head')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cad-pairs.css') }}">
    @endpush
@endonce
<article class="cadpair" data-reveal>
    <x-compare label-a="Before" label-b="After" ratio="1100 / 1104" :aria="'Drag to compare: ' . $pair['capA'] . ' and ' . strtolower($pair['capB'])">
        <x-slot:a><img class="compare__img" src="{{ $pair['a']['src'] }}" width="{{ $pair['a']['w'] }}" height="{{ $pair['a']['h'] }}" alt="{{ $pair['altA'] }}" loading="lazy" decoding="async"></x-slot:a>
        <x-slot:b><img class="compare__img" src="{{ $pair['b']['src'] }}" width="{{ $pair['b']['w'] }}" height="{{ $pair['b']['h'] }}" alt="{{ $pair['altB'] }}" loading="lazy" decoding="async"></x-slot:b>
    </x-compare>
    <div class="cadpair__body">
        <p class="cadpair__meta">{{ $pair['meta'] }}</p>
        <h3>{{ $pair['title'] }}</h3>
        <p class="cadpair__caps"><span><b>Before</b> {{ $pair['capA'] }}</span><span><b>After</b> {{ $pair['capB'] }}</span></p>
        @if (! empty($pair['sheet_url']))
            <a class="btn-ay btn-ay--sm cadpair__btn" href="{{ $pair['sheet_url'] }}" target="_blank" rel="noopener">View complete sheet <x-icon name="arrow-right" /></a>
        @endif
    </div>
</article>
