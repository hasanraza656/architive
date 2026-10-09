<?php

namespace Tests\Feature\Portal;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared helpers for the portal tests: they run on an in-memory SQLite database, never on the real one. */
abstract class PortalTestCase extends TestCase
{
    use RefreshDatabase;

    protected function admin(array $attrs = []): User
    {
        return User::create($attrs + [
            'role' => User::ROLE_ADMIN, 'first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.test',
            'password' => bcrypt('Secret-pass-1'), 'is_active' => true,
        ]);
    }

    protected function customer(string $email = 'client@example.test', array $attrs = []): User
    {
        return User::create($attrs + ['role' => User::ROLE_CUSTOMER, 'first_name' => 'Cleo', 'last_name' => 'Client', 'email' => $email, 'is_active' => true]);
    }

    /** A draft order for $customer with two lines: 450.00 + 2.5 x 120.00 = 750.00, minus 50, plus 10% tax = 770.00 */
    protected function order(User $customer, User $admin, array $extra = []): Order
    {
        return app(OrderService::class)->create($extra + [
            'customer_id' => $customer->id,
            'title' => 'Riverside House',
            'notes' => 'Two revision rounds.',
            'discount' => 50,
            'tax_rate' => 10,
            'items' => [
                ['description' => 'Exterior render', 'quantity' => 1, 'unit_price' => 450],
                ['description' => 'Revit model', 'quantity' => 2.5, 'unit_price' => 120],
            ],
        ], $admin);
    }
}
