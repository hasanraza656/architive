<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;
use App\Models\OrderDelivery;

/** Customer: work has been delivered. */
class OrderDelivered extends Mailable
{
    public function __construct(public Order $order, public OrderDelivery $delivery)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your delivery is ready: ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.order-delivered', with: ['order' => $this->order, 'delivery' => $this->delivery]);
    }
}
