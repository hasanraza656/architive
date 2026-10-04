<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('pu')) {
    /**
     * Page URL with the canonical trailing slash (client URL structure: /about-us/).
     * Usage: pu('contact', [], ['service' => 'bim'])  ->  https://site/contact/?service=bim
     */
    function pu(string $name, array $params = [], array $query = [], bool $absolute = false): string
    {
        $url = route($name, $params, $absolute);
        $url = ($url === '' ? '/' : rtrim($url, '/') . '/');

        return $query ? $url . '?' . http_build_query($query) : $url;
    }
}

if (! function_exists('abs_pu')) {
    /** Absolute canonical URL for a named page. */
    function abs_pu(string $name, array $params = []): string
    {
        return pu($name, $params, [], true);
    }
}

if (! function_exists('asset_v')) {
    /** Asset URL with a cache-busting mtime query string. */
    function asset_v(string $path): string
    {
        $file = public_path($path);
        $v = is_file($file) ? filemtime($file) : time();

        return asset($path) . '?v=' . $v;
    }
}

if (! function_exists('is_page')) {
    /** True when the current route matches one of the given names (wildcards allowed). */
    function is_page(string ...$names): bool
    {
        return Route::currentRouteName() !== null && Route::is(...$names);
    }
}
