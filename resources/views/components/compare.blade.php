@props(['labelA' => 'Before', 'labelB' => 'After', 'aria' => 'Drag to compare before and after', 'ratio' => '16 / 10'])
{{-- Keyboard + touch accessible before/after slider. Slot `a` sits on top (left), slot `b` underneath (right). --}}
<div {{ $attributes->merge(['class' => 'compare']) }} data-compare style="--pos: 50%; --ratio: {{ $ratio }}">
    <div class="compare__layer compare__layer--b">{{ $b }}</div>
    <div class="compare__layer compare__layer--a">{{ $a }}</div>
    <span class="compare__label compare__label--a">{{ $labelA }}</span>
    <span class="compare__label compare__label--b">{{ $labelB }}</span>
    <span class="compare__bar" aria-hidden="true"><span class="compare__knob"><x-icon name="chevron-left" /><x-icon name="chevron-right" /></span></span>
    <input class="compare__range" type="range" min="0" max="100" step="0.5" value="50" aria-label="{{ $aria }}">
</div>
