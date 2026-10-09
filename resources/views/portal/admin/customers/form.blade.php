@extends('portal.layouts.app')
@php $editing = $customer->exists; @endphp
@section('title', $editing ? 'Edit customer' : 'New customer')
@section('heading', 'Customers')

@section('content')
    <a class="crumb" href="{{ $editing ? route('admin.customers.show', $customer) : route('admin.customers.index') }}"><x-icon name="arrow-left" /> {{ $editing ? 'Back to customer' : 'All customers' }}</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $editing ? 'Edit' : 'New' }} <em>customer</em></h1>
            <p class="phead__sub">{{ $editing ? 'Update contact details.' : 'An account is created automatically. The customer signs in with a code sent to their e-mail.' }}</p>
        </div>
    </div>

    <form class="pcard" method="post" action="{{ $editing ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" data-loading novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="pcard__body pform" style="max-width:720px">
            <div class="prow">
                <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" value="{{ old('first_name', $customer->first_name) }}" autocomplete="off" required autofocus></x-portal.field>
                <x-portal.field name="last_name" label="Last name"><input class="pinput" id="last_name" name="last_name" value="{{ old('last_name', $customer->last_name) }}" autocomplete="off" required></x-portal.field>
            </div>
            <x-portal.field name="email" label="E-mail address" hint="Invoices and sign-in codes are sent here."><input class="pinput" type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" autocomplete="off" required></x-portal.field>
            @include('portal.shared.phone-field', ['countries' => $countries, 'country' => $customer->phone_country, 'phone' => $customer->phone])
            @if ($editing)
                <label class="pcheck"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active))> Account is active (can sign in)</label>
            @endif
        </div>
        <div class="pcard__head" style="border-top:1px solid var(--line);border-bottom:0">
            <span class="muted">{{ $editing ? '' : 'You can create an order for this customer next.' }}</span>
            <div class="pactions"><a class="pbtn pbtn--ghost" href="{{ route('admin.customers.index') }}">Cancel</a><button class="pbtn pbtn--primary" type="submit">{{ $editing ? 'Save changes' : 'Create customer' }}</button></div>
        </div>
    </form>
@endsection
