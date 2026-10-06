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
            $view->with('crumbs', $this->breadcrumbs($name, $view->getData()['c'] ?? null));
        });
    }

    /** Visible + structured breadcrumb trail derived from the route name. */
    private function breadcrumbs(?string $name, ?array $collab): array
    {
        if (! $name || $name === 'home') {
            return [];
        }

        $labels = [
            'about' => 'Who We Are', 'team' => 'Team', 'services.index' => 'Services',
            'services.visualization' => 'Architectural Visualization', 'services.bim' => 'BIM and Revit',
            'services.cad' => 'CAD Drafting', 'services.outsourcing' => 'Production Support',
            'collaborations.index' => 'Collaborations', 'process' => 'How It Works', 'faqs' => 'FAQs',
            'contact' => 'Contact', 'privacy' => 'Privacy Policy', 'terms' => 'Terms of Service', 'sitemap.html' => 'Sitemap',
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
        if (isset($labels[$name])) {
            $trail[] = ['label' => $labels[$name], 'url' => abs_pu($name)];
        }

        return $trail;
    }
}
