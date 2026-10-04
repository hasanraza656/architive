<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * One permanent URL per page: /about-us  ->  301  ->  /about-us/
 * (client SEO requirement; also keeps relative links and canonicals consistent).
 */
class EnsureTrailingSlash
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $path = $request->getPathInfo();

            if ($path !== '/' && ! str_ends_with($path, '/') && ! str_contains(basename($path), '.')) {
                $query = $request->getQueryString();

                return redirect()->to(
                    $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $path . '/' . ($query ? '?' . $query : ''),
                    301
                );
            }
        }

        return $next($request);
    }
}
