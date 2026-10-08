{{-- Auto-running strip of real samples (pauses on hover/focus; static + scrollable for reduced motion).
     Props: $category ('visualization'|'bim'|'cad'), optional $limit, $big (taller tiles for the home page), $rev (run the other way).
     Tiles keep each image's own proportions, so nothing is cropped. Each opens the full image in the lightbox. --}}
@php
    $reel = \App\Support\Portfolio::reel($category, $limit ?? 12);
@endphp
@if (count($reel))
    <div class="reel {{ ! empty($big) ? 'reel--big' : '' }} {{ ! empty($rev) ? 'reel--rev' : '' }}" aria-label="Samples of our work">
        <div class="reel__track">
            @foreach ([false, true] as $dup)
                <ul class="reel__row" @if ($dup) aria-hidden="true" @endif>
                    @foreach ($reel as $img)
                        <li class="{{ ! empty($img['sheet']) ? 'is-sheet' : '' }}">
                            <a href="{{ $img['src'] }}" data-lightbox="reel-{{ $category }}{{ ! empty($big) ? '-h' : '' }}{{ $dup ? '-b' : '' }}" data-title="{{ $img['title'] ?? '' }}" data-alt="{{ $img['alt'] }}" @if ($dup) tabindex="-1" @endif>
                                <img src="{{ $img['thumb'] }}" srcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" sizes="(min-width: 992px) 40vw, 80vw"
                                     width="{{ $img['tw'] }}" height="{{ $img['th'] }}" alt="{{ $dup ? '' : $img['alt'] }}" loading="{{ $loop->first && ! $dup ? 'eager' : 'lazy' }}" decoding="async">
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
@endif
