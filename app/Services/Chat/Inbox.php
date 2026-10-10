<?php

namespace App\Services\Chat;

use App\Models\OrderMessage;
use App\Models\OrderReadState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Admin inbox: every chat message across all orders. A customer message counts as unread until ANY admin has read
 * past it in that order (same rule as the dashboard), so the team shares one inbox. Messages sent by the team are never "unread".
 */
class Inbox
{
    /** SQL: highest message id an admin has read in this message's order. */
    private const ADMIN_READ = '(SELECT COALESCE(MAX(s.last_read_message_id), 0) FROM order_read_states s JOIN users a ON a.id = s.user_id WHERE s.order_id = order_messages.order_id AND a.role = \'admin\')';

    /** Text messages only (offer cards are system messages), newest first, with an `is_unread` flag. */
    public function query(): Builder
    {
        return OrderMessage::query()
            ->select('order_messages.*')
            ->selectRaw('(order_messages.user_id IN (SELECT id FROM users WHERE role = \'customer\') AND order_messages.id > ' . self::ADMIN_READ . ') AS is_unread')
            ->where('order_messages.kind', 'text')
            ->with(['order.customer', 'user', 'files']);
    }

    public function onlyUnread(Builder $q): Builder
    {
        return $q->whereRaw('order_messages.user_id IN (SELECT id FROM users WHERE role = \'customer\')')
            ->whereRaw('order_messages.id > ' . self::ADMIN_READ);
    }

    public function onlyRead(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->whereRaw('order_messages.user_id NOT IN (SELECT id FROM users WHERE role = \'customer\')')
                ->orWhereRaw('order_messages.id <= ' . self::ADMIN_READ);
        });
    }

    public function unreadCount(): int
    {
        return $this->onlyUnread(OrderMessage::query()->where('order_messages.kind', 'text'))->count();
    }

    /** The admin has now read this message (and everything before it) in its order. */
    public function markRead(OrderMessage $message, User $admin): void
    {
        $state = OrderReadState::firstOrCreate(['order_id' => $message->order_id, 'user_id' => $admin->id]);
        if ((int) $state->last_read_message_id < $message->id) {
            $state->forceFill(['last_read_message_id' => $message->id])->save();
        }
    }

    /** Make this message (and the ones after it) unread again, for every admin. */
    public function markUnread(OrderMessage $message): void
    {
        OrderReadState::where('order_id', $message->order_id)
            ->whereIn('user_id', User::where('role', User::ROLE_ADMIN)->select('id'))
            ->where('last_read_message_id', '>=', $message->id)
            ->update(['last_read_message_id' => $message->id - 1]);
    }

    /** Everything currently in the inbox counts as read. */
    public function markAllRead(User $admin): int
    {
        $rows = $this->onlyUnread(OrderMessage::query()->where('order_messages.kind', 'text'))
            ->select('order_messages.order_id', DB::raw('MAX(order_messages.id) AS last_id'))
            ->groupBy('order_messages.order_id')->get();

        foreach ($rows as $row) {
            $state = OrderReadState::firstOrCreate(['order_id' => $row->order_id, 'user_id' => $admin->id]);
            $state->forceFill(['last_read_message_id' => max((int) $state->last_read_message_id, (int) $row->last_id)])->save();
        }

        return $rows->count();
    }
}
