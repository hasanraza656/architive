<?php

namespace App\Http\Controllers;

use App\Support\Content;
use App\Support\Portfolio;

class SeoController extends Controller
{
    public function sitemap()
    {
        $urls = [];
        foreach (config('seo.pages') as $name => $meta) {
            if (! isset($meta['sitemap'])) {
                continue;
            }
            $urls[] = [
                'loc'        => abs_pu($name),
                'priority'   => $meta['sitemap'][0],
                'changefreq' => $meta['sitemap'][1],
                'lastmod'    => $this->lastmod($name),
                'images'     => $name === 'services.visualization'
                    ? array_map(fn ($i) => ['loc' => $i['src'], 'title' => $i['title'], 'caption' => $i['alt']], Portfolio::viz())
                    : [],
            ];
        }
        foreach (array_keys(Content::collaborations()) as $slug) {
            $urls[] = [
                'loc'        => abs_pu('collaborations.show', ['slug' => $slug]),
                'priority'   => 0.7,
                'changefreq' => 'monthly',
                'lastmod'    => $this->lastmod('collaboration'),
                'images'     => ($m = Portfolio::caseMedia($slug)) ? [['loc' => $m['cover']['src'], 'title' => $m['caption'], 'caption' => $m['alt']]] : [],
            ];
        }

        return response()->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /contact/send\nDisallow: /admin\nDisallow: /account\nDisallow: /portal\nDisallow: /webhooks\n\nSitemap: " . url('sitemap.xml') . "\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function lastmod(string $name): string
    {
        $map = [
            'services.index' => 'services', 'services.visualization' => 'visualization', 'services.bim' => 'bim',
            'services.cad' => 'cad', 'services.outsourcing' => 'outsourcing', 'collaborations.index' => 'collaborations',
            'sitemap.html' => 'sitemap',
        ];
        $view = $map[$name] ?? $name;
        $file = resource_path("views/pages/{$view}.blade.php");

        return date('c', is_file($file) ? filemtime($file) : time());
    }
}
