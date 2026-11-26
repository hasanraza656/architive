<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'mcp',
        'mcp/*',                // called by AI assistants with an access key, never from a browser session
        'ads/whatsapp',         // sendBeacon from the page (no token); same-site check + one-time ids inside the controller
        'webhooks/stripe',     // called by Stripe's servers; verified with the webhook signature instead
    ];
}
