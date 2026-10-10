<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Chat\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin: every chat message from every order in one list (filters, read/unread, quick view, jump to the message). */
class InboxController extends Controller
{
    public function __construct(private Inbox $inbox)
    {
    }

    public function index(Request $request)
    {
        $f = [
            'state' => in_array($request->query('state'), ['unread', 'read'], true) ? $request->query('state') : 'all',
            'from' => in_array($request->query('from'), ['team', 'all'], true) ? $request->query('from') : 'customers',
            'customer' => (int) $request->query('customer'),
            'order' => trim((string) $request->query('order')),
            'q' => trim((string) $request->query('q')),
            'date_from' => $this->date($request->query('date_from')),
            'date_to' => $this->date($request->query('date_to')),
        ];

        $query = $this->inbox->query()
            ->when($f['from'] === 'customers', fn ($q) => $q->whereRaw("order_messages.user_id IN (SELECT id FROM users WHERE role = 'customer')"))
            ->when($f['from'] === 'team', fn ($q) => $q->whereRaw("order_messages.user_id IN (SELECT id FROM users WHERE role = 'admin')"))
            ->when($f['customer'], fn ($q) => $q->whereHas('order', fn ($o) => $o->where('customer_id', $f['customer'])))
            ->when($f['order'] !== '', fn ($q) => $q->whereHas('order', fn ($o) => $o->where(fn ($w) => $w
                ->where('number', 'like', '%' . $f['order'] . '%')->orWhere('title', 'like', '%' . $f['order'] . '%'))))
            ->when($f['q'] !== '', fn ($q) => $q->where('order_messages.body', 'like', '%' . $f['q'] . '%'))
            ->when($f['date_from'], fn ($q) => $q->whereDate('order_messages.created_at', '>=', $f['date_from']))
            ->when($f['date_to'], fn ($q) => $q->whereDate('order_messages.created_at', '<=', $f['date_to']));

        if ($f['state'] === 'unread') {
            $this->inbox->onlyUnread($query);
        } elseif ($f['state'] === 'read') {
            $this->inbox->onlyRead($query);
        }

        $messages = $query->orderByDesc('order_messages.id')->paginate(25)->withQueryString();

        return view('portal.admin.inbox', [
            'messages' => $messages,
            'f' => $f,
            'unread' => $this->inbox->unreadCount(),
            'customers' => User::customers()->whereHas('orders')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'filtered' => $request->hasAny(['state', 'from', 'customer', 'order', 'q', 'date_from', 'date_to']),
        ]);
    }

    /** Quick view opened: this message counts as read (without silently reading the ones after it). */
    public function markRead(Request $request, OrderMessage $message): JsonResponse
    {
        $this->inbox->markRead($message, $request->user());

        return response()->json(['ok' => true, 'unread' => $this->inbox->unreadCount()]);
    }

    public function markUnread(Request $request, OrderMessage $message): RedirectResponse|JsonResponse
    {
        abort_unless($message->user?->isCustomer(), 422, 'Only customer messages can be marked unread.');
        $this->inbox->markUnread($message);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'unread' => $this->inbox->unreadCount()])
            : back()->with('success', 'Marked as unread.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $n = $this->inbox->markAllRead($request->user());

        return back()->with('success', $n ? 'All messages marked as read.' : 'Nothing was unread.');
    }

    private function date(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
