@extends('portal.layouts.app')
@section('title', 'Blog posts')
@section('heading', 'Blog')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-blog.css') }}">
@endpush

@section('content')
    <div class="phead">
        <div>
            <h1 class="phead__title">Blog posts</h1>
            <p class="phead__sub">Write articles, schedule them and publish when ready. Everything you publish appears on <a class="plink" href="{{ pu('blog.index') }}" target="_blank" rel="noopener">architive.net/blog</a>.</p>
        </div>
        <div class="phead__actions">
            <a class="pbtn pbtn--ghost" href="{{ route('admin.blog.categories.index') }}"><x-icon name="layers" /> Categories</a>
            <a class="pbtn pbtn--primary" href="{{ route('admin.blog.posts.create') }}"><x-icon name="plus" /> New post</a>
        </div>
    </div>

    <div class="toolbar">
        <div class="pills" role="navigation" aria-label="Filter by status">
            @foreach (['' => ['All', 'all'], 'live' => ['Published', 'live'], 'scheduled' => ['Scheduled', 'scheduled'], 'draft' => ['Drafts', 'draft']] as $key => [$label, $c])
                <a class="pill {{ ($stage ?? '') === $key ? 'is-on' : '' }}" href="{{ route('admin.blog.posts.index', array_filter(['stage' => $key, 'category' => $category ?: null, 'q' => $q])) }}">{{ $label }} <small>{{ $counts[$c] }}</small></a>
            @endforeach
        </div>
        <form class="blog-filter" method="get" action="{{ route('admin.blog.posts.index') }}" role="search">
            @if ($stage)<input type="hidden" name="stage" value="{{ $stage }}">@endif
            <select class="pselect" name="category" aria-label="Category" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($category === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <div class="search"><x-icon name="search" /><input class="pinput" type="search" name="q" value="{{ $q }}" placeholder="Search title, address or keyword" aria-label="Search posts"></div>
        </form>
    </div>

    <div class="pcard">
        @forelse ($posts as $p)
            @php $stageNow = $p->stage(); @endphp
            <article class="bpost-row">
                <a class="bpost-row__img" href="{{ route('admin.blog.posts.edit', $p) }}" tabindex="-1" aria-hidden="true">
                    @if ($p->thumbUrl())<img src="{{ $p->thumbUrl() }}" alt="" loading="lazy" width="120" height="80">@else<span><x-icon name="image" /></span>@endif
                </a>
                <div class="bpost-row__main">
                    <a class="bpost-row__title" href="{{ route('admin.blog.posts.edit', $p) }}">{{ $p->title }}</a>
                    <p class="bpost-row__meta">
                        <span class="bstage bstage--{{ $stageNow }}">{{ ['live' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft'][$stageNow] }}</span>
                        @if ($p->is_featured)<span class="bstage bstage--star">Featured</span>@endif
                        @if ($p->category)<span>{{ $p->category->name }}</span>@endif
                        <span>{{ $p->author?->name ?: 'No author' }}</span>
                        <span>{{ $p->reading_minutes }} min read</span>
                        @if ($stageNow === 'live')<span>{{ number_format($p->views) }} views</span>@endif
                    </p>
                    <p class="bpost-row__date">
                        @if ($p->published_at)<time data-dt="datetime" datetime="{{ $p->published_at->toIso8601String() }}">{{ $p->published_at->format('M j, Y H:i') }}</time>@else Not published yet @endif
                        · /blog/{{ $p->slug }}/
                    </p>
                </div>
                <div class="bpost-row__act">
                    <a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.blog.posts.edit', $p) }}"><x-icon name="edit" /> Edit</a>
                    <a class="pbtn pbtn--ghost pbtn--sm" href="{{ $p->url() }}" target="_blank" rel="noopener"><x-icon name="external" /> {{ $stageNow === 'live' ? 'View' : 'Preview' }}</a>
                </div>
            </article>
        @empty
            <div class="empty"><x-icon name="file-text" /><b>{{ $q || $stage || $category ? 'No articles match' : 'No articles yet' }}</b><span>{{ $q || $stage || $category ? 'Try another filter.' : 'Write your first article.' }}</span><a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.blog.posts.create') }}"><x-icon name="plus" /> New post</a></div>
        @endforelse
        @if ($posts->hasPages())<div class="pager">{{ $posts->links() }}</div>@endif
    </div>
@endsection
