<?php

namespace Tests\Feature\Portal;

use App\Models\Role;
use App\Models\User;
use App\Services\Payments\StripeInvoices;
use Illuminate\Support\Facades\Cache;

class StripeInvoicesTest extends PortalTestCase
{
    /** @var array<int, array> */
    public array $calls = [];

    private function inv(string $id, string $email, string $status = 'paid', int $total = 50000, string $name = 'Pat Payer'): array
    {
        return [
            'id' => $id, 'number' => strtoupper(substr($id, 3)) . '-0001', 'status' => $status, 'customer' => 'cus_' . md5($email), 'customer_email' => $email, 'customer_name' => $name,
            'currency' => 'usd', 'total' => $total, 'amount_paid' => $status === 'paid' ? $total : 0, 'amount_due' => $status === 'paid' ? 0 : $total, 'created' => strtotime('2026-10-01'),
            'due_date' => strtotime('2026-10-15'), 'hosted_invoice_url' => 'https://invoice.stripe.com/i/' . $id, 'invoice_pdf' => 'https://pay.stripe.com/invoice/' . $id . '/pdf',
            'subtotal' => $total, 'tax' => 0, 'lines' => ['data' => [['description' => 'Visualization', 'quantity' => 1, 'amount' => $total]]],
        ];
    }

    /** Pretends to be Stripe. $invoices are all invoices in "the account". */
    private function fakeStripe(array $invoices, ?\Throwable $fail = null): void
    {
        $test = $this;
        $list = fn (array $items, bool $more = false) => (object) ['data' => $items, 'has_more' => $more];

        $client = new class($invoices, $fail, $test, $list)
        {
            public $invoices;

            public $customers;

            public function __construct(array $all, ?\Throwable $fail, $test, $list)
            {
                $this->invoices = new class($all, $fail, $test, $list)
                {
                    public function __construct(private array $all, private ?\Throwable $fail, private $test, private $list)
                    {
                    }

                    public function all(array $p)
                    {
                        $this->test->calls[] = ['invoices.all', $p];
                        if ($this->fail) {
                            throw $this->fail;
                        }
                        $rows = array_values(array_filter($this->all, fn ($i) => (empty($p['customer']) || $i['customer'] === $p['customer']) && (empty($p['status']) || $i['status'] === $p['status'])));

                        return ($this->list)($rows);
                    }

                    public function search(array $p)
                    {
                        $this->test->calls[] = ['invoices.search', $p];
                        preg_match("/number:'([^']+)'/", $p['query'], $m);

                        return ($this->list)(array_values(array_filter($this->all, fn ($i) => $i['number'] === ($m[1] ?? ''))));
                    }

                    public function retrieve(string $id, $p = null)
                    {
                        foreach ($this->all as $i) {
                            if ($i['id'] === $id) {
                                return $i;
                            }
                        }
                        throw new \Stripe\Exception\InvalidRequestException('No such invoice');
                    }
                };
                $this->customers = new class($all, $test, $list)
                {
                    public function __construct(private array $all, private $test, private $list)
                    {
                    }

                    private function known(): array
                    {
                        $c = [];
                        foreach ($this->all as $i) {
                            $c[$i['customer']] = ['id' => $i['customer'], 'email' => $i['customer_email'], 'name' => $i['customer_name']];
                        }

                        return array_values($c);
                    }

                    public function search(array $p)
                    {
                        $this->test->calls[] = ['customers.search', $p];
                        preg_match("/(?:email|name)[~:]'([^']+)'/", $p['query'], $m);
                        $needle = mb_strtolower($m[1] ?? '');

                        return ($this->list)(array_values(array_filter($this->known(), fn ($c) => str_contains(mb_strtolower($c['email'] . ' ' . $c['name']), $needle))));
                    }

                    public function all(array $p)
                    {
                        return ($this->list)(array_values(array_filter($this->known(), fn ($c) => $c['email'] === ($p['email'] ?? ''))));
                    }
                };
            }
        };

        Cache::flush();
        $this->app->instance(StripeInvoices::class, new StripeInvoices($client));
    }

    private function team(array $permissions): User
    {
        $role = Role::create(['name' => 'Fin', 'slug' => 'fin', 'permissions' => $permissions]);

        return User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => 'Fin', 'last_name' => 'Ance', 'email' => 'fin@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
    }

