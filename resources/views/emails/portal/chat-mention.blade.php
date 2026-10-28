@extends('emails.portal.layout')
@section('title', $kind === 'added' ? 'You were added to a conversation' : 'You were mentioned')
@section('preheader', $kind === 'added' ? $by->name . ' added you to ' . $order->number : $by->name . ' mentioned you: ' . $excerpt)
@section('kicker', $kind === 'added' ? 'Conversation' : 'Mention')
@section('heading', $order->number . ' · ' . $order->title)
@section('content')
    <p style="margin:0 0 14px;">Hi {{ $user->first_name }},</p>
    @if ($kind === 'added')
        <p style="margin:0 0 14px;">{{ $by->name }} added you to the conversation with {{ $order->customer->name }}. You can read it and reply from the portal.</p>
    @else
        <p style="margin:0 0 14px;">{{ $by->name }} mentioned you:</p>
        <div style="margin:0 0 14px;padding:12px 14px;background:#fafaf7;border-left:4px solid #FFD60A;border-radius:8px;font-size:14px;color:#2b2b28;">{{ $excerpt }}</div>
    @endif
@endsection
@section('button', 'Open the conversation')
@section('button_url', $link)
