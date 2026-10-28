<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // The chat in the portal records voice messages and the screen; the public website needs neither.
        $portal = $request->is('admin', 'admin/*', 'account', 'account/*', 'portal/*');
        $response->headers->set('Permissions-Policy', ($portal ? 'camera=(), microphone=(self), display-capture=(self)' : 'camera=(), microphone=(), display-capture=()') . ', geolocation=(), interest-cohort=()');

        if ($request->isSecure()) {
            // Browsers keep using https for a year. No includeSubDomains or preload on purpose: other subdomains are not ours to decide.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
