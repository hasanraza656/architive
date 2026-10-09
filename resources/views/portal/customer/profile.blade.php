@extends('portal.layouts.app')
@section('title', 'My profile')
@section('heading', 'My profile')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">My <em>profile</em></h1>
            <p class="phead__sub">Keep your contact details up to date.</p>
        </div>
    </div>

    <form class="pcard" method="post" action="{{ route('customer.profile.update') }}" data-loading novalidate>
        @csrf @method('PUT')
        <div class="pcard__body pform" style="max-width:720px">
            <div class="prow">
                <x-portal.field name="first_name" label="First name"><input class="pinput" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></x-portal.field>
                <x-portal.field name="last_name" label="Last name"><input class="pinput" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required></x-portal.field>
            </div>
            <div class="pfield"><label for="email_ro">Sign-in e-mail</label><input class="pinput" id="email_ro" value="{{ $user->email }}" disabled><span class="pfield__hint">To change your e-mail address, message us and we will update it for you.</span></div>
            @include('portal.shared.phone-field', ['countries' => $countries, 'country' => $user->phone_country, 'phone' => $user->phone])
            <div class="pactions"><button class="pbtn pbtn--primary" type="submit">Save profile</button></div>
        </div>
    </form>
@endsection
