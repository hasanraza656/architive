<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;

/** Admin: the customer asked for changes after a delivery. */
class RevisionRequested extends Mailable
{
    public function __construct(public Order $order, public string $reason)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Revision requested: ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.revision-requested', with: ['order' => $this->order, 'reason' => $this->reason]);
    }
}
