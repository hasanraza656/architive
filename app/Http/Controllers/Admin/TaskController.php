<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Portal\TaskAssigned;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Tasks: my tasks, task page, side panel, quick edits from the board / panel / chat, comments. */
class TaskController extends Controller
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    /** "My tasks" (or every task you may see with ?scope=all), grouped by when they are due. */
    public function mine(Request $request)
    {
        $me = $request->user();
        $canAll = $me->isAdmin() || $me->hasPermission('tasks.view');
        $scope = $request->query('scope') === 'all' && $canAll ? 'all' : 'mine';
        $q = trim((string) $request->query('q'));
        $projectId = (int) $request->query('project');

        $base = Task::query()->with(['project', 'assignees'])
            ->when($scope === 'all', fn ($b) => $b->visibleTo($me), fn ($b) => $b->whereIn('tasks.id', DB::table('task_assignees')->where('user_id', $me->id)->select('task_id')))
            ->when($q !== '', fn ($b) => $b->where('title', 'like', "%$q%"))
            ->when($projectId, fn ($b) => $b->where('project_id', $projectId));

        $open = (clone $base)->open()->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")->get();
        $done = (clone $base)->where('status', 'done')->where('completed_at', '>=', now()->subDays(14))->latest('completed_at')->limit(15)->get();

        $today = Carbon::today();
        $groups = [
            'overdue' => ['label' => 'Overdue', 'tone' => 'red', 'tasks' => $open->filter(fn (Task $t) => $t->due_date && $t->due_date->lt($today))->values()],
            'today' => ['label' => 'Today', 'tone' => 'amber', 'tasks' => $open->filter(fn (Task $t) => $t->due_date && $t->due_date->isSameDay($today))->values()],
            'week' => ['label' => 'Next 7 days', 'tone' => 'blue', 'tasks' => $open->filter(fn (Task $t) => $t->due_date && $t->due_date->gt($today) && $t->due_date->lte($today->copy()->addDays(7)))->values()],
            'later' => ['label' => 'Later', 'tone' => 'grey', 'tasks' => $open->filter(fn (Task $t) => $t->due_date && $t->due_date->gt($today->copy()->addDays(7)))->values()],
            'nodate' => ['label' => 'No due date', 'tone' => 'grey', 'tasks' => $open->filter(fn (Task $t) => ! $t->due_date)->values()],
        ];

        return view('portal.admin.tasks.mine', [
            'groups' => $groups, 'done' => $done, 'scope' => $scope, 'canAll' => $canAll, 'q' => $q, 'projectId' => $projectId,
            'projects' => ($projects = Project::visibleTo($me)->orderBy('name')->get()),
            'addable' => $projects->filter(fn (Project $p) => $p->status !== 'archived' && $me->can('addTask', $p))->values(),
            'total' => $open->count(),
        ]);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return view('portal.admin.tasks.show', ['task' => $task] + $this->panelData($request->user(), $task));
    }

    /** HTML for the side drawer (used on the board, My tasks and in chats). */
    public function panel(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return view('portal.tasks._panel', ['task' => $task, 'mode' => 'drawer'] + $this->panelData($request->user(), $task));
    }

    public function store(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $this->authorize('addTask', $project);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:6000'],
            'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'staff'])],
        ], ['title.required' => 'Give the task a title.']);

        $status = $data['status'] ?? 'todo';
        $task = $project->tasks()->create([
            'title' => trim($data['title']), 'description' => $data['description'] ?? null, 'status' => $status,
            'priority' => $data['priority'] ?? 'normal', 'due_date' => $data['due_date'] ?? null,
            'position' => (int) $project->tasks()->where('status', $status)->max('position') + 1,
            'created_by' => $request->user()->id, 'completed_at' => $status === 'done' ? now() : null,
        ]);
        $ids = array_values(array_unique(array_map('intval', $data['assignee_ids'] ?? [])));
        $task->assignees()->sync($ids);
        $this->event($task, $request->user(), 'created this task');
        $this->notifyAssigned($task, $ids, $request->user());

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'id' => $task->id, 'card' => $this->card($task->fresh(['assignees']))])
            : redirect()->route('admin.projects.show', $project)->with('success', 'Task added.');
    }

    /** Quick edit from the panel / board. Send only the fields that changed. */
    public function update(Request $request, Task $task): JsonResponse
    {
        $me = $request->user();
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:6000'],
            'status' => ['sometimes', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['sometimes', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'staff'])],
        ]);

        $editsDetails = (bool) array_intersect(array_keys($data), ['title', 'description', 'priority', 'due_date', 'assignee_ids']);
        if ($editsDetails) {
            $this->authorize('update', $task);
        }
        if (array_key_exists('status', $data)) {
            $this->authorize('updateStatus', $task);
        }

        DB::transaction(function () use ($task, $data, $me) {
            if (array_key_exists('status', $data) && $data['status'] !== $task->status) {
                $this->moveTo($task, $data['status'], $me);
            }
            foreach (['title', 'description'] as $f) {
                if (array_key_exists($f, $data)) {
                    $task->{$f} = $data[$f] === null ? null : trim($data[$f]);
                }
            }
            if (array_key_exists('priority', $data) && $data['priority'] !== $task->priority) {
                $task->priority = $data['priority'];
                $this->event($task, $me, 'set the priority to ' . $task->priorityLabel());
            }
            if (array_key_exists('due_date', $data)) {
                $new = $data['due_date'] ? Carbon::parse($data['due_date'])->toDateString() : null;
                if ($new !== $task->due_date?->toDateString()) {
                    $task->due_date = $new;
                    $this->event($task, $me, $new ? 'set the due date to ' . Carbon::parse($new)->format('M j, Y') : 'removed the due date');
                }
            }
            $task->save();

            if (array_key_exists('assignee_ids', $data)) {
                $this->syncAssignees($task, array_map('intval', $data['assignee_ids']), $me);
            }
        });

        return response()->json($this->present($task->fresh(['project', 'assignees'])));
    }

    /** Board drag and drop: put the task in a column at a position. */
    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateStatus', $task);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Task::STATUSES))], 'index' => ['required', 'integer', 'min:0', 'max:1000']]);

        DB::transaction(function () use ($task, $data, $request) {
            if ($data['status'] !== $task->status) {
                $this->moveTo($task, $data['status'], $request->user());
                $task->save();
            }
            $ids = Task::where('project_id', $task->project_id)->where('status', $data['status'])->where('id', '!=', $task->id)
                ->orderBy('position')->orderBy('id')->pluck('id')->all();
            array_splice($ids, min($data['index'], count($ids)), 0, [$task->id]);
            foreach ($ids as $pos => $id) {
                Task::whereKey($id)->update(['position' => $pos]);
            }
        });

        return response()->json($this->present($task->fresh(['project', 'assignees'])));
    }

    public function destroy(Task $task): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $task);
        $project = $task->project;
        $task->delete();

        return request()->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('admin.projects.show', $project)->with('success', 'Task deleted.');
    }

    public function comment(Request $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']], ['body.required' => 'Write a comment first.']);
        $task->comments()->create(['user_id' => $request->user()->id, 'kind' => 'comment', 'body' => trim($data['body'])]);
        $task->touch();

        return response()->json(['ok' => true, 'activity' => $this->activity($task->fresh())]);
    }

    /* ------------------------------------------------------------ helpers */

    private function panelData(User $me, Task $task): array
    {
        $task->load(['project.members', 'assignees', 'creator']);

        return [
            'canEdit' => $me->can('update', $task),
            'canStatus' => $me->can('updateStatus', $task),
            'canComment' => $me->can('comment', $task),
            'canDelete' => $me->can('delete', $task),
            'readonly' => ! $me->isTeam(),
            'people' => User::team()->where('is_active', true)->orderBy('first_name')->get(),
            'activity' => $task->comments()->with('user')->get(),
        ];
    }

    private function moveTo(Task $task, string $status, User $by): void
    {
        $task->status = $status;
        $task->completed_at = $status === 'done' ? now() : null;
        $task->position = (int) Task::where('project_id', $task->project_id)->where('status', $status)->max('position') + 1;
        $this->event($task, $by, 'moved this to ' . (Task::STATUSES[$status]['label'] ?? $status));
    }

    private function syncAssignees(Task $task, array $ids, User $by): void
    {
        $ids = array_values(array_unique($ids));
        $before = $task->assignees()->pluck('users.id')->all();
        $task->assignees()->sync($ids);
        $added = array_diff($ids, $before);
        $removed = array_diff($before, $ids);
        if ($added) {
            $this->event($task, $by, 'assigned ' . User::whereIn('id', $added)->get()->map->name->implode(', '));
            $this->notifyAssigned($task, $added, $by);
        }
        if ($removed) {
            $this->event($task, $by, 'unassigned ' . User::whereIn('id', $removed)->get()->map->name->implode(', '));
        }
    }

    private function event(Task $task, User $by, string $text): void
    {
        if ($task->exists) {
            $task->comments()->create(['user_id' => $by->id, 'kind' => 'event', 'body' => $text]);
        }
    }

    /** One e-mail per newly assigned person (not to yourself). */
    private function notifyAssigned(Task $task, array $ids, User $by): void
    {
        foreach (User::team()->whereIn('id', $ids)->where('id', '!=', $by->id)->where('is_active', true)->get() as $user) {
            $this->mailer->send($user->email, new TaskAssigned($task->loadMissing('project'), $user, $by));
        }
    }

    private function card(Task $task): string
    {
        return view('portal.tasks._card', ['task' => $task])->render();
    }

    private function activity(Task $task): string
    {
        return view('portal.tasks._activity', ['activity' => $task->comments()->with('user')->get()])->render();
    }

    private function present(Task $task): array
    {
        return [
            'ok' => true,
            'id' => $task->id,
            'status' => $task->status,
            'status_label' => $task->statusLabel(),
            'title' => $task->title,
            'card' => $this->card($task),
            'row' => view('portal.tasks._row', ['task' => $task])->render(),
            'activity' => $this->activity($task),
        ];
    }
}
