<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\Order;
use Illuminate\Support\Collection;

/** Either side: unread chat messages (already throttled by ChatNotifier). */
class NewChatMessages extends Mailable
{
    public function __construct(public Order $order, public Collection $messages, public bool $toAdmin)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->messages->count() > 1 ? $this->messages->count() . ' new messages' : 'New message') . ' on ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.new-messages', with: ['order' => $this->order, 'messages' => $this->messages, 'toAdmin' => $this->toAdmin]);
    }
}
