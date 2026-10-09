<?php

namespace Tests\Feature\Portal;

use App\Enums\OrderStatus;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Support\Facades\Mail;

class StripeWebhookTest extends PortalTestCase
{
    private const SECRET = 'whsec_test_secret';

    private function sign(string $payload): string
    {
        $t = time();

        return "t={$t},v1=" . hash_hmac('sha256', "{$t}.{$payload}", self::SECRET);
    }

    private function event(int $orderId, int $amount, string $status = 'paid'): string
    {
        return json_encode([
            'id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_1', 'object' => 'checkout.session', 'payment_status' => $status, 'amount_total' => $amount,
                'payment_intent' => 'pi_test_1', 'metadata' => ['order_id' => (string) $orderId],
            ]],
        ]);
    }

    private function pendingOrder()
    {
        Mail::fake();
        $admin = $this->admin();
        $order = $this->order($this->customer(), $admin);
        app(OrderWorkflow::class)->send($order, $admin);
        config(['services.stripe.webhook_secret' => self::SECRET]);

        return $order->fresh();
    }

    private function hook(string $payload, ?string $sig)
    {
        return $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => (string) $sig, 'CONTENT_TYPE' => 'application/json'], $payload);
    }

    public function test_a_valid_paid_event_activates_the_order(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->event($order->id, $order->total_cents);

        $this->hook($payload, $this->sign($payload))->assertOk();
        $this->assertSame(OrderStatus::Active, $order->fresh()->status);
        $this->assertSame('pi_test_1', $order->fresh()->stripe_payment_intent);

        $this->hook($payload, $this->sign($payload))->assertOk();      // Stripe retries: still fine
    }

    public function test_bad_signature_is_rejected(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->event($order->id, $order->total_cents);

        $this->hook($payload, 't=1,v1=deadbeef')->assertStatus(400);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_wrong_amount_or_unpaid_sessions_do_not_activate(): void
    {
        $order = $this->pendingOrder();

        $p = $this->event($order->id, 100);
        $this->hook($p, $this->sign($p))->assertOk();
        $p = $this->event($order->id, $order->total_cents, 'unpaid');
        $this->hook($p, $this->sign($p))->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_webhook_needs_no_csrf_token_and_no_secret_means_error(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        $this->hook("{}", "x")->assertStatus(500);
    }
}
