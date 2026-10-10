<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Admin: a customer has paid, the order is now in progress. */
class PaymentReceivedAdmin extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment received: ' . $this->order->number . ' · ' . money($this->order->total_cents, $this->order->currency));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.payment-admin', with: ['order' => $this->order]);
    }

    /** The paid invoice as a PDF. */
    public function attachments(): array
    {
        $pdf = app(\App\Services\Orders\InvoicePdf::class);

        return [Attachment::fromData(fn () => $pdf->render($this->order), $pdf->filename($this->order))->withMime('application/pdf')];
    }
}
