@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/blog.css') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ config('site.name') }} Blog" href="{{ route('blog.feed') }}">
    @if ($post->keywords)<meta name="keywords" content="{{ $post->keywords }}">@endif
    @if ($post->author)<meta name="author" content="{{ $post->author->name }}">@endif
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    @if ($post->category)<meta property="article:section" content="{{ $post->category->name }}">@endif
    @foreach ($post->tags as $t)<meta property="article:tag" content="{{ $t->name }}">@endforeach
    @php
        $orgLogo = ['@type' => 'ImageObject', 'url' => asset('assets/img/icon-512.png'), 'width' => 512, 'height' => 512];
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $post->absoluteUrl() . '#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $post->absoluteUrl()],
            'headline' => \Illuminate\Support\Str::limit($post->title, 110, ''),
            'description' => $post->seoDescription(),
            'inLanguage' => 'en',
            'wordCount' => $words,
            'timeRequired' => 'PT' . $post->reading_minutes . 'M',
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at->toAtomString(),
            'author' => $post->author
                ? array_filter(['@type' => 'Person', 'name' => $post->author->name, 'jobTitle' => $post->author->job_title, 'description' => $post->author->bio, 'image' => $post->author->avatarUrl()])
                : ['@type' => 'Organization', 'name' => config('site.name')],
            'publisher' => ['@type' => 'Organization', 'name' => config('site.name'), 'url' => url('/') . '/', 'logo' => $orgLogo],
        ];
        if ($post->imageUrl()) { $ld['image'] = [['@type' => 'ImageObject', 'url' => $post->imageUrl(), 'width' => $imageSize[0], 'height' => $imageSize[1]]]; }
        if ($post->keywords) { $ld['keywords'] = $post->keywords; }
        if ($post->category) { $ld['articleSection'] = $post->category->name; }
    @endphp
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
@php
    $shareUrl = rawurlencode($post->absoluteUrl());
    $shareTitle = rawurlencode($post->title);
    $author = $post->author;
    $updated = $post->updated_at->gt($post->published_at ?? $post->updated_at) && $post->updated_at->diffInDays($post->published_at ?? $post->updated_at) >= 1;
@endphp

<div class="read-progress" aria-hidden="true"><span data-read-progress></span></div>

@if ($preview)
    <div class="preview-bar" role="status"><x-icon name="eye" /> <b>Preview.</b> This article is not published yet. Only signed-in admins can see it.
        <a href="{{ route('admin.blog.posts.edit', $post) }}">Back to editor</a></div>
@endif

