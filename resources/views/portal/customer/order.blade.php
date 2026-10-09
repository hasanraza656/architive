@extends('portal.layouts.app')
@section('title', $order->number . ' · ' . $order->title)
@section('heading', 'Order ' . $order->number)

@section('content')
    <a class="crumb" href="{{ route('customer.dashboard') }}"><x-icon name="arrow-left" /> My orders</a>
    @if ($canceledCheckout)
        <div class="pflash pflash--info"><x-icon name="info" /><span>Payment was not completed. You can try again whenever you are ready.</span></div>
    @endif
    @include('portal.shared.workspace', ['isAdmin' => false])

    {{-- request a revision --}}
    <dialog class="pmodal" id="revisionDialog" aria-labelledby="revTitle">
        <form method="post" action="{{ route('customer.orders.revision', $order) }}" data-loading>
            @csrf
            <div class="pmodal__head"><h3 id="revTitle">Request a revision</h3><p>Tell us what you would like changed. The order goes back to "In progress".</p></div>
            <div class="pmodal__body"><div class="pfield"><label for="reason">What should we change?</label><textarea class="ptextarea" id="reason" name="reason" minlength="5" maxlength="3000" required placeholder="e.g. Please make the facade lighter and add the tree on the left."></textarea></div></div>
            <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit">Send request</button></div>
        </form>
    </dialog>
@endsection
