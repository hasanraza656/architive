<?php

namespace App\Mail\Portal;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use App\Models\User;

/** Customer: the one-time sign-in code. */
class LoginCodeMail extends Mailable
{
    public function __construct(public ?User $user, public string $code, public int $minutes)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->code . ' is your Architive sign-in code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.login-code', with: ['user' => $this->user, 'code' => $this->code, 'minutes' => $this->minutes]);
    }
}
