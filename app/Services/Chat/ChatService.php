<?php

namespace App\Services\Chat;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderReadState;
use App\Models\User;
use App\Services\Orders\FileStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chat inside one order. Transport is plain HTTP polling for now; the JSON shape produced by toArray()
 * is what a future websocket broadcast can push as well, so the front end will not need to change.
 */
class ChatService
{
    public function __construct(private FileStorage $files, private ChatNotifier $notifier)
    {
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function post(Order $order, User $sender, ?string $body, array $files = []): OrderMessage
    {
        $message = DB::transaction(function () use ($order, $sender, $body, $files) {
            $message = $order->messages()->create(['user_id' => $sender->id, 'body' => $body !== null && trim($body) !== '' ? trim($body) : null]);
            $this->files->storeMany($files, $order, $sender, ['message_id' => $message->id]);

            return $message;
        });

        $this->markRead($order, $sender, $message->id);   // you have obviously read your own message
        $this->notifier->afterMessage($message->load('files', 'user'));

        return $message;
    }

    /** Messages newer than $afterId, oldest first. */
    public function after(Order $order, int $afterId = 0): Collection
    {
        return $order->messages()->with(['user', 'files'])->where('id', '>', $afterId)->get();
    }

    /** Records that $user has read up to the latest message and is on the page right now. */
    public function markRead(Order $order, User $user, ?int $upTo = null): OrderReadState
    {
        $state = OrderReadState::firstOrCreate(['order_id' => $order->id, 'user_id' => $user->id]);
        $latest = $upTo ?? (int) $order->messages()->max('id');
        $state->forceFill([
            'last_read_message_id' => max((int) $state->last_read_message_id, $latest),
            'last_seen_at' => now(),
        ])->save();

        return $state;
    }

    public function unreadCount(Order $order, User $user): int
    {
        $read = (int) OrderReadState::where('order_id', $order->id)->where('user_id', $user->id)->value('last_read_message_id');

        return $order->messages()->where('user_id', '!=', $user->id)->where('id', '>', $read)->count();
    }

    /** "Seen" marker: the highest message id the other side has read. */
    public function otherSideReadUpTo(Order $order, User $viewer): int
    {
        $query = OrderReadState::where('order_id', $order->id)->where('user_id', '!=', $viewer->id);
        $query->whereHas('user', fn ($q) => $q->where('role', $viewer->isAdmin() ? User::ROLE_CUSTOMER : User::ROLE_ADMIN));

        return (int) $query->max('last_read_message_id');
    }

    /** Everything the chat box needs: messages after $after, the "seen" marker and the order status (used for the first paint and every poll). */
    public function feed(Order $order, User $viewer, int $after = 0): array
    {
        return [
            'messages' => $this->after($order, $after)->map(fn (OrderMessage $m) => $this->toArray($m, $viewer))->values()->all(),
            'seen_up_to' => $this->otherSideReadUpTo($order, $viewer),
            'open' => $order->isChatOpen(),
            'status' => $order->status->value,
            'offer' => $this->offer($order, $viewer),
        ];
    }

    /**
     * The current state of this conversation's custom offer (request-origin orders only). Offer cards in the chat
     * are drawn from this on every poll, so a card turns from "Pay now" into "Paid" without a reload.
     */
    public function offer(Order $order, User $viewer): ?array
    {
        if (! $order->isRequestOrigin() || $order->status->isLead() || $order->status === \App\Enums\OrderStatus::Draft) {
            return null;
        }

        return [
            'number' => $order->number,
            'title' => $order->title,
            'total' => money($order->total_cents, $order->currency),
            'items' => $order->items()->count(),
            'due' => $order->due_at?->toIso8601String(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'can_pay' => $viewer->isCustomer() && $order->isPayable(),
            'pay_url' => route('customer.orders.pay', $order),
            'view_url' => $viewer->isAdmin() ? $order->adminUrl() . '#invoice' : $order->customerUrl() . '#invoice',
            'pdf_url' => route('portal.orders.invoice', $order),
        ];
    }

    public function toArray(OrderMessage $m, User $viewer): array
    {
        return [
            'id' => $m->id,
            'kind' => $m->kind ?? 'text',
            'mine' => $m->user_id === $viewer->id,
            'author' => $m->user->name,
            'initials' => $m->user->initials,
            'role' => $m->user->role,
            'body' => $m->body,
            'time' => $m->created_at->toIso8601String(),
            'files' => $m->files->map->toChatArray()->all(),
        ];
    }
}
