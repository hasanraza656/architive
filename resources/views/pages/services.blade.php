@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $core = Content::coreServices();
@endphp

@include('partials.svc-head', [
    'eyebrow' => 'Services',
    'title' => 'One Team for Drawings, Models <em>and Visuals.</em>',
    'lead' => 'Three connected services—architectural visualization, BIM and Revit, and CAD drafting—delivered as one coordinated production team.',
    'cta' => 'Start your project', 'ctaUrl' => pu('contact'),
])

{{-- The three services only: big visuals, "Explore more" and "Start a project" --}}
<section class="section section--flush-top" aria-label="Our three services">
    <div class="svc-show-list">
        @foreach ($core as $key => $s)
            @include('partials.service-show', ['s' => $s, 'key' => $key, 'n' => $loop->iteration])
        @endforeach
    </div>
</section>

@include('partials.world-map')

@endsection
