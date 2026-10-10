<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Resolve SEO metadata for every page: config/seo.php (by route name),
        // optionally overridden by a `$seo` array passed from the controller/view.
        View::composer('layouts.app', function ($view) {
            $name     = Route::currentRouteName();
            // NB: route names contain dots, so index the array directly (config() would treat them as nesting).
            $page     = (config("seo.pages") ?? [])[$name] ?? [];
            $override = $view->getData()['seo'] ?? [];
            $seo      = array_merge([
                'title'       => config('site.name') . ' | ' . config('site.tagline'),
                'description' => '',
                'image'       => config('seo.default_image'),
                'robots'      => 'index,follow,max-image-preview:large,max-snippet:-1',
                'type'        => 'website',
            ], $page, $override);

            $view->with('seoMeta', $seo);
            $view->with('crumbs', $this->breadcrumbs($name, is_array($view->getData()['c'] ?? null) ? $view->getData()['c'] : null, $view->getData()));
        });
    }

    /** Visible + structured breadcrumb trail derived from the route name. */
    private function breadcrumbs(?string $name, ?array $collab, array $data = []): array
    {
        if (! $name || $name === 'home') {
            return [];
        }

        $labels = [
            'about' => 'Who We Are', 'team' => 'Team', 'services.index' => 'Services',
            'services.visualization' => 'Architectural Visualization', 'services.bim' => 'BIM and Revit',
            'services.cad' => 'CAD Drafting', 'services.outsourcing' => 'Production Support',
            'collaborations.index' => 'Collaborations', 'process' => 'How It Works', 'faqs' => 'FAQs',
            'blog.index' => 'Blog', 'contact' => 'Contact', 'privacy' => 'Privacy Policy', 'terms' => 'Terms of Service', 'sitemap.html' => 'Sitemap',
        ];

        $trail = [['label' => 'Home', 'url' => abs_pu('home')]];

        if (str_starts_with($name, 'services.') && $name !== 'services.index') {
            $trail[] = ['label' => 'Services', 'url' => abs_pu('services.index')];
        }
        if ($name === 'collaborations.show' && $collab) {
            $trail[] = ['label' => 'Collaborations', 'url' => abs_pu('collaborations.index')];
            $trail[] = ['label' => $collab['name'], 'url' => abs_pu($name, ['slug' => request()->route('slug')])];

            return $trail;
        }
        if (in_array($name, ['blog.category', 'blog.tag', 'blog.show'], true)) {
            $trail[] = ['label' => 'Blog', 'url' => abs_pu('blog.index')];
            if ($name === 'blog.category' && ! empty($data['category'])) {
                $trail[] = ['label' => $data['category']->name, 'url' => $data['category']->absoluteUrl()];
            } elseif ($name === 'blog.tag' && ! empty($data['tag'])) {
                $trail[] = ['label' => $data['tag']->name, 'url' => $data['tag']->absoluteUrl()];
            } elseif ($name === 'blog.show' && ! empty($data['post'])) {
                if ($data['post']->category) {
                    $trail[] = ['label' => $data['post']->category->name, 'url' => $data['post']->category->absoluteUrl()];
                }
                $trail[] = ['label' => $data['post']->title, 'url' => $data['post']->absoluteUrl()];
            }

            return $trail;
        }
        if (isset($labels[$name])) {
            $trail[] = ['label' => $labels[$name], 'url' => abs_pu($name)];
        }

        return $trail;
    }
}
