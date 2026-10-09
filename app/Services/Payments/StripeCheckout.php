<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Orders\OrderWorkflow;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe Checkout (hosted payment page). Keys come from .env: STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET.
 * The customer is sent to Stripe, pays there, and comes back to success() — the webhook is a safety net for closed tabs.
 */
class StripeCheckout
{
    public function __construct(private OrderWorkflow $workflow)
    {
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            throw new \RuntimeException('Stripe is not configured (STRIPE_SECRET is missing).');
        }

        return new StripeClient($secret);
    }

    /** Creates a Checkout Session for the order and returns the Stripe-hosted URL. */
    public function createSession(Order $order): string
    {
        $order->loadMissing('customer');

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $order->customer->email,
            'client_reference_id' => (string) $order->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $order->total_cents,
                    'product_data' => [
                        'name' => 'Invoice ' . $order->number,
                        'description' => mb_substr($order->title, 0, 250),
                    ],
                ],
            ]],
            'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number],
            'payment_intent_data' => [
                'description' => 'Architive ' . $order->number . ' · ' . mb_substr($order->title, 0, 100),
                'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number],
                'receipt_email' => $order->customer->email,
            ],
            'success_url' => route('customer.orders.paid', $order) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('customer.orders.show', $order) . '?canceled=1',
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    /** Called when the customer returns from Stripe: confirm with Stripe that the money really arrived. */
    public function confirmReturn(Order $order, string $sessionId): bool
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId);

        return $this->settle($order, $session);
    }

    /**
     * Webhook-free safety net: if this unpaid order has a Checkout Session, ask Stripe whether it was paid.
     * Called when someone opens the order and by `portal:reconcile-payments`. Never throws.
     */
    public function reconcile(Order $order): bool
    {
        if ($order->status !== \App\Enums\OrderStatus::Pending || ! $order->stripe_session_id || ! config('services.stripe.secret')) {
            return false;
        }

        try {
            return $this->confirmReturn($order, $order->stripe_session_id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Stripe reconcile skipped: ' . $e->getMessage(), ['order' => $order->number]);

            return false;
        }
    }

    /** Verifies and handles a webhook call. Throws on a bad signature. */
    public function handleWebhook(string $payload, ?string $signature): void
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET is not set.');
        }

        try {
            $event = Webhook::constructEvent($payload, (string) $signature, $secret);
        } catch (SignatureVerificationException $e) {
            throw new \InvalidArgumentException('Invalid Stripe signature.');
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event->data->object;
            $order = Order::find((int) ($session->metadata->order_id ?? 0));
            if ($order) {
                $this->settle($order, $session);
            }
        }
    }

    /** Mark the order paid if (and only if) this Checkout Session belongs to it and is paid. */
    private function settle(Order $order, object $session): bool
    {
        $belongs = (string) ($session->metadata->order_id ?? '') === (string) $order->id;
        if (! $belongs || ($session->payment_status ?? '') !== 'paid') {
            return false;
        }
        if ((int) $session->amount_total !== (int) $order->total_cents) {
            return false;   // amount changed after the session was created: never auto-accept
        }

        $this->workflow->markPaid($order, $session->id, is_string($session->payment_intent ?? null) ? $session->payment_intent : null);

        return true;
    }
}
