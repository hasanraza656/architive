{{-- Phone with searchable country / dial-code picker. Props: $countries, $country (ISO), $phone (stored "+<dial><number>" or null), optional $prefix (field name prefix, e.g. "nc_") --}}
@php
    $prefix = $prefix ?? '';
    $country = old($prefix . 'phone_country', $country ?: 'US');
    $dial = $countries[$country][1] ?? '1';
    $number = old($prefix . 'phone');
    if ($number === null && ! empty($phone)) {
        $number = str_starts_with($phone, '+' . $dial) ? substr($phone, strlen($dial) + 1) : ltrim($phone, '+');
    }
    $err = $errors->first($prefix . 'phone') ?: $errors->first($prefix . 'phone_country');
@endphp
<div class="pfield {{ $err ? 'has-error' : '' }}">
    <label for="{{ $prefix }}phone">Phone <span class="opt">(optional)</span></label>
    <div class="phone">
        <div class="cc" data-cc>
            <input type="hidden" name="{{ $prefix }}phone_country" value="{{ $country }}">
            <button class="cc__btn" type="button" aria-haspopup="listbox" aria-expanded="false" aria-label="Country dial code">
                <b data-cc-label>{{ $country }} +{{ $dial }}</b><x-icon name="chevron-down" />
            </button>
            <div class="cc__panel">
                <input class="pinput cc__search" type="search" placeholder="Search country or code" autocomplete="off" aria-label="Search country">
                <div class="cc__list" role="listbox">
                    @foreach ($countries as $iso => [$cname, $cdial])
                        <button type="button" class="cc__item {{ $iso === $country ? 'is-sel' : '' }}" role="option" data-iso="{{ $iso }}" data-dial="{{ $cdial }}" data-name="{{ $cname }}">
                            <span>{{ $cname }}</span><b>+{{ $cdial }}</b>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <input class="pinput" type="tel" inputmode="tel" id="{{ $prefix }}phone" name="{{ $prefix }}phone" value="{{ $number }}" placeholder="Phone number" autocomplete="tel-national">
    </div>
    @if ($err)<span class="pfield__error" data-err="{{ $prefix }}phone">{{ $err }}</span>@endif
</div>
