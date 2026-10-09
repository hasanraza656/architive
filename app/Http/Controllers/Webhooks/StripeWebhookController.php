<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/** Stripe -> us. Marks orders paid even if the customer closed the tab before returning. */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckout $stripe): Response
    {
        try {
            $stripe->handleWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (\InvalidArgumentException $e) {
            return response('Invalid signature', 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed: ' . $e->getMessage());

            return response('Webhook error', 500);
        }

        return response('ok', 200);
    }
}
