<?php

namespace App\Mail\Portal;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Customer: we received your request, here is what happens next and how to follow it. */
class RequestReceived extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We received your request ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.request-received', with: ['order' => $this->order]);
    }
}
