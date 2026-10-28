<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Who is in a conversation. Everyone who can open it sees the list; people with the "manage members" permission add and remove team members. */
class ChatMemberController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    /** HTML for the "People in this chat" drawer. */
    public function index(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $me = $request->user();

        $people = $this->chat->participants($order);
        $memberIds = $order->chatMembers()->pluck('user_id')->all();
        $canManage = $me->can('manageMembers', $order);

        return view('portal.shared.chat-members', [
            'order' => $order,
            'people' => $people,
            'memberIds' => $memberIds,
            'canManage' => $canManage,
            'candidates' => $canManage ? User::where('role', User::ROLE_STAFF)->where('is_active', true)->whereNotIn('id', $people->pluck('id'))->with('teamRole')->orderBy('first_name')->get() : collect(),
            'viewer' => $me,
        ]);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $this->authorize('manageMembers', $order);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $member = User::findOrFail($data['user_id']);
        abort_unless($member->isStaff(), 422, 'Only team members can be added to a conversation.');
        $this->chat->addMember($order, $member, $request->user());

        return response()->json(['ok' => true, 'count' => $this->chat->participants($order)->count()]);
    }

    public function destroy(Request $request, Order $order, User $user): JsonResponse
    {
        // anyone can leave a chat they were added to; removing others needs the permission
        if ($user->id !== $request->user()->id) {
            $this->authorize('manageMembers', $order);
        } else {
            $this->authorize('view', $order);
        }
        $this->chat->removeMember($order, $user, $request->user());

        return response()->json(['ok' => true, 'count' => $this->chat->participants($order)->count()]);
    }
}
