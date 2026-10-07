{{-- Category pills under a service page's carousel hero — jump (and, where it exists, pre-filter) the full gallery further down.
     Props: $category ('visualization'|'bim'|'cad'), $jump (anchor id of that gallery). --}}
@php $cats = \App\Support\Portfolio::showcase($category)['cats']; @endphp
@if (count($cats) > 1)
    <div class="showcase-pills">
        <div class="wrap">
            <ul class="showcase__cats" aria-label="Browse by category">
                @foreach ($cats as $key => $label)
                    <li><a href="{{ $jump ?? '#work' }}" data-showcase-jump="{{ $key }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
