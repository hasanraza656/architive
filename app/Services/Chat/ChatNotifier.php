<?php

namespace App\Services\Chat;

use App\Enums\OrderStatus;
use App\Mail\Portal\ChatMention;
use App\Mail\Portal\NewChatMessages;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderReadState;
use App\Models\User;
use App\Services\Notifications\PortalMailer;

/**
 * Decides when a chat message deserves an e-mail. The rule of thumb: never for someone who is looking at the chat,
 * never more than one e-mail per person per order within the cool-down, and anything that slipped through the
 * cool-down is bundled into a single digest by `php artisan portal:chat-digest` (run every few minutes by the scheduler).
 */
class ChatNotifier
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    /** Called right after a message is stored. */
    public function afterMessage(OrderMessage $message): void
    {
        $order = $message->order;
        if ($order->status === OrderStatus::Cancelled) {
            return;
        }

        $sender = $message->user;
        if ($message->kind === 'system') {
            return;
        }
        $toAdmin = $sender->isCustomer();      // customer wrote -> the team is notified, a team member wrote -> the customer is
        $this->notifySide($order, $toAdmin, immediate: true);
        $this->notifyMentions($message);
    }

    /** A team member was added to the conversation: tell them once. */
    public function memberAdded(Order $order, User $member, User $by): void
    {
        if ($member->id === $by->id) {
            return;
        }
        $this->mailer->send($member->email, new ChatMention($order, $member, $by, 'added', null, route('admin.orders.show', $order) . '#chat'));
    }

    /** People tagged with @ get their own e-mail unless they are on the page right now or were e-mailed a moment ago. */
    private function notifyMentions(OrderMessage $message): void
    {
        $order = $message->order;
        $ids = $message->mentions->where('type', 'user')->pluck('target_id')->map(fn ($i) => (int) $i)->reject(fn ($i) => $i === $message->user_id)->all();
        if (! $ids) {
            return;
        }
        foreach (User::whereIn('id', $ids)->where('is_active', true)->get() as $user) {
            $state = OrderReadState::firstOrCreate(['order_id' => $order->id, 'user_id' => $user->id]);
            $cool = $state->last_emailed_at && $state->last_emailed_at->gt(now()->subMinutes(config('portal.chat.email_cooldown_minutes')));
            if ($state->isOnline() || $cool) {
                continue;
            }
            $link = $user->isTeam() ? route('admin.orders.show', $order) . '?m=' . $message->id . '#chat' : $order->customerUrl();
            if ($this->mailer->send($user->email, new ChatMention($order, $user, $message->user, 'mention', $message, $link))) {
                $state->forceFill(['last_emailed_at' => now()])->save();
            }
        }
    }

    /** Scheduler entry point: mail every side that still has unread messages older than the digest delay. */
    public function sendDigests(): int
    {
        $sent = 0;
        $orders = Order::whereHas('messages', fn ($q) => $q->where('created_at', '<=', now()->subMinutes(config('portal.chat.digest_after_minutes'))))
            ->with('customer')->get();

        foreach ($orders as $order) {
            foreach ([true, false] as $toAdmin) {
                $sent += $this->notifySide($order, $toAdmin, immediate: false) ? 1 : 0;
            }
        }

        return $sent;
    }

    /* ------------------------------------------------------------ internals */

    private function notifySide(Order $order, bool $toAdmin, bool $immediate): bool
    {
        $state = $this->state($order, $toAdmin);
        if ($state['online']) {
            return false;                                           // they are on the page, no e-mail needed
        }

        $query = $order->messages()->with(['user', 'files'])->where('kind', 'text')      // offer cards are covered by the offer e-mail
            ->whereHas('user', fn ($q) => $toAdmin ? $q->where('role', User::ROLE_CUSTOMER) : $q->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF]))
            ->where('id', '>', max($state['read'], $state['emailed_id']));

        if (! $immediate) {
            $query->where('created_at', '<=', now()->subMinutes(config('portal.chat.digest_after_minutes')));
        }
        $messages = $query->get();
        if ($messages->isEmpty()) {
            return false;
        }

        $cool = $state['emailed_at'] && $state['emailed_at']->gt(now()->subMinutes(config('portal.chat.email_cooldown_minutes')));
        if ($cool) {
            return false;                                           // already mailed recently; the digest will pick these up later
        }

        $to = $toAdmin ? $this->mailer->adminAddress() : $order->customer->email;
        if (! $to) {
            return false;
        }

        $ok = $this->mailer->send($to, new NewChatMessages($order, $messages, $toAdmin));
        if ($ok) {
            $this->saveEmailed($order, $toAdmin, (int) $messages->max('id'));
        }

        return $ok;
    }

    /**
     * Read/online/e-mail state for one side. The admin side is a team: it is "online" if any admin is on the page,
     * and what one admin has read counts for all.
     */
    private function state(Order $order, bool $admin): array
    {
        $rows = OrderReadState::where('order_id', $order->id)
            ->whereHas('user', fn ($q) => $admin ? $q->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF]) : $q->where('role', User::ROLE_CUSTOMER))
            ->get();

        return [
            'read' => (int) $rows->max('last_read_message_id'),
            'emailed_id' => (int) $rows->max('last_emailed_message_id'),
            'emailed_at' => $rows->max('last_emailed_at'),
            'online' => $rows->contains(fn (OrderReadState $r) => $r->isOnline()),
        ];
    }

    private function saveEmailed(Order $order, bool $admin, int $messageId): void
    {
        $user = $admin ? User::where('role', User::ROLE_ADMIN)->orderBy('id')->first() : $order->customer;
        if (! $user) {
            return;
        }
        OrderReadState::updateOrCreate(
            ['order_id' => $order->id, 'user_id' => $user->id],
            ['last_emailed_at' => now(), 'last_emailed_message_id' => $messageId],
        );
    }
}
