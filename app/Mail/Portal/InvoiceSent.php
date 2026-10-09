<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Customer: the invoice is ready, with the pay link. */
class InvoiceSent extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice ' . $this->order->number . ' from Architive · ' . money($this->order->total_cents, $this->order->currency));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.invoice-sent', with: ['order' => $this->order]);
    }
}
