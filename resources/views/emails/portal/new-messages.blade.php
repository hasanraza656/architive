@extends('emails.portal.layout')
@section('title', 'New message')
@section('preheader', $messages->last()->user->name . ': ' . \Illuminate\Support\Str::limit($messages->last()->body ?: 'Sent a file', 80))
@section('kicker', $messages->count() > 1 ? $messages->count() . ' new messages' : 'New message')
@section('heading', ($toAdmin ? $messages->last()->user->name : 'The Architive team') . ' wrote to you about ' . $order->number)
@section('content')
    @foreach ($messages->take(-5) as $m)
        @php
            $text = $m->body ? \Illuminate\Support\Str::limit($m->body, 400) : '';
            $att = $m->files->count() ? ($text ? "\n" : '') . '📎 ' . $m->files->count() . ' ' . \Illuminate\Support\Str::plural('attachment', $m->files->count()) : '';
        @endphp
        <div style="margin:0 0 10px;padding:12px 14px;background:#fafaf7;border:1px solid #e6e6e0;border-radius:10px;font-size:14px;white-space:pre-line;">{{ $text . $att }}</div>
    @endforeach
    @if ($messages->count() > 5)<p style="margin:0 0 10px;color:#8a8a83;font-size:13px;">…and {{ $messages->count() - 5 }} earlier {{ \Illuminate\Support\Str::plural('message', $messages->count() - 5) }}.</p>@endif
@endsection
@section('button', 'Reply')
@section('button_url', ($toAdmin ? $order->adminUrl() : $order->customerUrl()) . '#chat')
@section('footnote')
    <p style="margin:0 0 10px;">To keep your inbox quiet we send at most one message e-mail every {{ config('portal.chat.email_cooldown_minutes') }} minutes per order, and none while you are on the page.</p>
@endsection
