{{-- Progress steps. Prop: $order --}}
@php
    use App\Enums\OrderStatus as S;
    $cur = [S::Draft->value => 0, S::Pending->value => 1, S::Active->value => 2, S::Delivered->value => 3, S::Completed->value => 5][$order->status->value] ?? 0;
    $steps = [['Invoice sent', 'send'], ['Payment', 'credit-card'], ['In progress', 'zap'], ['Delivered', 'package'], ['Completed', 'check']];
@endphp
@if ($order->status !== S::Cancelled)
    <ol class="steps" aria-label="Order progress">
        @foreach ($steps as $i => [$label, $icon])
            <li class="step {{ $i < $cur ? 'is-done' : ($i === $cur ? 'is-now' : '') }}" @if ($i === $cur) aria-current="step" @endif>
                <span class="step__dot"><x-icon :name="$i < $cur ? 'check' : $icon" /></span>
                <span>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
@endif
