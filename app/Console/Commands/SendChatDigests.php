<?php

namespace App\Console\Commands;

use App\Services\Chat\ChatNotifier;
use Illuminate\Console\Command;

/** Scheduled every 5 minutes: one summary e-mail for chat messages that stayed unread (see ChatNotifier). */
class SendChatDigests extends Command
{
    protected $signature = 'portal:chat-digest';

    protected $description = 'E-mail a digest of unread order chat messages';

    public function handle(ChatNotifier $notifier): int
    {
        $this->info('Digest e-mails sent: ' . $notifier->sendDigests());

        return self::SUCCESS;
    }
}
