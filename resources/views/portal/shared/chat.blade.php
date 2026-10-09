{{-- Conversation box. Props: $order, $feed (ChatService::feed). Behaviour: public/assets/js/portal-chat.js --}}
<section class="chat" data-chat data-feed-url="{{ route('portal.chat.index', $order) }}" data-poll="{{ config('portal.chat.poll_seconds') }}" data-status="{{ $order->status->value }}" data-max-files="{{ config('portal.uploads.max_files') }}" aria-label="Conversation">
    <div class="chat__list" data-chat-list aria-live="polite" aria-relevant="additions"></div>

    @if ($order->isChatOpen())
        <form class="chat__composer" data-chat-form method="post" action="{{ route('portal.chat.store', $order) }}" enctype="multipart/form-data">
            <div class="chat__files" data-chat-files></div>
            <p class="chat__err" data-chat-err role="alert" hidden></p>
            <div class="chat__row">
                <button class="chat__btn" type="button" data-chat-attach aria-label="Attach files"><x-icon name="paperclip" /></button>
                <textarea name="body" rows="1" maxlength="5000" placeholder="Write a message…" aria-label="Message"></textarea>
                <button class="chat__btn chat__btn--send" type="submit" data-chat-send aria-label="Send message"><x-icon name="send" /></button>
            </div>
            <input type="file" multiple hidden>
            <span class="chat__hint">Enter to send · Shift+Enter for a new line · up to {{ round(config('portal.uploads.max_kb') / 1024) }} MB per file</span>
        </form>
    @else
        <div class="chat__closed"><x-icon name="lock" /> This conversation is closed.</div>
    @endif

    <template data-empty>
        <x-icon name="message" />
        <b>{{ auth()->user()->isAdmin() ? 'No messages yet' : 'Say hello to the team' }}</b>
        <span>{{ auth()->user()->isAdmin() ? 'Share progress, ask questions or send files. The customer is e-mailed when they are away.' : 'Ask a question, share references or send files. We will reply here.' }}</span>
    </template>
    <script type="application/json" id="chatFeed">{!! json_encode($feed, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
</section>
