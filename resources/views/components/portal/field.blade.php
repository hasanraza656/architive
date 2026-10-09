{{-- Labelled form field with inline validation message. <x-portal.field name="email" label="E-mail" optional hint="..."> <input ...> </x-portal.field> --}}
@props(['name', 'label', 'optional' => false, 'hint' => null, 'bag' => 'default'])
@php $error = $errors->getBag($bag)->first($name); @endphp
<div class="pfield {{ $error ? 'has-error' : '' }}">
    <label for="{{ $attributes->get('for', $name) }}">{{ $label }} @if ($optional)<span class="opt">(optional)</span>@endif</label>
    {{ $slot }}
    @if ($hint && ! $error)<span class="pfield__hint">{{ $hint }}</span>@endif
    @if ($error)<span class="pfield__error" data-err="{{ $name }}">{{ $error }}</span>@endif
</div>