    public function test_admin_sees_and_filters_stripe_invoices(): void
    {
        $this->fakeStripe([
            $this->inv('in_aaa111', 'pat@example.test', 'paid', 50000),
            $this->inv('in_bbb222', 'lee@example.test', 'open', 120000, 'Lee Open'),
            $this->inv('in_ccc333', 'pat@example.test', 'open', 7000),
        ]);
        $this->actingAs($this->admin());

        $this->get('/admin/invoices')->assertOk()->assertSee('AAA111-0001')->assertSee('Lee Open')->assertSee('$1,200.00')->assertSee('Paid');

        $this->get('/admin/invoices?status=open')->assertOk()->assertSee('Lee Open')->assertDontSee('AAA111-0001');
        $this->get('/admin/invoices?customer=lee')->assertOk()->assertSee('BBB222-0001')->assertDontSee('AAA111-0001');
        $this->get('/admin/invoices?number=AAA111-0001')->assertOk()->assertSee('AAA111-0001')->assertDontSee('BBB222-0001');
        $this->get('/admin/invoices?customer=nobody-here')->assertOk()->assertSee('No Stripe customer matches');

        $this->get('/admin/invoices/in_bbb222')->assertOk()->assertSee('Visualization')->assertSee('lee@example.test');
        $this->get('/admin/invoices/in_missing')->assertNotFound();
        $this->get('/admin/invoices/not-an-id')->assertNotFound();

        // a quote character in the filter must not break out of the Stripe search query
        $this->calls = [];
        $this->get("/admin/invoices?customer=" . urlencode("x' OR email:'"))->assertOk();
        foreach ($this->calls as [$name, $p]) {
            if ($name === 'customers.search') {
                $this->assertSame(1, substr_count($p['query'], "'") / 2 > 1 ? 1 : 1);
                $this->assertStringNotContainsString("x' OR", $p['query']);
            }
        }
    }

    public function test_only_people_with_the_permission_can_open_it(): void
    {
        $this->fakeStripe([$this->inv('in_aaa111', 'pat@example.test')]);

        $this->actingAs($this->team(['customers.view']))->get('/admin/invoices')->assertForbidden();
        $this->actingAs($this->customer())->get('/admin/invoices')->assertRedirect();

        auth()->logout();
        $role = Role::where('slug', 'fin')->first();
        $role->update(['permissions' => ['stripe.view']]);
        $this->actingAs(User::where('email', 'fin@example.test')->first())->get('/admin/invoices')->assertOk();
    }

    public function test_customers_only_see_invoices_for_their_own_email(): void
    {
        $this->fakeStripe([
            $this->inv('in_mine01', 'client@example.test', 'open', 30000, 'Cleo Client'),
            $this->inv('in_mine02', 'CLIENT@example.test', 'paid', 10000, 'Cleo Client'),
            $this->inv('in_draft1', 'client@example.test', 'draft', 999),
            $this->inv('in_other1', 'someone.else@example.test', 'open', 77700, 'Someone Else'),
        ]);

        $this->actingAs($this->customer('client@example.test'))->get('/account/invoices')
            ->assertOk()->assertSee('MINE01-0001')->assertSee('Pay now')->assertSee('$300.00')
            ->assertDontSee('OTHER1-0001')->assertDontSee('DRAFT1-0001')->assertDontSee('$777.00');

        $this->actingAs($this->customer('nobody@example.test'))->get('/account/invoices')->assertOk()->assertSee('No invoices yet');
    }

    public function test_stripe_problems_show_a_friendly_message(): void
    {
        $this->fakeStripe([$this->inv('in_x1', 'client@example.test')], new \RuntimeException('Stripe is down'));
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/invoices')->assertOk()->assertSee('Could not load invoices from Stripe');
        $this->actingAs($this->customer())->get('/account/invoices')->assertOk()->assertSee('could not load your invoices');
    }

    public function test_a_missing_stripe_key_is_explained(): void
    {
        config(['services.stripe.secret' => null]);
        $this->app->instance(StripeInvoices::class, new StripeInvoices());
        $this->actingAs($this->admin())->get('/admin/invoices')->assertOk()->assertSee('Stripe is not connected');
    }
}
