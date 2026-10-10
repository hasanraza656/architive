<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\LoginCodeMail;
use Illuminate\Support\Facades\Mail;

class AuthTest extends PortalTestCase
{
    public function test_admin_can_sign_in_and_reach_dashboard(): void
    {
        $this->admin();

        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'Secret-pass-1'])->assertRedirect(route('admin.dashboard'));
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_wrong_password_and_customer_accounts_cannot_use_admin_login(): void
    {
        $this->admin();
        $this->customer();

        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'client@example.test', 'password' => 'anything'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_are_sent_to_the_right_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/account')->assertRedirect(route('customer.login'));
    }

    public function test_customer_cannot_open_admin_and_admin_cannot_open_customer_area(): void
    {
        $this->actingAs($this->customer())->get('/admin/dashboard')->assertRedirect(route('customer.dashboard'));
        $this->actingAs($this->admin())->get('/account')->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_change_password_only_with_the_current_one(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->put('/admin/settings/password', ['current_password' => 'wrong', 'password' => 'Brand-new-9', 'password_confirmation' => 'Brand-new-9'])
            ->assertSessionHasErrors('current_password', null, 'password');

        $this->put('/admin/settings/password', ['current_password' => 'Secret-pass-1', 'password' => 'Brand-new-9', 'password_confirmation' => 'Brand-new-9'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Brand-new-9', $admin->fresh()->password));
    }

    public function test_customer_signs_in_with_an_emailed_code(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->post('/account/login', ['email' => 'Client@Example.test'])->assertRedirect(route('customer.login.verify'));
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return $m->hasTo('client@example.test'); });

        $this->post('/account/login/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/account/login/verify', ['code' => $code])->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($customer);

        // a used code cannot be replayed
        $this->post('/account/logout');
        $this->post('/account/login', ['email' => 'client@example.test']);
        $this->post('/account/login/verify', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_newcomers_get_an_account_after_confirming_their_email(): void
    {
        Mail::fake();

        $this->post('/account/login', ['email' => 'new.person@example.test'])->assertRedirect(route('customer.login.verify'));
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return $m->hasTo('new.person@example.test'); });
        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.test']);      // nothing is created until the code is confirmed

        $this->post('/account/login/verify', ['code' => $code])->assertRedirect(route('customer.welcome'));
        $this->assertDatabaseHas('users', ['email' => 'new.person@example.test', 'role' => 'customer']);

        $this->get('/account')->assertRedirect(route('customer.welcome'));                  // must introduce themselves first
        $this->post('/account/welcome', ['first_name' => 'New', 'last_name' => 'Person'])->assertRedirect(route('customer.requests.create'));
        $this->get('/account')->assertOk()->assertSee('Start a new request');
    }

    public function test_admin_emails_never_get_a_customer_code(): void
    {
        Mail::fake();
        $this->admin();

        $this->post('/account/login', ['email' => 'admin@example.test'])->assertRedirect(route('customer.login.verify'));
        Mail::assertNothingSent();
    }

    public function test_invoice_link_returns_after_sign_in(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        $order->update(['status' => 'pending']);

        $this->get($order->customerUrl())->assertRedirect(route('customer.login'));
        $this->post('/account/login', ['email' => 'client@example.test']);
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return true; });

        $this->post('/account/login/verify', ['code' => $code])->assertRedirect($order->customerUrl());
    }

    public function test_code_is_burned_after_too_many_wrong_guesses(): void
    {
        Mail::fake();
        $this->customer();
        $this->post('/account/login', ['email' => 'client@example.test']);
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$code) { $code = $m->code; return true; });

        foreach (range(1, 6) as $i) {
            $this->post('/account/login/verify', ['code' => '111111']);
        }
        $this->post('/account/login/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
