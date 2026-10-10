@extends('portal.layouts.app')
@section('title', 'New request')
@section('heading', 'New request')

@section('content')
    @php
        $icons = ['visualization' => 'eye', 'bim' => 'box', 'cad' => 'file-text', 'outsourcing' => 'users', 'unsure' => 'message'];
        $sel = old('service', $selService);
    @endphp
    <a class="crumb" href="{{ route('customer.dashboard') }}"><x-icon name="arrow-left" /> My orders</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">Start a <em>new request</em></h1>
            <p class="phead__sub">Tell us what you need. We will reply in a private conversation, usually within one business day, and send a custom offer when we agree on the scope. Free to ask, no commitment.</p>
        </div>
    </div>

    <form class="pgrid pgrid--main" method="post" action="{{ route('customer.requests.store') }}" enctype="multipart/form-data" data-loading novalidate>
        @csrf
        <div style="display:grid;gap:1.1rem;min-width:0">
            <section class="pcard">
                <div class="pcard__head"><h2 class="pcard__title"><span class="num-badge">1</span> What do you need help with?</h2></div>
                <div class="pcard__body">
                    <div class="pfield {{ $errors->has('service') ? 'has-error' : '' }}">
                        <div class="svcpick" role="radiogroup" aria-label="Service">
                            @foreach ($services as $k => $label)
                                <label><input type="radio" name="service" value="{{ $k }}" @checked($sel === $k) required><span><x-icon :name="$icons[$k] ?? 'message'" />{{ $label }}</span></label>
                            @endforeach
                        </div>
                        @error('service')<span class="pfield__error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="pcard">
                <div class="pcard__head"><h2 class="pcard__title"><span class="num-badge">2</span> Project details</h2></div>
                <div class="pcard__body pform">
                    <x-portal.field name="message" label="Describe the project and the outcome you need" hint="What do you have (sketches, PDFs, CAD files, a model)? What should we deliver, and any standards to follow?">
                        <textarea class="ptextarea" id="message" name="message" rows="6" maxlength="4000" required data-autosize placeholder="e.g. Two-storey house, I have CAD plans and need a Revit model and 3 exterior renders by the end of the month.">{{ old('message') }}</textarea>
                    </x-portal.field>
                    <div class="prow">
                        <x-portal.field name="deadline" label="Wanted by" optional><input class="pinput" type="date" id="deadline" name="deadline" min="{{ now()->toDateString() }}" value="{{ old('deadline') }}"></x-portal.field>
                        <x-portal.field name="company" label="Company / studio" optional><input class="pinput" id="company" name="company" maxlength="160" value="{{ old('company') }}" autocomplete="organization"></x-portal.field>
                    </div>
                    <x-portal.field name="links" label="Links to files or references" optional><input class="pinput" id="links" name="links" maxlength="1000" value="{{ old('links') }}" placeholder="Drive, Dropbox, WeTransfer… (or attach files below)"></x-portal.field>
                    <div class="pfield">
                        <label>Attach files <span class="opt">(optional)</span></label>
                        <label class="drop" data-drop>
                            <x-icon name="upload" />
                            <b>Drop files here or click to choose</b>
                            <small>Up to {{ config('portal.uploads.max_files') }} files · {{ $maxMb >= 1 ? round($maxMb) : 1 }} MB each · zip big folders</small>
                            <input type="file" name="files[]" multiple>
                        </label>
                        <ul class="filelist" data-drop-list></ul>
                        @error('files')<span class="pfield__error">{{ $message }}</span>@enderror
                        @foreach ($errors->get('files.*') as $msgs)<span class="pfield__error">{{ $msgs[0] }}</span>@endforeach
                    </div>
                    <label class="pcheck"><input type="checkbox" name="nda" value="1" @checked(old('nda'))> Please send a mutual NDA before I share detailed files.</label>
                </div>
            </section>
        </div>

        <aside style="display:grid;gap:1.1rem;align-content:start">
            <div class="savebar">
                <b style="font:400 1.4rem/1.2 var(--f-display)">Ready when you are</b>
                <small style="text-align:left;font-size:.84rem;line-height:1.5">We read every request and reply personally. You will get an e-mail when we do.</small>
                <button class="pbtn pbtn--primary pbtn--block pbtn--lg" type="submit"><x-icon name="send" /> Send request</button>
            </div>
            <div class="pcard"><div class="pcard__body">
                <div class="how" style="grid-template-columns:1fr;gap:.9rem">
                    <div style="display:flex;gap:.8rem"><span class="how__n" style="margin:0;flex:none">1</span><div><b style="font:700 .9rem var(--f-body)">We reply</b><br><span class="muted" style="font-size:.84rem">Questions or details, in your private conversation.</span></div></div>
                    <div style="display:flex;gap:.8rem"><span class="how__n" style="margin:0;flex:none">2</span><div><b style="font:700 .9rem var(--f-body)">You get an offer</b><br><span class="muted" style="font-size:.84rem">Price and timing, right inside the chat.</span></div></div>
                    <div style="display:flex;gap:.8rem"><span class="how__n" style="margin:0;flex:none">3</span><div><b style="font:700 .9rem var(--f-body)">Pay &amp; we start</b><br><span class="muted" style="font-size:.84rem">Secure card payment. Nothing is charged before.</span></div></div>
                </div>
            </div></div>
        </aside>
    </form>
@endsection
