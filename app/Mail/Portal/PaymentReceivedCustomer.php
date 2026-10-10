<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Customer: payment confirmation / receipt. */
class PaymentReceivedCustomer extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment received for ' . $this->order->number . ' – thank you');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.payment-customer', with: ['order' => $this->order]);
    }

    /** The paid invoice as a PDF. */
    public function attachments(): array
    {
        $pdf = app(\App\Services\Orders\InvoicePdf::class);

        return [Attachment::fromData(fn () => $pdf->render($this->order), $pdf->filename($this->order))->withMime('application/pdf')];
    }
}
