@extends('emails.portal.layout')
@section('title', $invite ? 'Welcome to the team' : 'Reset your password')
@section('preheader', $invite ? 'Set your password to start using the Architive portal.' : 'Use this link to choose a new password.')
@section('kicker', $invite ? 'Team' : 'Password')
@section('heading', $invite ? 'Welcome to the Architive team' : 'Choose a new password')
@section('content')
    <p style="margin:0 0 14px;">Hi {{ $user->first_name }},</p>
    @if ($invite)
        <p style="margin:0 0 14px;">{{ $inviter ? $inviter->name . ' has' : 'You have been' }} added you to the Architive portal{{ $user->teamRole ? ' as ' . $user->teamRole->name : '' }}. Click the button to choose your password and sign in.</p>
    @else
        <p style="margin:0 0 14px;">We received a request to reset the password of your Architive team account. Click the button to choose a new one.</p>
    @endif
    <p style="margin:0 0 12px;color:#55554f;font-size:14px;">This link works for 7 days and can be used once. If you did not expect this e-mail you can ignore it.</p>
@endsection
@section('button', $invite ? 'Set my password' : 'Choose a new password')
@section('button_url', $link)
