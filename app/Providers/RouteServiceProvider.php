<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // /admin/customers/{customer} must only ever resolve to a customer account
        Route::bind('customer', fn ($value) => \App\Models\User::customers()->findOrFail($value));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Admin + customer portal (kept in its own file; see routes/portal.php)
            Route::middleware('web')
                ->group(base_path('routes/portal.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Permit Revisions wizard: a few submissions a minute and a modest number per hour for each visitor address
        RateLimiter::for('permit', fn (Request $request) => [Limit::perMinute(5)->by('permit-m:' . $request->ip()), Limit::perHour(25)->by('permit-h:' . $request->ip())]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
