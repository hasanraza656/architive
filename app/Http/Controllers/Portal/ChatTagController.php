<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use App\Models\Task;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Suggestions while typing @ (people in this chat) or # (tasks and projects the person may see). */
class ChatTagController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    public function search(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $me = $request->user();
        $q = trim(mb_strtolower((string) $request->query('q')));

        if ($request->query('kind') === 'people') {
            $items = $this->chat->participants($order)
                ->filter(fn ($u) => $q === '' || str_contains(mb_strtolower($u->name . ' ' . ($me->isTeam() ? $u->email : '')), $q))
                ->sortBy(fn ($u) => [$u->id === $me->id ? 1 : 0, mb_strtolower($u->name)])
                ->take(8)->values()
                ->map(fn ($u) => [
                    'type' => 'user', 'id' => $u->id, 'label' => $u->name ?: $u->email, 'initials' => $u->initials, 'avatar' => $u->avatarUrl(),
                    'sub' => $u->isCustomer() ? 'Customer' : ($u->job_title ?: ($u->isAdmin() ? 'Admin' : ($u->teamRole?->name ?? 'Team'))),
                ])->all();

            return response()->json(['items' => $items]);
        }

        $tasks = Task::visibleTo($me)->with('project')
            ->when($q !== '', fn ($b) => $b->where('title', 'like', '%' . $q . '%'))
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderByRaw('(SELECT customer_id FROM projects WHERE projects.id = tasks.project_id) = ? DESC', [$order->customer_id])
            ->latest('tasks.id')->limit(7)->get()
            ->map(fn (Task $t) => ['type' => 'task', 'id' => $t->id, 'label' => $t->title, 'sub' => $t->project?->name . ' · ' . $t->statusLabel(), 'status' => $t->status]);

        $projects = Project::visibleTo($me)
            ->when($q !== '', fn ($b) => $b->where('name', 'like', '%' . $q . '%'))
            ->orderByRaw('customer_id = ? DESC', [$order->customer_id])->latest('projects.id')->limit(4)->get()
            ->map(fn (Project $p) => ['type' => 'project', 'id' => $p->id, 'label' => $p->name, 'sub' => 'Project · ' . $p->statusLabel(), 'color' => $p->color]);

        return response()->json(['items' => $projects->concat($tasks)->take(10)->values()->all()]);
    }
}
