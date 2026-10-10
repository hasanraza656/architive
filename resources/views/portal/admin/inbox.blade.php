@extends('portal.layouts.app')
@section('title', 'Inbox')
@section('heading', 'Inbox')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-inbox.css') }}">
@endpush

@php
    $keep = fn (array $over = []) => array_filter(array_merge(request()->except('page'), $over), fn ($v) => $v !== null && $v !== '' );
    $state = $f['state'];
@endphp

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Inbox</h1>
            <p class="phead__sub">Every message from every order and request in one place.</p>
        </div>
        <div class="phead__actions">
            <form method="post" action="{{ route('admin.inbox.read-all') }}" data-loading>@csrf
                <button class="pbtn pbtn--ghost" type="submit" @disabled(! $unread)><x-icon name="check-circle" /> Mark all read</button>
            </form>
        </div>
    </div>

    <div class="pills" role="navigation" aria-label="Filter by read state" style="margin-bottom:.9rem">
        <a class="pill {{ $state === 'all' ? 'is-on' : '' }}" href="{{ route('admin.inbox', $keep(['state' => null])) }}">All</a>
        <a class="pill {{ $state === 'unread' ? 'is-on' : '' }}" href="{{ route('admin.inbox', $keep(['state' => 'unread'])) }}">Unread <small data-inbox-count>{{ $unread }}</small></a>
        <a class="pill {{ $state === 'read' ? 'is-on' : '' }}" href="{{ route('admin.inbox', $keep(['state' => 'read'])) }}">Read</a>
    </div>

    <form class="pcard ibx-filters" method="get" action="{{ route('admin.inbox') }}" role="search" aria-label="Filter messages">
        @if ($state !== 'all')<input type="hidden" name="state" value="{{ $state }}">@endif
        <div class="pfield ibx-filters__q">
            <label for="ibxQ">Search</label>
            <div class="search" style="max-width:none"><x-icon name="search" /><input class="pinput" id="ibxQ" type="search" name="q" value="{{ $f['q'] }}" placeholder="Words inside a message"></div>
        </div>
        <div class="pfield">
            <label for="ibxCustomer">Customer</label>
            <select class="pselect" id="ibxCustomer" name="customer" data-autosubmit>
                <option value="">All customers</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}" @selected($f['customer'] === $c->id)>{{ $c->name ?: $c->email }}</option>
                @endforeach
            </select>
        </div>
        <div class="pfield">
            <label for="ibxOrder">Order or request</label>
            <input class="pinput" id="ibxOrder" type="text" name="order" value="{{ $f['order'] }}" placeholder="ARC-1002 or title">
        </div>
        <div class="pfield">
            <label for="ibxFrom">Show</label>
            <select class="pselect" id="ibxFrom" name="from" data-autosubmit>
                <option value="customers" @selected($f['from'] === 'customers')>Messages from customers</option>
                <option value="team" @selected($f['from'] === 'team')>Sent by the team</option>
                <option value="all" @selected($f['from'] === 'all')>Everything</option>
            </select>
        </div>
        <div class="pfield">
            <label for="ibxDf">From date</label>
            <input class="pinput" id="ibxDf" type="date" name="date_from" value="{{ $f['date_from'] }}" data-autosubmit>
        </div>
        <div class="pfield">
            <label for="ibxDt">To date</label>
            <input class="pinput" id="ibxDt" type="date" name="date_to" value="{{ $f['date_to'] }}" data-autosubmit>
        </div>
        <div class="ibx-filters__act">
            <button class="pbtn pbtn--primary" type="submit"><x-icon name="search" /> Filter</button>
            @if ($filtered)<a class="pbtn pbtn--ghost" href="{{ route('admin.inbox') }}">Reset</a>@endif
        </div>
    </form>

    <div class="pcard ibx" data-inbox data-csrf="{{ csrf_token() }}">
        @forelse ($messages as $m)
            @php
                $order = $m->order;
                $mine = $m->user?->isAdmin();
                $who = $mine ? 'To ' . ($order->customer->name ?: $order->customer->email) : ($m->user?->name ?: 'Customer');
                $unreadRow = ! $mine && (bool) $m->is_unread;
                $snippet = $m->body ? \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $m->body), 150) : '';
                $open = route('admin.orders.show', $order) . '?m=' . $m->id . '#chat';
            @endphp
            <article class="mrow {{ $unreadRow ? 'is-unread' : '' }} {{ $mine ? 'is-team' : '' }}" data-row data-id="{{ $m->id }}" data-order="{{ $m->order_id }}" data-customer="{{ ! $mine ? '1' : '0' }}"
                     data-read-url="{{ route('admin.inbox.read', $m) }}" data-unread-url="{{ route('admin.inbox.unread', $m) }}">
                <span class="mrow__dot" title="Unread" aria-hidden="true"></span>
                <span class="pavatar pavatar--sm {{ $mine ? '' : 'pavatar--soft' }}" aria-hidden="true">{{ $m->user?->initials ?? '?' }}</span>
                <a class="mrow__main" href="{{ $open }}" title="Open the conversation at this message">
                    <span class="mrow__top">
                        <b class="mrow__who">{{ $who }}</b>
                        <span class="mrow__chip">{{ $order->number }}</span>
                        <x-portal.badge :status="$order->status" />
                    </span>
                    <span class="mrow__title">{{ $order->title }}</span>
                    <span class="mrow__text">{{ $snippet ?: ($m->files->count() ? 'Sent ' . $m->files->count() . ' file' . ($m->files->count() > 1 ? 's' : '') : '(empty message)') }}</span>
                </a>
                <span class="mrow__meta">
                    @if ($m->files->count())<span class="mrow__att" title="{{ $m->files->count() }} attachment(s)"><x-icon name="paperclip" /> {{ $m->files->count() }}</span>@endif
                    <time class="mrow__time" data-dt="short" datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('M j, H:i') }}</time>
                </span>
                <span class="mrow__actions">
                    <button class="mrow__btn" type="button" data-quick aria-label="Quick view" title="Quick view"><x-icon name="eye" /></button>
                    @unless ($mine)
                        <button class="mrow__btn" type="button" data-toggle-read aria-label="{{ $unreadRow ? 'Mark as read' : 'Mark as unread' }}" title="{{ $unreadRow ? 'Mark as read' : 'Mark as unread' }}"><x-icon name="mail" /></button>
                    @endunless
                    <a class="mrow__btn" href="{{ $open }}" aria-label="Open conversation" title="Open conversation"><x-icon name="arrow-up-right" /></a>
                </span>

                <template data-qv>
                    <div class="qv__head">
                        <span class="pavatar {{ $mine ? '' : 'pavatar--soft' }}" aria-hidden="true">{{ $m->user?->initials ?? '?' }}</span>
                        <div>
                            <b>{{ $m->user?->name ?: 'Customer' }}</b>{{ $mine ? ' (team)' : '' }}
                            <small>{{ $mine ? 'To ' . ($order->customer->name ?: '') . ' · ' : '' }}{{ $order->customer->email }}</small>
                        </div>
                        <time data-dt="datetime" datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('M j, Y H:i') }}</time>
                    </div>
                    <p class="qv__order"><a class="plink" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }} · {{ $order->title }}</a> <x-portal.badge :status="$order->status" /></p>
                    @if ($m->body)<div class="qv__body">{{ $m->body }}</div>@endif
                    @if ($m->files->count())
                        <div class="qv__files">
                            @foreach ($m->files as $file)
                                <a class="msg__file" href="{{ route('portal.files.show', $file) }}"><x-icon name="download" /><span>{{ $file->original_name }}</span><small>{{ $file->humanSize() }}</small></a>
                            @endforeach
                        </div>
                    @endif
                    @if ($order->isChatOpen())
                        <form class="qv__reply" data-reply action="{{ route('portal.chat.store', $order) }}" method="post">
                            <label for="qvr{{ $m->id }}">Quick reply</label>
                            <textarea class="ptextarea" id="qvr{{ $m->id }}" name="body" rows="3" maxlength="5000" placeholder="Write a reply to {{ $order->customer->first_name ?: 'the customer' }}…"></textarea>
                            <p class="qv__msg" data-reply-msg role="status" hidden></p>
                            <div class="qv__replyact"><button class="pbtn pbtn--primary pbtn--sm" type="submit"><x-icon name="send" /> Send reply</button></div>
                        </form>
                    @else
                        <p class="muted" style="margin:0">This conversation is closed, so replies are off.</p>
                    @endif
                    <a class="qv__open" href="{{ $open }}" hidden></a>
                </template>
            </article>
        @empty
            <div class="empty"><x-icon name="mail" /><b>{{ $filtered ? 'No messages match these filters' : 'No messages yet' }}</b><span>{{ $filtered ? 'Try widening the dates or clearing a filter.' : 'When customers write in an order or request, their messages show up here.' }}</span>@if ($filtered)<a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.inbox') }}">Reset filters</a>@endif</div>
        @endforelse
        @if ($messages->hasPages())<div class="pager">{{ $messages->links() }}</div>@endif
    </div>

    <dialog class="pmodal pmodal--wide" id="quickView" aria-labelledby="qvTitle">
        <div class="pmodal__head"><h3 id="qvTitle">Message</h3></div>
        <div class="pmodal__body" data-qv-body></div>
        <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Close</button><a class="pbtn pbtn--dark" href="#" data-qv-open><x-icon name="arrow-up-right" /> Open conversation</a></div>
    </dialog>
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-inbox.js') }}"></script>
@endpush
