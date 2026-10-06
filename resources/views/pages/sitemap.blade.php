@extends('layouts.app')

@section('content')
@php $collabs = \App\Support\Content::collaborations(); @endphp
<section class="legal-hero"><div class="wrap wrap--narrow">
    @include('partials.breadcrumbs')
    <p class="eyebrow">Site map</p>
    <h1 class="display-h">Find Your Way <em>Around.</em></h1>
</div></section>

<section class="section legal">
    <div class="wrap wrap--narrow">
        <div class="row g-4">
            <div class="col-md-6">
                <h2>Company</h2>
                <ul class="sitemap-list">
                    <li><a href="{{ pu('home') }}">Home</a></li>
                    <li><a href="{{ pu('about') }}">About Architive</a></li>
                    <li><a href="{{ pu('process') }}">How It Works</a></li>
                    <li><a href="{{ pu('faqs') }}">FAQs</a></li>
                    <li><a href="{{ pu('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h2>Services</h2>
                <ul class="sitemap-list">
                    <li><a href="{{ pu('services.index') }}">All services</a></li>
                    <li><a href="{{ pu('services.visualization') }}">Architectural Visualization and Rendering</a></li>
                    <li><a href="{{ pu('services.bim') }}">BIM and Revit, Scan to BIM</a></li>
                    <li><a href="{{ pu('services.cad') }}">CAD Drafting and Permit Support</a></li>
                    <li><a href="{{ pu('services.outsourcing') }}">Architectural Production Support</a></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h2>Collaborations</h2>
                <ul class="sitemap-list">
                    <li><a href="{{ pu('collaborations.index') }}">All collaborations</a></li>
                    @foreach ($collabs as $slug => $c)
                        <li><a href="{{ pu('collaborations.show', ['slug' => $slug]) }}">{{ $c['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-md-6">
                <h2>Legal</h2>
                <ul class="sitemap-list">
                    <li><a href="{{ pu('privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ pu('terms') }}">Terms of Service</a></li>
                    <li><a href="{{ url('sitemap.xml') }}">XML sitemap</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
