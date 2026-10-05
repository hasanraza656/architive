{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
        <lastmod>{{ $u['lastmod'] }}</lastmod>
        <changefreq>{{ $u['changefreq'] }}</changefreq>
        <priority>{{ number_format($u['priority'], 1) }}</priority>
@foreach ($u['images'] ?? [] as $im)
        <image:image>
            <image:loc>{{ $im['loc'] }}</image:loc>
            <image:title>{{ $im['title'] }}</image:title>
            <image:caption>{{ $im['caption'] }}</image:caption>
        </image:image>
@endforeach
    </url>
@endforeach
</urlset>
