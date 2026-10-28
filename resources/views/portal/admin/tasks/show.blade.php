@extends('portal.layouts.app')
@section('title', $task->number() . ' · ' . $task->title)
@section('heading', 'Task')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-tasks.css') }}">
@endpush

@section('content')
    <a class="crumb" href="{{ route('admin.projects.show', $task->project) }}"><x-icon name="arrow-left" /> {{ $task->project->name }}</a>
    <div class="pcard tpage">
        @include('portal.tasks._panel', ['mode' => 'page'])
    </div>
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/portal-tasks.js') }}"></script>
@endpush
