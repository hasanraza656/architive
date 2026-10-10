{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ config('site.name') }} Blog</title>
        <link>{{ abs_pu('blog.index') }}</link>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml"/>
        <description>Practical guides on architectural visualization, BIM and Revit, and CAD drafting from the Architive studio.</description>
        <language>en-us</language>
        <lastBuildDate>{{ ($posts->first()?->updated_at ?? now())->toRssString() }}</lastBuildDate>
@foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $post->absoluteUrl() }}</link>
            <guid isPermaLink="true">{{ $post->absoluteUrl() }}</guid>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            @if ($post->author)<dc:creator>{{ $post->author->name }}</dc:creator>@endif
            @if ($post->category)<category>{{ $post->category->name }}</category>@endif
            <description>{{ $post->summary(300) }}</description>
            <content:encoded><![CDATA[@if ($post->imageUrl())<p><img src="{{ $post->imageUrl() }}" alt="{{ e($post->featured_image_alt ?: $post->title) }}"></p>@endif{!! str_replace(']]>', ']]]]><![CDATA[>', $post->content) !!}]]></content:encoded>
        </item>
@endforeach
    </channel>
</rss>