<article class="post" itemscope itemtype="https://schema.org/BlogPosting">
    <header class="post-head">
        <div class="wrap wrap--narrow">
            @include('partials.breadcrumbs')
            @if ($post->category)<a class="post-cat" href="{{ $post->category->url() }}">{{ $post->category->name }}</a>@endif
            <h1 class="post-title" itemprop="headline">{{ $post->title }}</h1>
            @if ($post->excerpt)<p class="post-lead">{{ $post->excerpt }}</p>@endif
            <div class="post-meta">
                <span class="bavatar" aria-hidden="true">@if ($author?->avatarUrl())<img src="{{ $author->avatarUrl() }}" alt="" width="44" height="44">@else{{ $author?->initials ?? 'A' }}@endif</span>
                <div class="post-meta__who">
                    <b itemprop="author">{{ $author?->name ?? config('site.name') }}</b>
                    <span>@if ($author?->job_title){{ $author->job_title }}@else{{ config('site.name') }}@endif</span>
                </div>
                <div class="post-meta__info">
                    <time datetime="{{ $post->published_at?->toIso8601String() }}" itemprop="datePublished">{{ $post->published_at?->format('F j, Y') ?? 'Draft' }}</time>
                    <span aria-hidden="true">·</span>
                    <span>{{ $post->reading_minutes }} min read</span>
                    @if ($updated)<span aria-hidden="true">·</span><span>Updated {{ $post->updated_at->format('M j, Y') }}</span>@endif
                </div>
            </div>
        </div>
    </header>

    @if ($post->imageUrl())
        <figure class="post-hero">
            <div class="wrap">
                <img src="{{ $post->imageUrl() }}" alt="{{ $post->featured_image_alt ?: $post->title }}" width="{{ $imageSize[0] }}" height="{{ $imageSize[1] }}" fetchpriority="high" decoding="async" itemprop="image">
                @if ($post->image_credit)<figcaption>{{ $post->image_credit }}</figcaption>@endif
            </div>
        </figure>
    @endif

    <div class="wrap post-layout">
        <aside class="post-share" aria-label="Share this article">
            <span>Share</span>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn" data-no-modal><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45z"/></svg></a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X" data-no-modal><svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.2 8.2L23 22h-6.6l-5.2-6.8L5.2 22H2l7.7-8.8L1.5 2h6.8l4.7 6.2L18.9 2zm-1.2 18h1.8L7.4 3.9H5.5L17.7 20z"/></svg></a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook" data-no-modal><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8.2h2.8l.4-3.3h-3.2V8.4c0-.95.27-1.6 1.63-1.6h1.7V3.85A22.5 22.5 0 0 0 14.35 3.7c-2.5 0-4.2 1.5-4.2 4.3v2.5H7.3v3.3h2.85V22h3.35z"/></svg></a>
            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp" data-no-modal><svg viewBox="0 0 32 32" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.1.55 4.2 1.6 6L4 29l8.2-1.6A12 12 0 0 0 16 27c6.6 0 12-5.4 12-12S22.6 3 16 3zm0 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.7.7.7-3.6-.2-.4A9.8 9.8 0 1 1 16 24.8zm5.4-7.3c-.3-.15-1.75-.86-2-.96-.28-.1-.47-.15-.66.15s-.76.96-.93 1.15-.34.22-.64.07c-.3-.15-1.24-.46-2.37-1.46-.88-.78-1.47-1.74-1.64-2.04-.17-.3 0-.45.13-.6.13-.13.3-.34.44-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.66-1.6-.9-2.18-.24-.58-.48-.5-.66-.5h-.56c-.2 0-.52.07-.8.37s-1.03 1-1.03 2.45 1.06 2.85 1.2 3.05c.15.2 2.08 3.17 5.03 4.45.7.3 1.25.48 1.68.62.7.22 1.35.2 1.86.12.57-.08 1.74-.7 1.98-1.4.25-.68.25-1.28.17-1.4-.07-.12-.27-.2-.57-.35z"/></svg></a>
            <button type="button" aria-label="Copy link" data-copy-link="{{ $post->absoluteUrl() }}"><x-icon name="copy" /><span class="visually-hidden" data-copy-state>Copy link</span></button>
        </aside>

        <div class="post-main">
            @if (count($toc) >= 3)
                <details class="post-toc post-toc--inline" open>
                    <summary>In this article</summary>
                    <ol>@foreach ($toc as $t)<li class="lvl-{{ $t['level'] }}"><a href="#{{ $t['id'] }}">{{ $t['text'] }}</a></li>@endforeach</ol>
                </details>
            @endif

            <div class="prose" itemprop="articleBody">{!! $body !!}</div>

            @if ($post->tags->count())
                <div class="post-tags" aria-label="Tags">
                    @foreach ($post->tags as $t)<a href="{{ $t->url() }}">#{{ $t->name }}</a>@endforeach
                </div>
            @endif

            <div class="post-share post-share--bottom" aria-label="Share this article">
                <b>Found this useful? Share it.</b>
                <div>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" data-no-modal>LinkedIn</a>
                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" data-no-modal>X</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" data-no-modal>Facebook</a>
                    <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" data-no-modal>WhatsApp</a>
                    <button type="button" data-copy-link="{{ $post->absoluteUrl() }}"><span data-copy-state>Copy link</span></button>
                </div>
            </div>

            {{-- author --}}
            <section class="author-box" aria-labelledby="authorTitle">
                <span class="author-box__pic">@if ($author?->avatarUrl())<img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" width="96" height="96" loading="lazy">@else<span>{{ $author?->initials ?? 'A' }}</span>@endif</span>
                <div class="author-box__body">
                    <p class="author-box__kicker" id="authorTitle">Written by</p>
                    <h2>{{ $author?->name ?? config('site.name') }}</h2>
                    @if ($author?->job_title)<p class="author-box__role">{{ $author->job_title }} · {{ config('site.name') }}</p>@endif
                    <p class="author-box__bio">{{ $author?->bio ?: 'Architive is an architectural production studio providing architectural visualization, BIM and Revit, and CAD drafting for architecture firms, interior studios, developers and homeowners worldwide.' }}</p>
                    <a class="author-box__link" href="{{ pu('contact') }}">Talk to our team <x-icon name="arrow-right" /></a>
                </div>
            </section>

            @if ($prev || $next)
                <nav class="post-nav" aria-label="More articles">
                    @if ($prev)<a class="post-nav__prev" href="{{ $prev->url() }}" rel="prev"><small>&larr; Previous</small><span>{{ $prev->title }}</span></a>@else<span></span>@endif
                    @if ($next)<a class="post-nav__next" href="{{ $next->url() }}" rel="next"><small>Next &rarr;</small><span>{{ $next->title }}</span></a>@endif
                </nav>
            @endif
        </div>

        @if (count($toc) >= 3)
            <aside class="post-toc post-toc--side" aria-label="Table of contents">
                <p>On this page</p>
                <ol data-toc>@foreach ($toc as $t)<li class="lvl-{{ $t['level'] }}"><a href="#{{ $t['id'] }}">{{ $t['text'] }}</a></li>@endforeach</ol>
            </aside>
        @endif
    </div>
</article>

@if ($related->count())
    <section class="section section--alt related" aria-labelledby="relatedTitle">
        <div class="wrap wrap--wide">
            <p class="eyebrow">Keep reading</p>
            <h2 class="display-h display-h--sm" id="relatedTitle">More from the <em>Journal.</em></h2>
            <div class="blog-grid">
                @foreach ($related as $p)@include('partials.blog-card', ['post' => $p])@endforeach
            </div>
        </div>
    </section>
@endif

@include('partials.cta-band', ['title' => 'Need Help With <em>Your Own Project?</em>', 'text' => 'Share the files, deadline and outcome you need. We will review them and recommend a practical scope during a free consultation.'])

<script src="{{ asset_v('assets/js/blog.js') }}" defer></script>
@endsection
