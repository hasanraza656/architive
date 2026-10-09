<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // /admin/* goes to the admin sign-in, everything else (customer area, shared portal pages) to the customer sign-in
        return $request->is('admin', 'admin/*') ? route('admin.login') : route('customer.login');
    }
}
