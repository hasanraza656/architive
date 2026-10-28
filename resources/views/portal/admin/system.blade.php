@extends('portal.layouts.app')
@section('title', 'System')
@section('heading', 'System')

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">System</h1>
            <p class="phead__sub">Apply updates after a new version of the site is uploaded, and refresh the site when changes do not show.</p>
        </div>
    </div>

    <div class="pgrid pgrid--2">
        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="layers" /> Database updates</h2></div>
            <div class="pcard__body">
                @if (count($pending))
                    <p><b>{{ count($pending) }} update{{ count($pending) === 1 ? '' : 's' }} waiting.</b> They only add new tables and columns. Your existing orders, chats and customers are not changed or deleted.</p>
                    <ul class="plist" style="margin:.8rem 0 1.2rem">
                        @foreach ($pending as $name)
                            <li><code>{{ $name }}</code></li>
                        @endforeach
                    </ul>
                    <form method="post" action="{{ route('admin.system.migrate') }}" data-loading data-confirm="Apply {{ count($pending) }} database update(s) now? Take a database backup first if you can.">
                        @csrf
                        <button class="pbtn pbtn--primary" type="submit"><x-icon name="check" /> Update the database</button>
                    </form>
                @else
                    <p><x-icon name="check-circle" /> <b>The database is up to date.</b> {{ $ran }} updates have been applied so far.</p>
                @endif

                @if ($output)
                    <pre class="sys-out">{{ $output }}</pre>
                @endif
            </div>
        </section>

        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="refresh" /> Clear cache</h2></div>
            <div class="pcard__body">
                <p>Use this when you uploaded new files and the site still shows the old version, or after changing settings. It is safe: nobody is signed out and no data is touched.</p>
                <form method="post" action="{{ route('admin.system.cache') }}" data-loading style="margin-top:1rem">
                    @csrf
                    <button class="pbtn pbtn--ghost" type="submit"><x-icon name="refresh" /> Clear cache</button>
                </form>
            </div>
        </section>

        <section class="pcard" style="grid-column: 1 / -1">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="link" /> Connect Claude (AI assistant)</h2></div>
            <div class="pcard__body">
                <p>Let Claude work in this portal for you: create projects and tasks, change due dates, statuses and assignees, read customer messages and reply to them. It acts <b>as you</b> and can only do what your account can do. It cannot delete anything or touch invoices, offers, payments or deliveries.</p>

                @if (! $mcpReady)
                    <p class="sys-warn">Apply the database update above first, then come back here.</p>
                @else
                    @if ($mcpKey)
                        <div class="sys-key">
                            <b>Your connection key (shown only once, copy it now)</b>
                            <label>For Claude.ai (Settings &rarr; Connectors &rarr; Add custom connector) paste this address:</label>
                            <input type="text" readonly value="{{ url('/mcp/' . $mcpKey) }}" onclick="this.select()">
                            <label>For Claude Desktop, Claude Code or other apps that accept headers, use this address and this key:</label>
                            <input type="text" readonly value="{{ url('/mcp') }}" onclick="this.select()">
                            <input type="text" readonly value="Authorization: Bearer {{ $mcpKey }}" onclick="this.select()">
                            <small>Treat it like a password: anyone with it can act as you. If it leaks, remove the connection below.</small>
                        </div>
                    @endif

                    <form method="post" action="{{ route('admin.system.mcp.create') }}" class="sys-new" data-loading>
                        @csrf
                        <input type="text" name="name" maxlength="80" placeholder="Name this connection, e.g. Claude on my laptop" required>
                        <button class="pbtn pbtn--primary" type="submit"><x-icon name="plus" /> Create connection</button>
                    </form>

                    @if ($tokens->count())
                        <ul class="plist">
                            @foreach ($tokens as $t)
                                <li class="sys-tok">
                                    <span><b>{{ $t->name }}</b> <code>…{{ $t->hint }}</code><small>Created {{ $t->created_at->format('M j, Y') }} &middot; {{ $t->last_used_at ? 'last used ' . $t->last_used_at->diffForHumans() : 'never used' }}</small></span>
                                    <form method="post" action="{{ route('admin.system.mcp.revoke', $t) }}" data-confirm="Remove the connection “{{ $t->name }}”? Claude will lose access straight away.">
                                        @csrf @method('DELETE')
                                        <button class="pbtn pbtn--ghost pbtn--sm" type="submit"><x-icon name="trash" /> Remove</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </section>

        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="info" /> About this installation</h2></div>
            <div class="pcard__body">
                <dl class="sys-info">
                    @foreach ($info as $k => $v)
                        <dt>{{ $k }}</dt><dd>{{ $v }}</dd>
                    @endforeach
                </dl>
            </div>
        </section>
    </div>

    <style>
        .sys-out { margin-top: 1rem; padding: .8rem 1rem; background: var(--p-bg-alt, #F4F4F0); border-radius: 10px; font: 12px/1.5 "JetBrains Mono", monospace; white-space: pre-wrap; max-height: 260px; overflow: auto; }
        .sys-info { display: grid; grid-template-columns: max-content 1fr; gap: .45rem 1.2rem; margin: 0; }
        .sys-info dt { font: 11px "JetBrains Mono", monospace; text-transform: uppercase; letter-spacing: .06em; opacity: .65; align-self: center; }
        .sys-info dd { margin: 0; font-weight: 600; }
        .sys-warn { padding: .7rem 1rem; border-radius: 10px; background: rgba(255, 214, 10, .2); }
        .sys-key { margin: 1rem 0; padding: 1rem; border-radius: 12px; border: 1px dashed var(--accent, #FFD60A); background: rgba(255, 214, 10, .12); display: grid; gap: .5rem; }
        .sys-key label { font-size: 12px; opacity: .75; margin-top: .3rem; }
        .sys-key input, .sys-new input { width: 100%; padding: .6rem .8rem; border-radius: 10px; border: 1px solid rgba(0, 0, 0, .18); background: #fff; color: #141414; font: 12.5px "JetBrains Mono", monospace; }
        .sys-new { display: flex; gap: .6rem; margin: 1rem 0; flex-wrap: wrap; } .sys-new input { flex: 1 1 260px; font-family: inherit; font-size: 14px; }
        .sys-tok { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .sys-tok small { display: block; opacity: .65; font-size: 12px; }
        .plist { list-style: none; padding: 0; }
        .plist li { padding: .3rem 0; border-bottom: 1px solid rgba(0, 0, 0, .07); font-size: 13px; }
    </style>
@endsection
