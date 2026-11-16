<?php

namespace App\Mail\Portal;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Customer: your client area is ready and how to sign in (no password, e-mailed one-time code). Sent when the team creates a customer, or by hand. */
class WelcomeCustomer extends Mailable
{
    public function __construct(public User $customer)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Architive: your client area is ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.welcome', with: ['customer' => $this->customer]);
    }
}
