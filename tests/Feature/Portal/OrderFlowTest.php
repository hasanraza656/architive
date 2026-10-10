<?php

namespace Tests\Feature\Portal;

use App\Enums\OrderStatus;
use App\Mail\Portal\DeliveryAccepted;
use App\Mail\Portal\InvoiceSent;
use App\Mail\Portal\OrderDelivered;
use App\Mail\Portal\PaymentReceivedAdmin;
use App\Mail\Portal\PaymentReceivedCustomer;
use App\Models\Order;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrderFlowTest extends PortalTestCase
{
    public function test_totals_are_calculated_in_cents(): void
    {
        $order = $this->order($this->customer(), $this->admin());

        $this->assertSame('ARC-' . (1000 + $order->id), $order->number);
        $this->assertSame(75000, $order->subtotal_cents);
        $this->assertSame(5000, $order->discount_cents);
        $this->assertSame(7000, $order->tax_cents);
        $this->assertSame(77000, $order->total_cents);
        $this->assertSame(OrderStatus::Draft, $order->status);
    }

    public function test_admin_creates_an_order_from_the_form_and_sends_the_invoice(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->post('/admin/orders', [
            'customer_id' => $customer->id, 'title' => 'Kitchen render', 'action' => 'send',
            'due_at' => now()->addDays(7)->toIso8601String(),
            'items' => [['description' => 'Interior render', 'quantity' => 1, 'unit_price' => '300.00']],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(30000, $order->total_cents);
        $this->assertNotNull($order->due_at);
        Mail::assertSent(InvoiceSent::class, fn ($m) => $m->hasTo('client@example.test'));
    }

    public function test_an_order_needs_a_customer_and_an_item(): void
    {
        $this->actingAs($this->admin())->post('/admin/orders', ['title' => 'x', 'items' => []])->assertSessionHasErrors(['customer_id', 'items']);
    }

    public function test_drafts_are_hidden_from_customers_and_paid_orders_cannot_be_edited(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);

        $this->actingAs($customer)->get($order->customerUrl())->assertForbidden();

        app(OrderWorkflow::class)->send($order, $admin);
        $this->actingAs($customer)->get($order->customerUrl())->assertOk()->assertSee('Pay securely');

        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');
        $this->actingAs($admin)->get('/admin/orders/' . $order->number . '/edit')->assertForbidden();
    }

    public function test_payment_is_confirmed_once_and_notifies_both_sides(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $order = $this->order($this->customer(), $admin);
        $workflow = app(OrderWorkflow::class);
        $workflow->send($order, $admin);

        $this->assertTrue($workflow->markPaid($order, 'cs_1', 'pi_1'));
        $this->assertFalse($workflow->markPaid($order, 'cs_1', 'pi_1'));       // second call (webhook after return page) does nothing

        $order->refresh();
        $this->assertSame(OrderStatus::Active, $order->status);
        $this->assertNotNull($order->paid_at);
        Mail::assertSent(PaymentReceivedCustomer::class, 1);
        Mail::assertSent(PaymentReceivedAdmin::class, 1);
    }

    public function test_delivery_acceptance_and_revision_cycle(): void
    {
        Mail::fake();
        Storage::fake('uploads');
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        $wf = app(OrderWorkflow::class);
        $wf->send($order, $admin);
        $wf->markPaid($order, 'cs_1', 'pi_1');

        $this->actingAs($admin)->post("/admin/orders/{$order->number}/deliver", [
            'note' => 'Done', 'files' => [UploadedFile::fake()->create('render.zip', 200)],
        ])->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertCount(1, $order->deliveries->first()->files);
        Mail::assertSent(OrderDelivered::class, fn ($m) => $m->hasTo('client@example.test'));

        $this->actingAs($customer)->post($order->customerUrl() . '/revision', ['reason' => 'Make it brighter please'])->assertRedirect();
        $this->assertSame(OrderStatus::Active, $order->fresh()->status);

        $wf->deliver($order->fresh(), $admin, null, [UploadedFile::fake()->create('v2.zip', 100)]);
        $this->actingAs($customer)->post($order->customerUrl() . '/accept')->assertRedirect();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        Mail::assertSent(DeliveryAccepted::class);
    }

    public function test_cancel_and_delete_rules(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $draft = $this->order($this->customer(), $admin);

        $this->actingAs($admin)->delete("/admin/orders/{$draft->number}")->assertRedirect(route('admin.orders.index'));
        $this->assertNull(Order::find($draft->id));

        $sent = $this->order($this->customer('b@example.test'), $admin);
        app(OrderWorkflow::class)->send($sent, $admin);
        $this->actingAs($admin)->post("/admin/orders/{$sent->number}/cancel", ['reason' => 'Changed plans'])->assertRedirect();
        $this->assertSame(OrderStatus::Cancelled, $sent->fresh()->status);
        $this->actingAs($admin)->delete("/admin/orders/{$sent->number}")->assertRedirect();   // admin may delete any order
        $this->assertNull(Order::find($sent->id));
    }

    public function test_orders_below_the_minimum_charge_cannot_be_sent(): void
    {
        $admin = $this->admin();
        $order = $this->order($this->customer(), $admin, ['discount' => 0, 'tax_rate' => 0, 'items' => [['description' => 'Tiny', 'quantity' => 1, 'unit_price' => 0.10]]]);

        $this->expectException(\DomainException::class);
        app(OrderWorkflow::class)->send($order, $admin);
    }

    public function test_dashboards_render_with_data(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        app(OrderWorkflow::class)->send($order, $admin);
        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Upcoming due');
        $this->actingAs($admin)->get('/admin/orders?status=active&q=Riverside')->assertOk()->assertSee('Riverside House');
        $this->actingAs($admin)->get('/admin/customers')->assertOk()->assertSee('Cleo Client');
        $this->actingAs($customer)->get('/account')->assertOk()->assertSee('Riverside House');
    }

    public function test_admin_creates_a_customer_inline_with_a_country_code(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'Pat@Example.test', 'phone_country' => 'GB', 'phone' => '07700 900123'])
            ->assertCreated()->assertJson(['email' => 'pat@example.test', 'phone' => '+447700900123']);

        $this->postJson('/admin/customers', ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.test'])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
