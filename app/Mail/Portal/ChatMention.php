<?php

namespace App\Mail\Portal;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Someone was tagged with @ in a conversation, or was added to it. */
class ChatMention extends Mailable
{
    public function __construct(public Order $order, public User $user, public User $by, public string $kind, public ?OrderMessage $message = null, public string $link = '')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->kind === 'added'
            ? $this->by->name . ' added you to ' . $this->order->number
            : $this->by->name . ' mentioned you in ' . $this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.chat-mention', with: ['order' => $this->order, 'user' => $this->user, 'by' => $this->by, 'kind' => $this->kind, 'excerpt' => $this->message ? $this->plain($this->message->body) : '', 'link' => $this->link]);
    }

    /** The message text without the @[Name](u:1) token syntax. */
    private function plain(?string $body): string
    {
        $text = preg_replace('/([@#])\[([^\]]*)\]\([utp]:\d+\)/u', '$1$2', (string) $body);

        return \Illuminate\Support\Str::limit(trim($text), 280);
    }
}
