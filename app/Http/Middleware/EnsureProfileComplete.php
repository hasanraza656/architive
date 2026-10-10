<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** A brand-new customer (account created by their first sign-in) is asked for their name once, before anything else. */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isCustomer() && ! $user->profile_completed_at && ! $request->routeIs('customer.welcome*', 'customer.logout')) {
            return redirect()->route('customer.welcome');
        }

        return $next($request);
    }
}
