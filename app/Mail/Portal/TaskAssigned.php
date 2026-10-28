<?php

namespace App\Mail\Portal;

use App\Models\Task;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Team member: a task was assigned to you. */
class TaskAssigned extends Mailable
{
    public function __construct(public Task $task, public User $user, public User $by)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Task assigned to you: ' . $this->task->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal.task-assigned', with: ['task' => $this->task, 'user' => $this->user, 'by' => $this->by]);
    }
}
