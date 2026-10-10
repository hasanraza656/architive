@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/blog.css') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ config('site.name') }} Blog" href="{{ route('blog.feed') }}">
    @php
        $ld = ['@context' => 'https://schema.org', '@type' => 'Blog', 'name' => config('site.name') . ' Blog', 'url' => abs_pu('blog.index'), 'inLanguage' => 'en',
            'publisher' => ['@type' => 'Organization', 'name' => config('site.name'), 'url' => url('/') . '/'],
            'blogPost' => $posts->map(fn ($p) => ['@type' => 'BlogPosting', 'headline' => $p->title, 'url' => $p->absoluteUrl(), 'datePublished' => $p->published_at->toAtomString(), 'image' => $p->imageUrl()])->values()->all()];
    @endphp
    @if (! $q)<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>@endif
@endpush

@section('content')
@php
    $heading = $category ? $category->name : ($tag ? '#' . $tag->name : ($q !== '' ? 'Search results' : null));
@endphp

<section class="blog-head">
    <div class="wrap wrap--wide">
        @include('partials.breadcrumbs')
        <div class="blog-head__grid">
            <div>
                <p class="eyebrow" data-reveal>The Architive Journal</p>
                @if ($heading)
                    <h1 class="display-h" data-reveal>{{ $heading }}@if ($q !== '') <em>“{{ $q }}”</em>@endif</h1>
                    <p class="lead-p" data-reveal>
                        @if ($category && $category->description){{ $category->description }}
                        @elseif ($tag)Everything we have written about {{ $tag->name }}.
                        @elseif ($q !== ''){{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }} found.
                        @endif
                    </p>
                @else
                    <h1 class="display-h" data-split>Ideas for Teams Who <em>Draw, Model and Deliver.</em></h1>
                    <p class="lead-p" data-reveal>Practical guides on architectural visualization, BIM and Revit, and CAD drafting: what things cost, how to brief a team and how to keep drawings coordinated.</p>
                @endif
            </div>
            <form class="blog-search" method="get" action="{{ pu('blog.index') }}" role="search" data-reveal>
                <label class="visually-hidden" for="blogQ">Search articles</label>
                <x-icon name="search" />
                <input id="blogQ" type="search" name="q" value="{{ $q }}" placeholder="Search articles" autocomplete="off">
                <button type="submit" class="btn-ay btn-ay--sm">Search</button>
            </form>
        </div>

        @if ($categories->count())
            <nav class="blog-cats" aria-label="Blog categories" data-reveal>
                <a class="blog-cat {{ ! $category && ! $tag && $q === '' ? 'is-on' : '' }}" href="{{ pu('blog.index') }}">All</a>
                @foreach ($categories as $cat)
                    <a class="blog-cat {{ $category && $category->id === $cat->id ? 'is-on' : '' }}" href="{{ $cat->url() }}">{{ $cat->name }} <small>{{ $cat->posts_count }}</small></a>
                @endforeach
            </nav>
        @endif
    </div>
</section>

<section class="section blog-list">
    <div class="wrap wrap--wide">
        @if ($featured)
            @include('partials.blog-card', ['post' => $featured, 'big' => true])
        @endif

        @if ($posts->count())
            <div class="blog-grid">
                @foreach ($posts as $p)
                    @include('partials.blog-card', ['post' => $p])
                @endforeach
            </div>
            @if ($posts->hasPages())
                <nav class="blog-pager" aria-label="Pagination">
                    @if ($posts->onFirstPage())<span class="is-off">&larr; Newer</span>@else<a href="{{ $posts->previousPageUrl() }}" rel="prev">&larr; Newer</a>@endif
                    <span class="blog-pager__now">Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
                    @if ($posts->hasMorePages())<a href="{{ $posts->nextPageUrl() }}" rel="next">Older &rarr;</a>@else<span class="is-off">Older &rarr;</span>@endif
                </nav>
            @endif
        @elseif (! $featured)
            <div class="blog-empty">
                <x-icon name="file-text" />
                <h2>{{ $q !== '' ? 'Nothing matched your search' : 'New articles are on the way' }}</h2>
                <p>{{ $q !== '' ? 'Try a different word, or browse all articles.' : 'We are writing the first guides now. Check back soon.' }}</p>
                <a class="btn-ay btn-ay--sm" href="{{ pu('blog.index') }}">All articles</a>
            </div>
        @endif

        @if ($popularTags->count() && $q === '')
            <div class="blog-tags" data-reveal>
                <span>Popular topics</span>
                @foreach ($popularTags as $t)<a href="{{ $t->url() }}" class="{{ $tag && $tag->id === $t->id ? 'is-on' : '' }}">#{{ $t->name }}</a>@endforeach
            </div>
        @endif
    </div>
</section>

@include('partials.cta-band', ['title' => 'Have a Project in Mind? <em>Let Us Help.</em>', 'text' => 'Share your files, deadline and the outcome you need. We reply with a practical next step, usually within one business day.'])
@endsection
