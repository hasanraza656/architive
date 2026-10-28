<?php

namespace App\Services\Chat;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderChatMember;
use App\Models\OrderMessage;
use App\Models\OrderMessageMention;
use App\Models\OrderMessageReaction;
use App\Models\OrderReadState;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Orders\FileStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chat inside one order, shared by the customer and the team. A conversation can be a group: the customer, every super
 * admin, any team member who was added (or whose role may open every chat). Messages support files, voice, screen
 * recordings, reactions, threads and @person / #task / #project tags. Transport is plain HTTP polling; the JSON shape produced
 * by present() is what a future websocket broadcast can push too.
 */
class ChatService
{
    private array $participantCache = [];

    public function __construct(private FileStorage $files, private ChatNotifier $notifier, private MentionParser $mentions)
    {
    }

    /* ------------------------------------------------------------ people */

    /** Everyone who can take part in this conversation: the customer, super admins, added team members and wide-access staff. */
    public function participants(Order $order): Collection
    {
        return $this->participantCache[$order->id] ??= (function () use ($order) {
            $ids = collect([$order->customer_id])
                ->merge(User::where('role', User::ROLE_ADMIN)->where('is_active', true)->pluck('id'))
                ->merge($order->chatMembers()->pluck('user_id'));

            $users = User::whereIn('id', $ids->unique()->all())->where('is_active', true)->get();
            $wide = User::where('role', User::ROLE_STAFF)->where('is_active', true)->with('teamRole')->get()
                ->filter(fn (User $u) => $u->hasAnyPermission('orders.view', 'chat.access_all'));

            return $users->merge($wide)->unique('id')->values();
        })();
    }

    public function addMember(Order $order, User $member, User $by): bool
    {
        if (! $member->isStaff() || ! $member->is_active || $order->chatMembers()->where('user_id', $member->id)->exists()) {
            return false;
        }
        OrderChatMember::create(['order_id' => $order->id, 'user_id' => $member->id, 'added_by' => $by->id]);
        $this->participantCache = [];
        $this->system($order, $by, $by->id === $member->id ? "{$member->name} joined the conversation" : "{$by->name} added {$member->name} to the conversation");
        $this->notifier->memberAdded($order, $member, $by);

        return true;
    }

    public function removeMember(Order $order, User $member, User $by): bool
    {
        $removed = $order->chatMembers()->where('user_id', $member->id)->delete() > 0;
        if ($removed) {
            $this->participantCache = [];
            $this->system($order, $by, $by->id === $member->id ? "{$member->name} left the conversation" : "{$by->name} removed {$member->name} from the conversation");
        }

        return $removed;
    }

    /** A centred grey line in the timeline ("Aisha added Omar to the conversation"). */
    public function system(Order $order, User $by, string $text): OrderMessage
    {
        return $order->messages()->create(['user_id' => $by->id, 'kind' => 'system', 'body' => $text]);
    }

