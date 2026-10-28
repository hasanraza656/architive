<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // The super admin (users.role = admin) can do everything.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // Every "module.action" key from config/permissions.php becomes a gate: can('orders.view'), @can('blog.edit'), ->middleware('can:...')
        foreach (Permissions::keys() as $key) {
            Gate::define($key, fn (User $user) => $user->hasPermission($key));
        }
    }
}
