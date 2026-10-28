@extends('portal.layouts.app')
@section('title', 'Different account')
@section('heading', 'Invoices')

@section('content')
    <section class="pcard" style="max-width:640px;margin:2rem auto">
        <div class="pcard__head"><h1 class="pcard__title"><x-icon name="lock" /> This is not your order</h1></div>
        <div class="pcard__body">
            <p>You are signed in as <b>{{ $email }}</b>, but <b>{{ $number }}</b> belongs to a different account.</p>
            <p style="margin-top:.8rem">Please sign out and sign in again with the <b>exact e-mail address the invoice was sent to</b>. If you are not sure which one that is, ask us and we will check.</p>
            <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:1.4rem">
                <form method="post" action="{{ route('customer.logout') }}">@csrf<button class="pbtn pbtn--primary" type="submit"><x-icon name="log-out" /> Sign out and try again</button></form>
                <a class="pbtn pbtn--ghost" href="{{ route('customer.dashboard') }}">Go to my orders</a>
            </div>
        </div>
    </section>
@endsection
