@php $quick = \App\Support\Content::faqs(); @endphp
<div class="chat" data-chat>
    <button class="chat-fab" type="button" aria-expanded="false" aria-controls="chatPanel">
        <span class="chat-fab__icon"><x-icon name="message" /></span>
        <span class="chat-fab__label">Ask Architive</span>
        <i class="chat-fab__dot" aria-hidden="true"></i>
    </button>

    <section class="chat-panel" id="chatPanel" role="dialog" aria-label="Ask Architive" hidden>
        <header class="chat-panel__head">
            <div>
                <strong>Ask Architive</strong>
                <small>Quick answers to common questions</small>
            </div>
            <button type="button" class="chat-panel__close" aria-label="Close chat"><x-icon name="x" /></button>
        </header>
        <div class="chat-panel__log" aria-live="polite">
            <div class="bubble bubble--bot">Hi! Pick a question below, or tell us about your project and we'll recommend a practical scope.</div>
        </div>
        <div class="chat-panel__chips">
            @foreach ($quick as $id => $f)
                <button type="button" class="chip" data-q="{{ $f['q'] }}" data-a="{{ $f['a'] }}">{{ $f['q'] }}</button>
            @endforeach
        </div>
        <footer class="chat-panel__foot">
            <a class="btn-ay btn-ay--sm" href="{{ pu('contact') }}">Start your project <x-icon name="arrow-right" /></a>
            <a class="chat-panel__link" href="{{ pu('faqs') }}">All FAQs</a>
        </footer>
    </section>
</div>
