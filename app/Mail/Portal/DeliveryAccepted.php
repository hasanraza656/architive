<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Admin: the customer accepted the delivery. */
class DeliveryAccepted extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Delivery accepted: ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.delivery-accepted', with: ['order' => $this->order]);
    }
}
