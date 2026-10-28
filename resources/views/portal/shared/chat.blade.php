{{-- Conversation box (customer + team). Props: $order, $feed (ChatService::feed). Behaviour: public/assets/js/chat-core.js (+ chat-media.js and chat-viewer.js loaded on demand) --}}
@php
    $me = auth()->user();
    $team = $me->isTeam();
    $people = app(\App\Services\Chat\ChatService::class)->participants($order)->map(fn ($u) => [
        'id' => $u->id, 'name' => $u->name ?: $u->email, 'first' => $u->first_name, 'initials' => $u->initials, 'avatar' => $u->avatarUrl(),
        'sub' => $u->isCustomer() ? 'Customer' : ($u->job_title ?: ($u->isAdmin() ? 'Admin' : ($u->teamRole?->name ?? 'Team'))), 'customer' => $u->isCustomer(),
    ])->values()->all();
    $config = [
        'order' => $order->number,
        'me' => ['id' => $me->id, 'name' => $me->name, 'first' => $me->first_name, 'initials' => $me->initials, 'avatar' => $me->avatarUrl(), 'team' => $team],
        'urls' => [
            'feed' => route('portal.chat.index', $order),
            'store' => route('portal.chat.store', $order),
            'chunk' => route('portal.chat.chunk', $order),
            'thread' => route('portal.chat.thread', ['order' => $order, 'message' => '__ID__']),
            'react' => route('portal.chat.react', ['order' => $order, 'message' => '__ID__']),
            'members' => route('portal.chat.members', $order),
            'tags' => route('portal.chat.tags', $order),
            'taskCard' => route('portal.cards.task', ['task' => '__ID__']),
            'projectCard' => route('portal.cards.project', ['project' => '__ID__']),
        ],
        'poll' => (int) config('portal.chat.poll_seconds'),
        'status' => $order->status->value,
        'open' => $order->isChatOpen(),
        'maxFiles' => (int) config('portal.uploads.max_files'),
        'maxMb' => (float) min(config('portal.uploads.max_kb') / 1024, (float) ini_get('upload_max_filesize') ?: 9999, (float) ini_get('post_max_size') ?: 9999),
        'reactions' => config('portal.reactions'),
        'media' => config('portal.media'),
        'people' => $people,
        'version' => max(array_map(fn ($f) => @filemtime(public_path($f)) ?: 0, ['assets/js/chat-core.js', 'assets/js/chat-media.js', 'assets/js/chat-viewer.js', 'assets/css/portal-chat-viewer.css'])),
        'base' => asset('assets/js'),
        'pdf' => ['lib' => asset('assets/vendor/pdfjs/pdf.min.js'), 'worker' => asset('assets/vendor/pdfjs/pdf.worker.min.js'), 'pdflib' => asset('assets/vendor/pdf-lib/pdf-lib.min.js')],
    ];
@endphp
<section class="chat" data-chat aria-label="Conversation" data-config='@json($config, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)'>
    <header class="chat__bar">
        <button class="chat__people" type="button" data-open-members aria-label="People in this conversation">
            <span class="chat__avs" data-chat-avs></span>
            <span class="chat__people-n" data-chat-people-count>…</span>
        </button>
        <span class="chat__bar-space"></span>
        <button class="chat__bar-btn chat__expand" type="button" data-chat-expand aria-label="Full screen chat" title="Full screen"><x-icon name="arrow-up-right" /></button>
    </header>

    <div class="chat__list" data-chat-list aria-live="polite" aria-relevant="additions">
        <button class="chat__earlier" type="button" data-chat-earlier hidden>Load earlier messages</button>
    </div>
    <button class="chat__jump" type="button" data-chat-jump hidden aria-label="Jump to the newest message"><x-icon name="arrow-down" /> <span data-chat-jump-n></span></button>

    @if ($order->isChatOpen())
        <form class="chat__composer" data-chat-form method="post" action="{{ route('portal.chat.store', $order) }}" enctype="multipart/form-data">
            <div class="chat__tagged" data-chat-tagged hidden></div>
            <div class="chat__files" data-chat-files></div>
            <p class="chat__err" data-chat-err role="alert" hidden></p>

            <div class="chat__row" data-chat-row>
                <div class="chat__tools">
                    <button class="chat__btn" type="button" data-chat-attach aria-label="Attach files" title="Attach files, pictures or PDFs"><x-icon name="paperclip" /></button>
                    <button class="chat__btn" type="button" data-chat-voice aria-label="Record a voice message" title="Record a voice message"><x-icon name="mic" /></button>
                    <button class="chat__btn" type="button" data-chat-screen aria-label="Record your screen" title="Record your screen"><x-icon name="video" /></button>
                    <button class="chat__btn" type="button" data-chat-emoji aria-label="Add an emoji" title="Emoji"><x-icon name="smile" /></button>
                </div>
                <textarea name="body" rows="1" maxlength="5000" aria-label="Message" data-chat-input
                          placeholder="{{ $team ? 'Write a message…  @ tags a person · # tags a task or project' : 'Write a message…  type @ to tag someone' }}"></textarea>
                <button class="chat__btn chat__btn--send" type="submit" data-chat-send aria-label="Send message"><x-icon name="send" /></button>
            </div>
            <input type="file" multiple hidden data-chat-file>
            <span class="chat__hint">Enter to send · Shift+Enter for a new line · drop or paste files · up to {{ round(config('portal.uploads.max_kb') / 1024) }} MB each</span>
            <div class="tagpop" data-chat-tagpop role="listbox" hidden></div>
        </form>
    @else
        <div class="chat__closed"><x-icon name="lock" /> This conversation is closed.</div>
    @endif

    <template data-empty>
        <x-icon name="message" />
        <b>{{ $team ? 'No messages yet' : 'Say hello to the team' }}</b>
        <span>{{ $team ? 'Share progress, ask questions, send files, voice notes or a screen recording. The customer is e-mailed when they are away.' : 'Ask a question, share references or send files. We will reply here.' }}</span>
    </template>
    <script type="application/json" data-chat-feed>{!! json_encode($feed, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
</section>
