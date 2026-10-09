<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Pay an invoice with Stripe Checkout, and handle the customer coming back from Stripe. */
class CheckoutController extends Controller
{
    public function __construct(private StripeCheckout $stripe)
    {
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        try {
            return redirect()->away($this->stripe->createSession($order));
        } catch (\Throwable $e) {
            Log::error('Stripe checkout could not start: ' . $e->getMessage(), ['order' => $order->number]);

            return redirect()->to($order->customerUrl())->with('error', 'We could not open the payment page. Please try again in a moment, or message us in the conversation.');
        }
    }

    /** Stripe sends the customer here after paying. We ask Stripe directly instead of trusting the URL. */
    public function returned(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        $sessionId = (string) $request->query('session_id');

        if ($order->status === OrderStatus::Pending && $sessionId !== '') {
            try {
                $this->stripe->confirmReturn($order, $sessionId);
            } catch (\Throwable $e) {
                Log::warning('Stripe return could not be confirmed: ' . $e->getMessage(), ['order' => $order->number]);
            }
        }

        $order->refresh();

        return redirect()->to($order->customerUrl())->with(
            $order->status === OrderStatus::Pending ? 'info' : 'success',
            $order->status === OrderStatus::Pending
                ? 'We are still confirming your payment. This page will update in a minute; you will also get an e-mail.'
                : 'Payment received. Thank you! Your order has started.'
        );
    }
}
