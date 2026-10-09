<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Customer: the order was cancelled. */
class OrderCancelled extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order ' . $this->order->number . ' was cancelled');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.order-cancelled', with: ['order' => $this->order]);
    }
}
