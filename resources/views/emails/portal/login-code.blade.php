@extends('emails.portal.layout')
@section('title', 'Your sign-in code')
@section('preheader', $code . ' is your Architive sign-in code.')
@section('kicker', 'Sign in')
@section('heading', 'Your one-time code')
@section('content')
    <p style="margin:0 0 14px;">Hi {{ $user?->profile_completed_at ? $user->first_name : 'there' }}, use this code to sign in to your Architive client area:</p>
    <div style="margin:0 0 16px;padding:18px;text-align:center;background:#fafaf7;border:1px solid #e6e6e0;border-radius:12px;font-size:34px;letter-spacing:10px;font-weight:bold;font-family:'Courier New',monospace;">{{ $code }}</div>
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">The code works for {{ $minutes }} minutes. If you did not try to sign in, you can safely ignore this e-mail.</p>
@endsection
