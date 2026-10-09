<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin') or 'role:customer'.
 * A signed-in person with the wrong role is sent to their own home instead of seeing an error.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($role === 'admin' ? 'admin.login' : 'customer.login');
        }

        if ($user->role !== $role) {
            return $request->expectsJson() ? abort(403) : redirect($user->homeRoute());
        }

        return $next($request);
    }
}
