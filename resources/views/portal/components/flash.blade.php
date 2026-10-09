@foreach (['success' => 'check-circle', 'error' => 'alert', 'info' => 'info'] as $type => $icon)
    @if (session($type))
        <div class="pflash pflash--{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
            <x-icon :name="$icon" />
            <span>{{ session($type) }}</span>
            <button class="pflash__x" type="button" data-flash-close aria-label="Dismiss"><x-icon name="x" /></button>
        </div>
    @endif
@endforeach
@if ($errors->any() && ! $errors->hasBag('password') && ! session('error'))
    <div class="pflash pflash--error" role="alert">
        <x-icon name="alert" />
        <span>{{ $errors->count() > 1 ? 'Please check the highlighted fields and try again.' : $errors->first() }}</span>
        <button class="pflash__x" type="button" data-flash-close aria-label="Dismiss"><x-icon name="x" /></button>
    </div>
@endif
<span data-flash-anchor hidden></span>
