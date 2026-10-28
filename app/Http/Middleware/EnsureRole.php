<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:team') (super admin or team member), 'role:admin' (super admin only) or 'role:customer'.
 * A signed-in person with the wrong role is sent to their own home instead of seeing an error.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $teamSide = in_array($role, ['admin', 'team'], true);

        if (! $user || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($teamSide ? 'admin.login' : 'customer.login');
        }

        $allowed = match ($role) {
            'team' => $user->isTeam(),
            default => $user->role === $role,
        };

        if (! $allowed) {
            return $request->expectsJson() ? abort(403) : redirect($user->homeRoute());
        }

        return $next($request);
    }
}
