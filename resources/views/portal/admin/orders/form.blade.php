@extends('portal.layouts.app')
@php
    $editing = $order->exists;
    $sym = ['usd' => '$', 'eur' => '€', 'gbp' => '£'][config('portal.currency')] ?? strtoupper(config('portal.currency')) . ' ';

    // rows: failed submit -> what was typed; edit -> saved items; new -> one empty row
    if (old('items')) {
        $rows = collect(old('items'))->values()->all();
    } elseif ($editing && $order->items->count()) {
        $rows = $order->items->map(fn ($it) => ['description' => $it->description, 'quantity' => rtrim(rtrim(number_format($it->quantity, 2, '.', ''), '0'), '.'), 'unit_price' => number_format($it->unit_price_cents / 100, 2, '.', '')])->all();
    } else {
        $rows = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    }
    $dueIso = old('due_at', $order->due_at?->toIso8601String());
    $isPending = $editing && $order->status === \App\Enums\OrderStatus::Pending;
    $isRequest = $editing && $order->status === \App\Enums\OrderStatus::Request;
@endphp
@section('title', $editing ? 'Edit ' . $order->number : 'New order')
@section('heading', $editing ? $order->number : 'New order')

@section('content')
    <a class="crumb" href="{{ $editing ? route('admin.orders.show', $order) : route('admin.orders.index') }}"><x-icon name="arrow-left" /> {{ $editing ? 'Back to order' : 'All orders' }}</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">{{ $isRequest ? 'Create' : ($editing ? 'Edit' : 'New') }} <em>{{ $isRequest ? 'custom offer' : 'order' }}</em></h1>
            <p class="phead__sub">{{ $isRequest ? 'Price what you agreed in the conversation. The customer receives it as an offer card in the chat and by e-mail, and can pay right away.' : ($isPending ? 'This invoice was already sent. Changes are visible to the customer as soon as you save.' : 'Build the invoice, then send it. The customer pays online and the order starts.') }}</p>
        </div>
    </div>

    <form id="orderForm" class="builder" method="post" action="{{ $editing ? route('admin.orders.update', $order) : route('admin.orders.store') }}" data-currency="{{ config('portal.currency') }}" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif
        <script type="application/json" id="customerData">{!! json_encode($customers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>

        <div class="builder__form">
            {{-- 1. customer --}}
            <section class="pcard">
                <div class="pcard__head"><h2 class="pcard__title"><span class="num-badge">1</span> Customer</h2></div>
                <div class="pcard__body">
                    <div class="pfield {{ $errors->has('customer_id') ? 'has-error' : '' }}">
                        <div class="combo" data-combo>
                            <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', $presetCustomer) }}">
                            <button class="combo__box" type="button" aria-haspopup="listbox" aria-expanded="false">
                                <span class="pavatar pavatar--soft"><x-icon name="user" /></span>
                                <span class="combo__sel"><span class="combo__ph">Choose a customer…</span></span>
                                <x-icon name="chevron-down" />
                            </button>
                            <div class="combo__panel">
                                <input class="pinput combo__search" type="search" placeholder="Search by name or e-mail" autocomplete="off" aria-label="Search customers">
                                <div class="combo__list" role="listbox"></div>
                                <button class="combo__new" type="button"><x-icon name="plus" /> Create a new customer</button>
                            </div>
                        </div>
                        @error('customer_id')<span class="pfield__error">{{ $message }}</span>@enderror
                    </div>

                    <div class="newcust" id="newCustomer" data-url="{{ route('admin.customers.store') }}">
                        <h4>New customer</h4>
                        <div class="prow">
                            <div class="pfield"><label for="nc_first_name">First name</label><input class="pinput" id="nc_first_name" name="nc_first_name" autocomplete="off"></div>
                            <div class="pfield"><label for="nc_last_name">Last name</label><input class="pinput" id="nc_last_name" name="nc_last_name" autocomplete="off"></div>
                        </div>
                        <div class="pfield"><label for="nc_email">E-mail address</label><input class="pinput" type="email" id="nc_email" name="nc_email" autocomplete="off"></div>
                        @include('portal.shared.phone-field', ['countries' => $countries, 'country' => 'US', 'phone' => null, 'prefix' => 'nc_'])
                        <div class="pactions">
                            <button class="pbtn pbtn--dark pbtn--sm" type="button" data-nc-save>Create &amp; select</button>
                            <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-nc-cancel>Cancel</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. items --}}
            <section class="pcard">
                <div class="pcard__head"><h2 class="pcard__title"><span class="num-badge">2</span> What are you selling?</h2></div>
                <div class="pcard__body">
                    <div class="items" id="items">
                        <div class="items__head"><span>Description</span><span>Qty</span><span>Unit price ({{ trim($sym) }})</span><span style="text-align:right">Amount</span><span></span></div>
                        @foreach ($rows as $i => $row)
                            @include('portal.admin.orders._item', ['i' => $i, 'item' => $row])
                        @endforeach
                    </div>
                    <template id="itemTpl">@include('portal.admin.orders._item', ['i' => '__i__', 'item' => []])</template>
                    @error('items')<p class="pfield__error" style="margin-top:.6rem">{{ $message }}</p>@enderror
                    <button class="pbtn pbtn--ghost pbtn--sm add-item" type="button" data-add-item style="margin-top:.8rem"><x-icon name="plus" /> Add another line</button>

                    <div class="totals-box">
                        <div class="tr"><span class="muted">Subtotal</span><b data-pv="subtotal">{{ $sym }}0.00</b></div>
                        <div class="tr"><label class="muted" for="discount">Discount ({{ trim($sym) }})</label><input class="pinput" id="discount" name="discount" inputmode="decimal" value="{{ old('discount', $editing && $order->discount_cents ? number_format($order->discount_cents / 100, 2, '.', '') : '') }}" placeholder="0.00"></div>
                        <div class="tr"><label class="muted" for="tax_rate">Tax rate (%)</label><input class="pinput" id="tax_rate" name="tax_rate" inputmode="decimal" value="{{ old('tax_rate', $editing && $order->tax_rate > 0 ? rtrim(rtrim((string) $order->tax_rate, '0'), '.') : '') }}" placeholder="0"></div>
                        <div class="tr grand"><span>Total</span><b data-pv="total">{{ $sym }}0.00</b></div>
                    </div>
                </div>
            </section>

            {{-- 3. details --}}
            <section class="pcard">
                <div class="pcard__head"><h2 class="pcard__title"><span class="num-badge">3</span> Details</h2></div>
                <div class="pcard__body pform">
                    <x-portal.field name="title" label="Order title" hint="Shown to the customer as the name of this project.">
                        <input class="pinput" id="title" name="title" maxlength="190" value="{{ old('title', $order->title) }}" placeholder="e.g. Exterior visualization, Riverside House" required>
                    </x-portal.field>
                    <div class="pfield {{ $errors->has('due_at') ? 'has-error' : '' }}">
                        <label for="due_local">Due date <span class="opt">(optional)</span></label>
                        <div class="dt">
                            <input class="pinput" type="datetime-local" id="due_local">
                            <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-due-clear>Clear</button>
                        </div>
                        <input type="hidden" id="due_at" name="due_at" value="{{ $dueIso }}">
                        <div class="pills" style="margin-top:.2rem">
                            @foreach ([3 => '+3 days', 7 => '+1 week', 14 => '+2 weeks', 30 => '+1 month'] as $d => $l)
                                <button class="pill" type="button" data-due-quick="{{ $d }}">{{ $l }}</button>
                            @endforeach
                        </div>
                        <span class="pfield__hint">When set, the customer sees a live countdown as soon as the invoice is paid. Times use your browser's time zone.</span>
                        @error('due_at')<span class="pfield__error">{{ $message }}</span>@enderror
                    </div>
                    <x-portal.field name="notes" label="Note for the customer" optional hint="Appears at the bottom of the invoice: scope, delivery format, payment terms…">
                        <textarea class="ptextarea" id="notes" name="notes" maxlength="3000" data-autosize placeholder="e.g. Includes 2 revision rounds. Final files delivered as PNG + PDF.">{{ old('notes', $order->notes) }}</textarea>
                    </x-portal.field>
                </div>
            </section>
        </div>

        {{-- live preview + save --}}
        <aside class="builder__side">
            <div class="savebar">
                <div class="savebar__total"><span>Invoice total</span><b data-savetotal>{{ $sym }}0.00</b></div>
                <button class="pbtn pbtn--primary pbtn--block" type="submit" name="action" value="send"><x-icon name="send" /> {{ $isRequest ? 'Send custom offer' : ($isPending ? 'Save & re-send invoice' : 'Save & send invoice') }}</button>
                <button class="pbtn pbtn--ghost pbtn--block" type="submit" name="action" value="draft">{{ $isRequest ? 'Save offer draft' : ($isPending ? 'Save changes only' : 'Save as draft') }}</button>
                <small>{{ $isRequest ? 'A draft stays private: nothing is sent until you send the offer.' : ($isPending ? 'Re-sending e-mails the customer again.' : 'Drafts are private. Nothing is e-mailed until you send.') }}</small>
            </div>

            <div class="preview" aria-label="Invoice preview">
                <div class="preview__label">Live preview</div>
                <article class="paper">
                    <div class="paper__top">
                        <div class="paper__brand"><img src="{{ asset('assets/img/logo.png') }}" alt="Architive" width="170" height="29"><small>WhatsApp / phone: {{ config('site.phone_display') }}</small></div>
                        <div class="paper__ref"><h3>Invoice</h3><span>{{ $editing ? $order->number : 'Draft' }}</span></div>
                    </div>
                    <div class="paper__meta">
                        <div><h4>Billed to</h4><p><span data-pv="customer">Choose a customer</span><small data-pv="email"></small></p></div>
                        <div><h4>Due date</h4><p data-pv="due">Not set</p></div>
                        <div><h4>Project</h4><p data-pv="title">Untitled order</p></div>
                    </div>
                    <div class="paper__tablewrap">
                        <table>
                            <thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Amount</th></tr></thead>
                            <tbody data-pv="lines"></tbody>
                        </table>
                    </div>
                    <div class="paper__totals">
                        <div><span>Subtotal</span><span data-pv="subtotal">{{ $sym }}0.00</span></div>
                        <div data-pv-row="discount" hidden><span>Discount</span><span data-pv="discount"></span></div>
                        <div data-pv-row="tax" hidden><span>Tax (<span data-pv="taxrate">0</span>%)</span><span data-pv="tax"></span></div>
                        <div class="t"><span>Total</span><b data-pv="total">{{ $sym }}0.00</b></div>
                    </div>
                    <div class="paper__notes" data-pv-row="notes" hidden><h4>Notes</h4><span data-pv="notes"></span></div>
                </article>
            </div>
        </aside>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-order-form.js') }}"></script>
@endpush
