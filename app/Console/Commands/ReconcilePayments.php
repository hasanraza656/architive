<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payments\StripeCheckout;
use Illuminate\Console\Command;

/** Marks unpaid orders as paid when Stripe says their Checkout Session was paid (works without a webhook). */
class ReconcilePayments extends Command
{
    protected $signature = 'portal:reconcile-payments';

    protected $description = 'Check Stripe for payments that were completed but not yet recorded';

    public function handle(StripeCheckout $stripe): int
    {
        $found = 0;
        Order::where('status', OrderStatus::Pending->value)->whereNotNull('stripe_session_id')->where('updated_at', '>', now()->subDays(3))
            ->each(function (Order $o) use ($stripe, &$found) { $found += $stripe->reconcile($o) ? 1 : 0; });

        $this->info("Payments recorded: {$found}");

        return self::SUCCESS;
    }
}
