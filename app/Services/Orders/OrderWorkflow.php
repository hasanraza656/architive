<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Mail\Portal\DeliveryAccepted;
use App\Mail\Portal\InvoiceSent;
use App\Mail\Portal\OrderCancelled;
use App\Mail\Portal\OrderDelivered;
use App\Mail\Portal\PaymentReceivedAdmin;
use App\Mail\Portal\PaymentReceivedCustomer;
use App\Mail\Portal\RevisionRequested;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Support\Facades\DB;

/**
 * Every status change of an order goes through here, so the rules, the timeline entry and the e-mails stay in one place.
 *
 *   draft --send--> pending --paid--> active --deliver--> delivered --accept--> completed
 *                                        ^                    |
 *                                        +----revision--------+            (cancel is possible while open)
 */
class OrderWorkflow
{
    public function __construct(private PortalMailer $mailer, private FileStorage $files)
    {
    }

    public function record(Order $order, string $type, string $message, ?User $by = null): void
    {
        $order->events()->create(['type' => $type, 'message' => $message, 'user_id' => $by?->id]);
    }

    /* ------------------------------------------------------------ Admin actions */

    /**
     * Send (or re-send) the invoice / custom offer e-mail and make the order payable.
     *
     * @return bool whether the e-mail was handed to the mail server
     */
    public function send(Order $order, User $by): bool
    {
        if (! $order->isEditable()) {
            throw new \DomainException('Only draft or unpaid orders can be sent.');
        }
        if ($order->items()->count() === 0 || $order->total_cents < config('portal.min_charge_cents')) {
            throw new \DomainException('Add at least one line item with a total of ' . money(config('portal.min_charge_cents'), $order->currency) . ' or more.');
        }

        $resend = $order->status === OrderStatus::Pending;
        $offer = $order->isRequestOrigin();       // a request from the website/portal: the invoice is a "custom offer" inside the chat
        $order->update(['status' => OrderStatus::Pending, 'sent_at' => now()]);
        $this->record($order, 'sent', ($offer ? ($resend ? 'Custom offer updated: ' : 'Custom offer sent: ') . money($order->total_cents, $order->currency)
            : ($resend ? 'Invoice re-sent to ' : 'Invoice sent to ') . $order->customer->email), $by);

        if ($offer) {
            // the offer card in the conversation (no extra "new message" e-mail: the offer e-mail below covers it)
            OrderMessage::create(['order_id' => $order->id, 'user_id' => $by->id, 'kind' => 'offer', 'body' => $resend ? 'Updated custom offer' : 'Custom offer']);
        }

        return $this->mailer->send($order->customer->email, new InvoiceSent($order->fresh(['customer', 'items'])));
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function deliver(Order $order, User $by, ?string $note, array $files): OrderDelivery
    {
        if ($order->status !== OrderStatus::Active) {
            throw new \DomainException('Only orders in progress can be delivered.');
        }

        $delivery = DB::transaction(function () use ($order, $by, $note, $files) {
            $delivery = $order->deliveries()->create(['user_id' => $by->id, 'note' => $note]);
            $this->files->storeMany($files, $order, $by, ['delivery_id' => $delivery->id]);
            $order->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
            $this->record($order, 'delivered', 'Work delivered', $by);

            return $delivery;
        });

        $this->mailer->send($order->customer->email, new OrderDelivered($order->fresh(['customer']), $delivery->load('files')));

        return $delivery;
    }

    public function complete(Order $order, User $by): void
    {
        if (! $order->status->isRunning()) {
            throw new \DomainException('Only orders in progress or delivered can be completed.');
        }
        $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
        $this->record($order, 'completed', 'Order marked as completed', $by);
    }

    public function cancel(Order $order, User $by, ?string $reason): void
    {
        if ($order->status->isClosed()) {
            throw new \DomainException('This order is already closed.');
        }
        $wasVisible = $order->isVisibleToCustomer();
        $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
        $this->record($order, 'cancelled', ($order->paid_at || ! $order->isRequestOrigin() ? 'Order cancelled' : 'Request closed') . ($reason ? ': ' . $reason : ''), $by);

        if ($wasVisible) {
            $this->mailer->send($order->customer->email, new OrderCancelled($order->fresh(['customer'])));
        }
    }

    /* ------------------------------------------------------------ Customer actions */

    public function acceptDelivery(Order $order, User $by): void
    {
        if ($order->status !== OrderStatus::Delivered) {
            throw new \DomainException('There is no delivery waiting for approval.');
        }
        $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
        $this->record($order, 'completed', 'Delivery accepted, order completed', $by);
        $this->mailer->toAdmin(new DeliveryAccepted($order->fresh(['customer'])));
    }

    public function requestRevision(Order $order, User $by, string $reason): void
    {
        if ($order->status !== OrderStatus::Delivered) {
            throw new \DomainException('A revision can only be requested after a delivery.');
        }
        $order->update(['status' => OrderStatus::Active, 'delivered_at' => null]);
        $this->record($order, 'revision', 'Revision requested', $by);
        OrderMessage::create(['order_id' => $order->id, 'user_id' => $by->id, 'body' => "Revision requested:\n" . $reason]);
        $this->mailer->toAdmin(new RevisionRequested($order->fresh(['customer']), $reason));
    }

    /* ------------------------------------------------------------ Payment */

    /**
     * Called by the Stripe return page AND the webhook; whichever arrives first wins, the second is a no-op.
     *
     * @return bool true only for the call that actually moved the order to "active"
     */
    public function markPaid(Order $order, ?string $sessionId, ?string $paymentIntent): bool
    {
        $moved = Order::where('id', $order->id)
            ->where('status', OrderStatus::Pending->value)
            ->update([
                'status' => OrderStatus::Active->value,
                'paid_at' => now(),
                'stripe_session_id' => $sessionId,
                'stripe_payment_intent' => $paymentIntent,
                'updated_at' => now(),
            ]);

        if (! $moved) {
            return false;
        }

        $order = $order->fresh(['customer', 'items']);
        $this->record($order, 'paid', 'Payment of ' . money($order->total_cents, $order->currency) . ' received', $order->customer);
        $this->mailer->send($order->customer->email, new PaymentReceivedCustomer($order));
        $this->mailer->toAdmin(new PaymentReceivedAdmin($order));

        return true;
    }
}