    /* ------------------------------------------------------------ writing */

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @param  array{reply_to?:int|null,extras?:array<int,array>}  $opts  reply_to = a message of the same order (a reply always lands in that message's thread)
     */
    public function post(Order $order, User $sender, ?string $body, array $files = [], array $opts = []): OrderMessage
    {
        $root = null;
        if (! empty($opts['reply_to'])) {
            $target = OrderMessage::where('order_id', $order->id)->whereIn('kind', ['text'])->find((int) $opts['reply_to']);
            $root = $target ? ($target->thread_root_id ? OrderMessage::find($target->thread_root_id) : $target) : null;
        }

        $body = $body !== null && trim($body) !== '' ? trim($body) : null;
        $tags = [];
        if ($body !== null) {
            [$body, $tags] = $this->mentions->parse($body, $order, $sender, $this->participants($order)->pluck('id')->map(fn ($i) => (int) $i)->all());
        }

        $message = DB::transaction(function () use ($order, $sender, $body, $files, $opts, $root, $tags) {
            $message = $order->messages()->create(['user_id' => $sender->id, 'body' => $body, 'thread_root_id' => $root?->id]);
            $this->files->storeMany($files, $order, $sender, ['message_id' => $message->id], $opts['extras'] ?? []);
            foreach ($tags as $t) {
                OrderMessageMention::create(['message_id' => $message->id, 'type' => $t['type'], 'target_id' => $t['id']]);
            }
            $root?->touch();            // the thread summary on the root message changes: pollers pick the root up as "updated"

            return $message;
        });

        $this->markRead($order, $sender, $message->id);   // you have obviously read your own message
        $this->notifier->afterMessage($message->load('files', 'user', 'mentions'));

        return $message;
    }

    /** Toggle one reaction. Returns the message as the viewer should now see it. */
    public function react(OrderMessage $message, User $user, string $emoji): array
    {
        abort_unless(in_array($emoji, config('portal.reactions'), true), 422, 'That reaction is not available.');
        $existing = OrderMessageReaction::where(['message_id' => $message->id, 'user_id' => $user->id, 'emoji' => $emoji])->first();
        $existing ? $existing->delete() : OrderMessageReaction::create(['message_id' => $message->id, 'user_id' => $user->id, 'emoji' => $emoji, 'created_at' => now()]);
        $message->touch();
        $message->root?->touch();

        return $this->present(collect([$message->fresh()]), $user)[0];
    }

    /* ------------------------------------------------------------ reading */

    /** Messages newer than $afterId in the main timeline, oldest first (kept for callers that only need the plain list). */
    public function after(Order $order, int $afterId = 0): Collection
    {
        return $order->messages()->timeline()->with(['user', 'files'])->where('id', '>', $afterId)->get();
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

    /** "Seen" marker: the highest message id any other participant has read. */
    public function otherSideReadUpTo(Order $order, User $viewer): int
    {
        return (int) OrderReadState::where('order_id', $order->id)->where('user_id', '!=', $viewer->id)->max('last_read_message_id');
    }

    /**
     * Everything the chat box needs for the first paint and every poll.
     *
     * @param  array{after?:int,before?:int,limit?:int,since?:string|null,thread?:int,thread_after?:int}  $q
     */
    public function feed(Order $order, User $viewer, array $q = []): array
    {
        $after = (int) ($q['after'] ?? 0);
        $before = (int) ($q['before'] ?? 0);
        $limit = max(1, min(100, (int) ($q['limit'] ?? 30)));
        $since = ! empty($q['since']) ? $this->date($q['since']) : null;

        $base = $order->messages()->timeline()->with(['user', 'files', 'reactions.user', 'mentions']);
        $hasMore = false;

        if ($after > 0) {
            $new = (clone $base)->where('id', '>', $after)->orderBy('id')->get();
        } elseif ($before > 0) {
            $page = (clone $base)->where('id', '<', $before)->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $page->count() > $limit;
            $new = $page->take($limit)->reverse()->values();
        } else {
            $page = (clone $base)->orderByDesc('id')->limit($limit + 1)->get();
            $hasMore = $page->count() > $limit;
            $new = $page->take($limit)->reverse()->values();
        }

        $updated = collect();
        if ($since && $after > 0) {
            $updated = (clone $base)->where('id', '<=', $after)->where('updated_at', '>', $since)->orderBy('id')->get();
        }

        $out = [
            'messages' => $this->present($new, $viewer),
            'updated' => $this->present($updated, $viewer),
            'has_more' => $hasMore,
            'seen_up_to' => $this->otherSideReadUpTo($order, $viewer),
            'open' => $order->isChatOpen(),
            'status' => $order->status->value,
            'offer' => $this->offer($order, $viewer),
            'now' => now()->toIso8601String(),
        ];

        if (! empty($q['thread'])) {
            $out['thread'] = $this->thread($order, (int) $q['thread'], $viewer, (int) ($q['thread_after'] ?? 0), $since);
        }

        return $out;
    }

    /** A thread: the root message and its replies (all of them, or only the ones after $afterId plus changed ones when polling). */
    public function thread(Order $order, int $rootId, User $viewer, int $afterId = 0, ?\Carbon\Carbon $since = null): ?array
    {
        $root = OrderMessage::where('order_id', $order->id)->whereNull('thread_root_id')->with(['user', 'files', 'reactions.user', 'mentions'])->find($rootId);
        if (! $root) {
            return null;
        }
        $replies = $root->replies()->with(['user', 'files', 'reactions.user', 'mentions']);
        $new = $afterId > 0 ? (clone $replies)->where('id', '>', $afterId)->orderBy('id')->get() : $replies->orderBy('id')->get();
        $changed = ($since && $afterId > 0) ? $root->replies()->with(['user', 'files', 'reactions.user', 'mentions'])->where('id', '<=', $afterId)->where('updated_at', '>', $since)->get() : collect();

        return [
            'root' => $this->present(collect([$root]), $viewer)[0],
            'replies' => $this->present($new, $viewer),
            'updated' => $this->present($changed, $viewer),
        ];
    }

    /**
     * The current state of this conversation's custom offer (request-origin orders only). Offer cards in the chat
     * are drawn from this on every poll, so a card turns from "Pay now" into "Paid" without a reload.
     */
    public function offer(Order $order, User $viewer): ?array
    {
        if (! $order->isRequestOrigin() || $order->status->isLead() || $order->status === OrderStatus::Draft) {
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
            'view_url' => $viewer->isTeam() ? $order->adminUrl() . '#invoice' : $order->customerUrl() . '#invoice',
            'pdf_url' => route('portal.orders.invoice', $order),
        ];
    }

    /* ------------------------------------------------------------ JSON shape */

    /**
     * Turns messages into the arrays the chat script draws. Batch version: thread summaries and tag permissions are
     * looked up once for the whole list.
     *
     * @return array<int, array<string,mixed>>
     */
    public function present(Collection $messages, User $viewer): array
    {
        if ($messages->isEmpty()) {
            return [];
        }
        $messages = new \Illuminate\Database\Eloquent\Collection($messages->all());
        $messages->loadMissing(['user', 'files', 'reactions.user', 'mentions']);

        $threads = OrderMessage::whereIn('thread_root_id', $messages->pluck('id'))
            ->select('thread_root_id', DB::raw('COUNT(*) AS c'), DB::raw('MAX(created_at) AS last_at'))->groupBy('thread_root_id')->get()->keyBy('thread_root_id');
        $threadAuthors = OrderMessage::whereIn('thread_root_id', $messages->pluck('id'))->with('user')->get()->groupBy('thread_root_id');
        $access = [];

        return $messages->map(function (OrderMessage $m) use ($viewer, $threads, $threadAuthors, &$access) {
            $author = $m->user;
            $thread = $threads->get($m->id);
            $reactions = $m->reactions->groupBy('emoji')->map(fn (Collection $rows, string $emoji) => [
                'emoji' => $emoji,
                'count' => $rows->count(),
                'mine' => $rows->contains(fn ($r) => (int) $r->user_id === (int) $viewer->id),
                'users' => $rows->map(fn ($r) => $r->user?->first_name ?: '?')->take(8)->values()->all(),
            ])->values()->all();

            $tags = [];
            foreach ($m->mentions as $t) {
                $key = $t->type . ':' . $t->target_id;
                $access[$key] ??= $this->canOpen($t->type, (int) $t->target_id, $viewer);
                $tags[$key] = ['type' => $t->type, 'id' => (int) $t->target_id, 'open' => $access[$key]];
            }

            return [
                'id' => $m->id,
                'kind' => $m->kind ?? 'text',
                'mine' => (int) $m->user_id === (int) $viewer->id,
                'author' => $author?->name ?? 'Someone',
                'first' => $author?->first_name ?? '',
                'initials' => $author?->initials ?? '?',
                'avatar' => $author?->avatarUrl(),
                'role' => $author?->isTeam() ? 'admin' : 'customer',      // "admin" = our side, kept for the existing styles
                'user_id' => $author?->id,
                'body' => $m->body,
                'time' => $m->created_at->toIso8601String(),
                'edited' => (bool) $m->edited_at,
                'files' => $m->files->map->toChatArray()->all(),
                'reactions' => $reactions,
                'tags' => array_values($tags),
                'reply_to' => $m->thread_root_id,
                'thread' => $thread ? [
                    'count' => (int) $thread->c,
                    'last' => \Illuminate\Support\Carbon::parse($thread->last_at)->toIso8601String(),
                    'people' => ($threadAuthors->get($m->id) ?? collect())->pluck('user')->filter()->unique('id')->take(3)->map(fn (User $u) => ['initials' => $u->initials, 'avatar' => $u->avatarUrl(), 'name' => $u->name])->values()->all(),
                ] : null,
            ];
        })->values()->all();
    }

    /** Can this person open the tagged task/project (used to turn a tag into a clickable chip or a grey one). */
    private function canOpen(string $type, int $id, User $viewer): bool
    {
        return match ($type) {
            'task' => ($t = Task::with('project')->find($id)) ? $viewer->can('view', $t) : false,
            'project' => ($p = Project::find($id)) ? $viewer->can('view', $p) : false,
            default => true,
        };
    }

    /** Kept for existing callers (inbox quick reply etc.). */
    public function toArray(OrderMessage $m, User $viewer): array
    {
        return $this->present(collect([$m]), $viewer)[0];
    }

    private function date(string $iso): ?\Carbon\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::parse($iso);
        } catch (\Throwable) {
            return null;
        }
    }
}
