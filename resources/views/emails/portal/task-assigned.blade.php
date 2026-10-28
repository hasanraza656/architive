@extends('emails.portal.layout')
@section('title', 'Task assigned to you')
@section('preheader', $by->name . ' assigned you: ' . $task->title)
@section('kicker', 'Task')
@section('heading', $task->title)
@section('content')
    <p style="margin:0 0 14px;">Hi {{ $user->first_name }}, {{ $by->name }} assigned this task to you.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;font-size:14px;color:#2b2b28;">
        <tr><td style="padding:3px 18px 3px 0;color:#8a8a83;">Project</td><td>{{ $task->project->name }}</td></tr>
        <tr><td style="padding:3px 18px 3px 0;color:#8a8a83;">Status</td><td>{{ $task->statusLabel() }}</td></tr>
        <tr><td style="padding:3px 18px 3px 0;color:#8a8a83;">Priority</td><td>{{ $task->priorityLabel() }}</td></tr>
        <tr><td style="padding:3px 18px 3px 0;color:#8a8a83;">Due</td><td>{{ $task->due_date ? $task->due_date->format('M j, Y') : 'No due date' }}</td></tr>
    </table>
    @if ($task->description)<p style="margin:0 0 12px;color:#55554f;font-size:14px;">{{ \Illuminate\Support\Str::limit($task->description, 240) }}</p>@endif
@endsection
@section('button', 'Open the task')
@section('button_url', route('admin.tasks.show', $task))
