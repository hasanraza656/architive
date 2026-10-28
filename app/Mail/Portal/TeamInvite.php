<?php

namespace App\Mail\Portal;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A new team member: "you have been added, set your password". Also used for "reset your password" (invite = false). */
class TeamInvite extends Mailable
{
    public function __construct(public User $user, public string $link, public ?User $inviter = null, public bool $invite = true)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->invite ? 'You have been added to the Architive team' : 'Reset your Architive password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.team-invite', with: ['user' => $this->user, 'link' => $this->link, 'inviter' => $this->inviter, 'invite' => $this->invite]);
    }
}
