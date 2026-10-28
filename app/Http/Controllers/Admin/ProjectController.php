<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Projects: list, board/list view of one project, create, edit, delete. Access is by role permission or project membership. */
class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();
        $status = $request->query('status', 'open');
        $q = trim((string) $request->query('q'));
        $mine = $request->boolean('mine');

        $base = Project::visibleTo($me)
            ->when($mine, fn ($b) => $b->whereIn('projects.id', DB::table('project_members')->where('user_id', $me->id)->select('project_id')))
            ->when($q !== '', fn ($b) => $b->where('name', 'like', "%$q%"));

        $counts = (clone $base)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $projects = $base
            ->when($status === 'open', fn ($b) => $b->whereIn('status', ['active', 'on_hold']))
            ->when(isset(Project::STATUSES[$status]), fn ($b) => $b->where('status', $status))
            ->withCount(['tasks', 'tasks as done_tasks_count' => fn ($t) => $t->where('status', 'done')])
            ->with(['members', 'customer'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'on_hold' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderByDesc('id')
            ->paginate(18)->withQueryString();

        return view('portal.admin.projects.index', [
            'projects' => $projects, 'status' => $status, 'q' => $q, 'mine' => $mine, 'counts' => $counts,
            'form' => $this->formData(), 'canCreate' => $me->can('projects.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('projects.create');
        $data = $this->validated($request);

        $project = DB::transaction(function () use ($request, $data) {
            $project = Project::create($this->fields($data) + ['created_by' => $request->user()->id]);
            $this->syncMembers($project, $data['member_ids'] ?? [], $request->user()->id);

            return $project;
        });

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project created. Add the first task below.');
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $me = $request->user();

        $tasks = $project->tasks()->with(['assignees'])->withCount(['comments' => fn ($c) => $c->where('kind', 'comment')])->get();

        return view('portal.admin.projects.show', [
            'project' => $project->load(['members', 'customer', 'order']),
            'columns' => collect(Task::STATUSES)->map(fn ($def, $key) => $tasks->where('status', $key)->values()),
            'tasks' => $tasks,
            'people' => User::team()->where('is_active', true)->orderBy('first_name')->get(),
            'canEdit' => $me->can('update', $project),
            'canAddTask' => $me->can('addTask', $project),
            'canDelete' => $me->can('delete', $project),
            'form' => $this->formData(),
            'progress' => $tasks->count() ? (int) round($tasks->where('status', 'done')->count() / $tasks->count() * 100) : 0,
            'version' => $this->version($tasks),
        ]);
    }

    /**
     * The board and list as they are right now, for the page to refresh itself. With ?v=<version> the answer is just
     * {changed:false} when nothing happened, so polling stays tiny.
     */
    public function live(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $project);
        $tasks = $project->tasks()->with(['assignees'])->withCount(['comments' => fn ($c) => $c->where('kind', 'comment')])->get();
        $version = $this->version($tasks);
        if ($request->query('v') === $version) {
            return response()->json(['changed' => false, 'v' => $version]);
        }
        $columns = collect(Task::STATUSES)->map(fn ($def, $key) => $tasks->where('status', $key)->values());
        $done = $tasks->where('status', 'done')->count();

        return response()->json([
            'changed' => true, 'v' => $version,
            'cols' => $columns->map(fn ($list) => $list->map(fn (Task $t) => view('portal.tasks._card', ['task' => $t])->render())->implode(''))->all(),
            'list' => view('portal.admin.projects._list', ['columns' => $columns])->render(),
            'total' => $tasks->count(), 'done' => $done, 'progress' => $tasks->count() ? (int) round($done / $tasks->count() * 100) : 0,
        ]);
    }

    /** Changes whenever anything visible on the board or list changes (titles, statuses, order, dates, people, comment counts). */
    private function version($tasks): string
    {
        return md5($tasks->map(fn (Task $t) => implode(':', [
            $t->id, $t->title, $t->status, $t->priority, $t->due_date?->toDateString(), $t->position, $t->comments_count ?? 0, $t->assignees->pluck('id')->sort()->implode(','),
        ]))->implode('|'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $this->validated($request);

        DB::transaction(function () use ($project, $data) {
            $project->update($this->fields($data));
            $this->syncMembers($project, $data['member_ids'] ?? [], (int) ($data['lead_id'] ?? 0));
        });

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project saved.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $name = $project->name;
        $project->delete();      // its tasks and comments go with it

        return redirect()->route('admin.projects.index')->with('success', "Project “{$name}” and its tasks were deleted.");
    }

    /* ------------------------------------------------------------ helpers */

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(array_keys(Project::STATUSES))],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'visible_to_customer' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'staff'])],
            'lead_id' => ['nullable', 'integer'],
        ], ['due_date.after_or_equal' => 'The due date cannot be before the start date.', 'name.required' => 'Give the project a name.']);
    }

    private function fields(array $data): array
    {
        $order = ! empty($data['order_id']) ? Order::find($data['order_id']) : null;

        return [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'color' => $data['color'] ?? '#FFD60A',
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'order_id' => $order?->id,
            'customer_id' => $order?->customer_id,
            'visible_to_customer' => $order ? ! empty($data['visible_to_customer']) : false,
        ];
    }

    /** The chosen people become members; one of them is the lead (defaults to the first). */
    private function syncMembers(Project $project, array $ids, int $leadId): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($leadId && ! in_array($leadId, $ids, true)) {
            $ids[] = $leadId;
        }
        $lead = in_array($leadId, $ids, true) ? $leadId : ($ids[0] ?? 0);
        $sync = [];
        foreach ($ids as $id) {
            $sync[$id] = ['role' => $id === $lead ? 'lead' : 'member'];
        }
        $project->members()->sync($sync);
    }

    private function formData(): array
    {
        return [
            'people' => User::team()->where('is_active', true)->orderBy('first_name')->get(),
            'orders' => Order::with('customer')->where('status', '!=', 'draft')->orderByDesc('id')->limit(200)->get(),
        ];
    }
}
