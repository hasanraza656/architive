<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\WelcomeCustomer;
use Illuminate\Support\Facades\Mail;

/** Welcome e-mail for customers: optional tick box when the team creates one, and a manual "send" button. */
class WelcomeEmailTest extends PortalTestCase
{
    public function test_welcome_email_renders_with_the_sign_in_link(): void
    {
        $html = (new WelcomeCustomer($this->customer('new@example.test')))->render();

        $this->assertStringContainsString('your client area is ready', $html);
        $this->assertStringContainsString('new@example.test', $html);
        $this->assertStringContainsString('/account/login', $html);
    }

    public function test_welcome_email_is_sent_when_the_box_is_ticked(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());

        $this->post('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.test', 'send_welcome' => '1'])
            ->assertRedirect()->assertSessionHas('success');

        Mail::assertSent(WelcomeCustomer::class, fn ($m) => $m->hasTo('pat@example.test'));
    }

    public function test_no_welcome_email_when_the_box_is_not_ticked(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());

        $this->post('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.test'])->assertRedirect();
        $this->postJson('/admin/customers', ['first_name' => 'Sam', 'last_name' => 'Roe', 'email' => 'sam@example.test', 'send_welcome' => false])
            ->assertCreated()->assertJson(['welcome' => null]);

        Mail::assertNotSent(WelcomeCustomer::class);
        $this->assertDatabaseHas('users', ['email' => 'pat@example.test']);
    }

    public function test_inline_customer_from_the_order_form_can_send_the_welcome_email(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());

        $this->postJson('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.test', 'send_welcome' => true])
            ->assertCreated()->assertJson(['welcome' => true]);

        Mail::assertSent(WelcomeCustomer::class, 1);
    }

    public function test_customer_is_still_created_when_the_mail_server_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $this->actingAs($this->admin());

        $this->post('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.test', 'send_welcome' => '1'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['email' => 'pat@example.test']);
    }

    public function test_manual_button_on_the_customer_page_and_the_order_page(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);

        $this->actingAs($admin)->get('/admin/customers/' . $customer->id)->assertOk()->assertSee('Send welcome e-mail');
        $this->get('/admin/orders/' . $order->number)->assertOk()->assertSee('Send welcome e-mail');

        $this->post('/admin/customers/' . $customer->id . '/welcome')->assertRedirect()->assertSessionHas('success');
        Mail::assertSent(WelcomeCustomer::class, fn ($m) => $m->hasTo($customer->email));
    }

    public function test_welcome_email_is_not_sent_to_a_switched_off_account_or_by_a_customer(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->actingAs($customer)->post('/admin/customers/' . $customer->id . '/welcome')->assertStatus(302);
        Mail::assertNotSent(WelcomeCustomer::class);

        $customer->update(['is_active' => false]);
        $this->actingAs($this->admin())->post('/admin/customers/' . $customer->id . '/welcome')->assertSessionHas('error');
        Mail::assertNotSent(WelcomeCustomer::class);
    }

    public function test_new_customer_form_has_the_box_ticked_by_default(): void
    {
        $this->actingAs($this->admin())->get('/admin/customers/create')->assertOk()
            ->assertSee('name="send_welcome" value="1" checked', false);
    }
}
