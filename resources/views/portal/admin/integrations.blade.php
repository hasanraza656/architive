@extends('portal.layouts.app')
@section('title', 'Integrations')
@section('heading', 'Integrations')

@php
    $bytes = function ($n) {
        if ($n === null) { return '-'; }
        if ($n >= 1073741824) { return number_format($n / 1073741824, 1) . ' GB'; }
        if ($n >= 1048576) { return number_format($n / 1048576, 1) . ' MB'; }
        return max(1, (int) round($n / 1024)) . ' KB';
    };
@endphp

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Integrations</h1>
            <p class="phead__sub">Connect outside services to the portal.</p>
        </div>
    </div>

    <section class="pcard">
        <div class="pcard__head"><h2 class="pcard__title"><x-icon name="package" /> Google Drive storage</h2></div>
        <div class="pcard__body">
            <p>Files customers and the team send in chats and deliveries are saved on the server first, so they open instantly. A background job then copies them to your Google Drive. After <b>{{ $keepDays }} days</b> (and only after checking the Drive copy is there) the server copy is removed to save space. Opening an old file fetches it back from Drive automatically.</p>

            @if (! $configured)
                <p class="int-warn"><b>Google keys are missing.</b> Add <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code> and <code>GOOGLE_REDIRECT_URI</code> to the server settings (.env), then reload this page.</p>
            @elseif (! $connections->count())
                <p class="int-hint">Not connected yet. You will choose a Google account on Google's own page. The portal only gets access to a folder it creates (called <b>Architive</b>), never the rest of the Drive.</p>
                <a class="pbtn pbtn--primary" href="{{ route('admin.integrations.google.connect') }}"><x-icon name="plus" /> Connect Google Drive</a>
            @endif

            @foreach ($connections as $c)
                @php $pct = $c->quota_total ? min(100, (int) round($c->quota_used / $c->quota_total * 100)) : null; @endphp
                <div class="int-conn {{ $c->is_active ? 'is-active' : '' }}">
                    <div class="int-conn__top">
                        <div>
                            <b>{{ $c->account_email }}</b>
                            @if ($c->is_active)<span class="pbadge pbadge--green">Receiving new files</span>@endif
                            @if ($c->status !== 'connected')<span class="pbadge pbadge--amber">Needs to be reconnected</span>@endif
                            @if ($c->moving_files)<span class="pbadge pbadge--teal">Moving files…</span>@endif
                            <small>{{ $c->account_name }} &middot; {{ $c->files_count }} {{ \Illuminate\Support\Str::plural('file', $c->files_count) }} in this Drive</small>
                        </div>
                        <div class="int-conn__act">
                            @if ($c->folderUrl())<a class="pbtn pbtn--ghost pbtn--sm" href="{{ $c->folderUrl() }}" target="_blank" rel="noopener"><x-icon name="external" /> Open folder</a>@endif
                            @if ($c->status !== 'connected')
                                <a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.integrations.google.connect') }}"><x-icon name="refresh" /> Reconnect</a>
                            @elseif (! $c->is_active)
                                <form method="post" action="{{ route('admin.integrations.drive.activate', $c) }}" data-loading>@csrf<button class="pbtn pbtn--ghost pbtn--sm" type="submit">Use for new files</button></form>
                                @if ($c->files_count && ! $c->moving_files)
                                    <form method="post" action="{{ route('admin.integrations.drive.move', $c) }}" data-confirm="Copy all {{ $c->files_count }} files of {{ $c->account_email }} to the active account? This runs in the background and the old Drive is left untouched.">@csrf<button class="pbtn pbtn--ghost pbtn--sm" type="submit">Move its files to the active account</button></form>
                                @endif
                            @endif
                            <form method="post" action="{{ route('admin.integrations.drive.remove', $c) }}" data-confirm="Remove the connection to {{ $c->account_email }}? Your files in that Drive are not deleted.">@csrf @method('DELETE')<button class="pbtn pbtn--ghost pbtn--sm" type="submit"><x-icon name="trash" /> Remove</button></form>
                        </div>
                    </div>
                    @if ($c->quota_used !== null)
                        <div class="int-bar" role="img" aria-label="Storage used"><i style="width:{{ $pct ?? 8 }}%"></i></div>
                        <small>{{ $bytes($c->quota_used) }} used of {{ $c->quota_total ? $bytes($c->quota_total) : 'unlimited' }} in this Google account (all Google services together)</small>
                    @endif
                    @if ($c->last_error)<p class="int-warn" style="margin:.6rem 0 0">{{ $c->last_error }}</p>@endif
                </div>
            @endforeach

            @if ($configured && $connections->count())
                <a class="pbtn pbtn--ghost" href="{{ route('admin.integrations.google.connect') }}"><x-icon name="plus" /> Connect another Google account</a>
            @endif
        </div>
    </section>

    @if ($connections->count())
        <section class="pcard" style="margin-top:1rem">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="refresh" /> Copy status</h2></div>
            <div class="pcard__body">
                <div class="int-stats">
                    <div><b>{{ $stats['total'] }}</b><span>files in the portal</span></div>
                    <div><b>{{ $stats['in_drive'] }}</b><span>safe in Google Drive</span></div>
                    <div><b>{{ $stats['waiting'] }}</b><span>waiting to be copied</span></div>
                    <div><b>{{ $stats['failed'] }}</b><span>failed (will not retry by themselves)</span></div>
                    <div><b>{{ $stats['drive_only'] }}</b><span>only in Drive (removed from server)</span></div>
                    <div><b>{{ $bytes($stats['local_bytes']) }}</b><span>kept on the server</span></div>
                </div>
                <div class="int-conn__act" style="margin-top:1rem">
                    <form method="post" action="{{ route('admin.integrations.drive.sync') }}" data-loading>@csrf<button class="pbtn pbtn--primary" type="submit"><x-icon name="refresh" /> Copy now</button></form>
                    @if ($stats['failed'])
                        <form method="post" action="{{ route('admin.integrations.drive.retry') }}" data-loading>@csrf<button class="pbtn pbtn--ghost" type="submit">Retry failed files</button></form>
                    @endif
                </div>
                <p class="muted" style="font-size:12px;margin-top:.8rem">The copy runs by itself every minute (the server's scheduled task must be running). Files are never deleted from the server until their Drive copy has been checked.</p>
            </div>
        </section>
    @endif

    <style>
        .int-warn { padding: .7rem 1rem; border-radius: 10px; background: rgba(255, 214, 10, .2); margin: 1rem 0; }
        .int-hint { opacity: .8; margin: 1rem 0; }
        .int-conn { margin: 1rem 0; padding: 1rem 1.1rem; border: 1px solid rgba(0, 0, 0, .12); border-radius: 14px; }
        .int-conn.is-active { border-color: var(--accent, #FFD60A); background: rgba(255, 214, 10, .08); }
        .int-conn__top { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .int-conn__top small { display: block; opacity: .65; font-size: 12px; margin-top: .2rem; }
        .int-conn__act { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        .int-conn small { opacity: .7; }
        .int-bar { height: 8px; border-radius: 8px; background: rgba(0, 0, 0, .1); overflow: hidden; margin: .8rem 0 .3rem; }
        .int-bar i { display: block; height: 100%; background: var(--accent, #FFD60A); }
        .int-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .int-stats b { display: block; font-size: 1.5rem; } .int-stats span { font-size: 12px; opacity: .7; }
    </style>
@endsection
