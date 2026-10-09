@extends('portal.layouts.app')
@section('title', $order->number . ' · ' . $order->title)
@section('heading', 'Order ' . $order->number)

@section('content')
    <a class="crumb" href="{{ route('admin.orders.index') }}"><x-icon name="arrow-left" /> All orders</a>
    @include('portal.shared.workspace', ['isAdmin' => true])

    {{-- deliver work --}}
    <dialog class="pmodal" id="deliverDialog" aria-labelledby="deliverTitle">
        <form method="post" action="{{ route('admin.orders.deliver', $order) }}" enctype="multipart/form-data" data-loading>
            @csrf
            <div class="pmodal__head"><h3 id="deliverTitle">Deliver the work</h3><p>The customer is e-mailed and can download the files straight away.</p></div>
            <div class="pmodal__body">
                <label class="drop" data-drop>
                    <x-icon name="upload" />
                    <b>Drop files here or click to choose</b>
                    <small>Up to {{ $maxUploadMb >= 1 ? round($maxUploadMb) : 1 }} MB per file · {{ config('portal.uploads.max_files') }} files · zip big folders</small>
                    <input type="file" name="files[]" multiple>
                </label>
                <ul class="filelist" data-drop-list></ul>
                <div class="pfield"><label for="note">Message to the customer <span class="opt">(optional)</span></label><textarea class="ptextarea" id="note" name="note" maxlength="3000" placeholder="What is included, how to open it, anything to check…"></textarea></div>
            </div>
            <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Cancel</button><button class="pbtn pbtn--primary" type="submit"><x-icon name="package" /> Deliver now</button></div>
        </form>
    </dialog>

    {{-- cancel --}}
    <dialog class="pmodal" id="cancelDialog" aria-labelledby="cancelTitle">
        <form method="post" action="{{ route('admin.orders.cancel', $order) }}" data-loading>
            @csrf
            <div class="pmodal__head"><h3 id="cancelTitle">Cancel this order?</h3><p>@if ($order->paid_at)It is already paid. Cancelling does <b>not</b> refund automatically: refund it from your Stripe dashboard.@else The customer will be notified and the invoice can no longer be paid.@endif</p></div>
            <div class="pmodal__body"><div class="pfield"><label for="reason">Reason <span class="opt">(optional, shown to the customer)</span></label><input class="pinput" id="reason" name="reason" maxlength="300"></div></div>
            <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Keep order</button><button class="pbtn pbtn--danger" type="submit">Cancel order</button></div>
        </form>
    </dialog>
@endsection
