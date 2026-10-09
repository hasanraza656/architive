<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
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
}
