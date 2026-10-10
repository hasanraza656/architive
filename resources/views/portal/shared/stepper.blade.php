{{-- Progress steps. Prop: $order. Requests from the website/portal get a leading "Request" step. --}}
@php
    use App\Enums\OrderStatus as S;
    $fromRequest = $order->isRequestOrigin();
    $cur = $fromRequest
        ? [S::Draft->value => 1, S::Request->value => 1, S::Pending->value => 2, S::Active->value => 3, S::Delivered->value => 4, S::Completed->value => 6][$order->status->value] ?? 1
        : [S::Draft->value => 0, S::Request->value => 0, S::Pending->value => 1, S::Active->value => 2, S::Delivered->value => 3, S::Completed->value => 5][$order->status->value] ?? 0;
    $steps = $fromRequest
        ? [['Request', 'message'], ['Offer', 'send'], ['Payment', 'credit-card'], ['In progress', 'zap'], ['Delivered', 'package'], ['Completed', 'check']]
        : [['Invoice sent', 'send'], ['Payment', 'credit-card'], ['In progress', 'zap'], ['Delivered', 'package'], ['Completed', 'check']];
@endphp
@if ($order->status !== S::Cancelled)
    <ol class="steps {{ $fromRequest ? 'steps--6' : '' }}" aria-label="Order progress">
        @foreach ($steps as $i => [$label, $icon])
            <li class="step {{ $i < $cur ? 'is-done' : ($i === $cur ? 'is-now' : '') }}" @if ($i === $cur) aria-current="step" @endif>
                <span class="step__dot"><x-icon :name="$i < $cur ? 'check' : $icon" /></span>
                <span>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
@endif
