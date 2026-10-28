<?php

namespace App\Services\Chat;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderReadState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Inbox: every chat message across the conversations a person may open. Unread is personal: a message written by
 * someone else is unread for you until YOUR read marker in that conversation has passed it.
 */
class Inbox
{
    /** SQL snippet: this viewer's read marker for the message's order (0 = nothing read yet). */
    private function marker(User $viewer): string
    {
        return '(SELECT COALESCE(MAX(s.last_read_message_id), 0) FROM order_read_states s WHERE s.order_id = order_messages.order_id AND s.user_id = ' . (int) $viewer->id . ')';
    }

    /** Text messages (offer cards are system messages) in the viewer's conversations, with an `is_unread` flag. */
    public function query(User $viewer): Builder
    {
        return OrderMessage::query()
            ->select('order_messages.*')
            ->selectRaw('(order_messages.user_id != ? AND order_messages.id > ' . $this->marker($viewer) . ') AS is_unread', [$viewer->id])
            ->where('order_messages.kind', 'text')
            ->whereIn('order_messages.order_id', Order::visibleTo($viewer)->select('orders.id'))
            ->with(['order.customer', 'user', 'files']);
    }

    public function onlyUnread(Builder $q, User $viewer): Builder
    {
        return $q->where('order_messages.user_id', '!=', $viewer->id)->whereRaw('order_messages.id > ' . $this->marker($viewer));
    }

    public function onlyRead(Builder $q, User $viewer): Builder
    {
        return $q->where(fn ($w) => $w->where('order_messages.user_id', $viewer->id)->orWhereRaw('order_messages.id <= ' . $this->marker($viewer)));
    }

    public function unreadCount(User $viewer): int
    {
        return $this->onlyUnread(OrderMessage::query()->where('order_messages.kind', 'text')
            ->whereIn('order_messages.order_id', Order::visibleTo($viewer)->select('orders.id')), $viewer)->count();
    }

    /** This person has now read this message (and everything before it) in its order. */
    public function markRead(OrderMessage $message, User $viewer): void
    {
        $state = OrderReadState::firstOrCreate(['order_id' => $message->order_id, 'user_id' => $viewer->id]);
        if ((int) $state->last_read_message_id < $message->id) {
            $state->forceFill(['last_read_message_id' => $message->id])->save();
        }
    }

    /** Make this message (and the ones after it) unread again for this person. */
    public function markUnread(OrderMessage $message, User $viewer): void
    {
        OrderReadState::where('order_id', $message->order_id)->where('user_id', $viewer->id)
            ->where('last_read_message_id', '>=', $message->id)
            ->update(['last_read_message_id' => max(0, $message->id - 1)]);
    }

    /** Everything currently in this person's inbox counts as read. */
    public function markAllRead(User $viewer): int
    {
        $rows = $this->onlyUnread(OrderMessage::query()->where('order_messages.kind', 'text')
            ->whereIn('order_messages.order_id', Order::visibleTo($viewer)->select('orders.id')), $viewer)
            ->select('order_messages.order_id', DB::raw('MAX(order_messages.id) AS last_id'))
            ->groupBy('order_messages.order_id')->get();

        foreach ($rows as $row) {
            $state = OrderReadState::firstOrCreate(['order_id' => $row->order_id, 'user_id' => $viewer->id]);
            $state->forceFill(['last_read_message_id' => max((int) $state->last_read_message_id, (int) $row->last_id)])->save();
        }

        return $rows->count();
    }
}
