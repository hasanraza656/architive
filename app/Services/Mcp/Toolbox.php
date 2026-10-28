<?php

namespace App\Services\Mcp;

use App\Http\Controllers\Admin\TaskController;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Chat\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Everything an admin's AI assistant can do through MCP. Each tool is a small, validated action that runs AS the person who
 * owns the access key and goes through the same permission checks (policies / gates) and the same code as the portal screens
 * (tasks use TaskController, chat replies use ChatService), so nothing is possible here that the person could not do by hand.
 *
 * Deliberately NOT offered: deleting anything, invoices / offers / payments / deliveries, customers, roles and settings.
 */
class Toolbox
{
    private const MAX_LIST = 100;

    public function __construct(private User $user, private int $tokenId = 0)
    {
    }

    /** @return array<string, array{description: string, schema: array, write: bool, handler: callable}> */
    public function tools(): array
    {
        $id = fn (string $what) => ['type' => 'integer', 'description' => $what];
        $str = fn (string $what) => ['type' => 'string', 'description' => $what];
        $date = fn (string $what) => ['type' => ['string', 'null'], 'description' => $what . ' Format YYYY-MM-DD.'];
        $ids = fn (string $what) => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => $what];
        $limit = ['type' => 'integer', 'description' => 'How many rows to return (default 25, max 100).'];
        $rid = ['type' => 'string', 'description' => 'Optional. A unique text you make up for this action; if the same request_id is sent again within 10 minutes the action is NOT repeated (protects against double sends).'];
        $taskStatus = ['type' => 'string', 'enum' => array_keys(Task::STATUSES), 'description' => 'backlog, todo, in_progress, review or done.'];
        $priority = ['type' => 'string', 'enum' => array_keys(Task::PRIORITIES), 'description' => 'low, normal, high or urgent.'];
        $projectStatus = ['type' => 'string', 'enum' => array_keys(Project::STATUSES), 'description' => 'active, on_hold, completed or archived.'];
        $obj = fn (array $props = [], array $required = []) => ['type' => 'object', 'properties' => (object) $props, 'required' => $required, 'additionalProperties' => false];

        return [
            /* ------------------------------------------------------------ reading */
            'whoami' => [
                'description' => 'Who you are acting as in the portal, and what is waiting for you (unread customer messages, your open and overdue tasks). Call this first.',
                'schema' => $obj(), 'write' => false, 'handler' => fn (array $a) => $this->whoami(),
            ],
            'list_team_members' => [
                'description' => 'People on the team (not customers) with their ids. Use these ids to assign tasks and projects.',
                'schema' => $obj(['q' => $str('Optional: part of a name or e-mail.')]), 'write' => false, 'handler' => fn (array $a) => $this->teamMembers($a),
            ],
            'list_customers' => [
                'description' => 'Customers with their ids, e-mail and number of orders. Needs the customers permission.',
                'schema' => $obj(['q' => $str('Optional: part of a name or e-mail.'), 'limit' => $limit]), 'write' => false, 'handler' => fn (array $a) => $this->customers($a),
            ],
            'list_projects' => [
                'description' => 'Projects with progress. Newest first.',
                'schema' => $obj(['status' => $projectStatus, 'q' => $str('Optional: part of the project name.'), 'limit' => $limit]), 'write' => false, 'handler' => fn (array $a) => $this->projects($a),
            ],
            'get_project' => [
                'description' => 'One project: description, dates, members and all its tasks grouped by status.',
                'schema' => $obj(['project_id' => $id('Project id from list_projects.')], ['project_id']), 'write' => false, 'handler' => fn (array $a) => $this->project($a),
            ],
            'list_tasks' => [
                'description' => 'Tasks you may see. By default only open tasks (not done), earliest due date first. Combine filters freely.',
                'schema' => $obj([
                    'project_id' => $id('Only this project.'), 'status' => $taskStatus, 'priority' => $priority,
                    'assigned_to' => $id('Only tasks assigned to this user id (see list_team_members).'),
                    'assigned_to_me' => ['type' => 'boolean', 'description' => 'Only tasks assigned to you.'],
                    'overdue' => ['type' => 'boolean', 'description' => 'Only open tasks whose due date has passed.'],
                    'due_before' => $date('Only tasks due on or before this date.'), 'due_after' => $date('Only tasks due on or after this date.'),
                    'include_done' => ['type' => 'boolean', 'description' => 'Also list finished tasks.'], 'q' => $str('Optional: part of the task title.'), 'limit' => $limit,
                ]), 'write' => false, 'handler' => fn (array $a) => $this->tasks($a),
            ],
            'get_task' => [
                'description' => 'One task with its description, assignees, due date, status and recent activity and comments.',
                'schema' => $obj(['task_id' => $id('Task id from list_tasks.')], ['task_id']), 'write' => false, 'handler' => fn (array $a) => $this->task($a),
            ],
            'list_orders' => [
                'description' => 'Customer orders and requests (each has a chat). Newest first. Prices only appear if your role may see them.',
                'schema' => $obj([
                    'q' => $str('Optional: part of the order number or title.'), 'status' => ['type' => 'string', 'enum' => ['request', 'pending', 'active', 'delivered', 'completed', 'cancelled'], 'description' => 'Optional status.'],
                    'customer_id' => $id('Only this customer.'), 'limit' => $limit,
                ]), 'write' => false, 'handler' => fn (array $a) => $this->orders($a),
            ],
            'get_order' => [
                'description' => 'One order or request: customer, brief, status, dates and linked projects.',
                'schema' => $obj(['order_number' => $str('For example ARC-1005.')], ['order_number']), 'write' => false, 'handler' => fn (array $a) => $this->order($a),
            ],
            'list_customer_messages' => [
                'description' => 'The inbox: recent chat messages across all orders, newest first. Defaults to unread messages from customers. Does NOT mark anything as read.',
                'schema' => $obj([
                    'state' => ['type' => 'string', 'enum' => ['unread', 'all'], 'description' => 'unread (default) or all.'],
                    'from' => ['type' => 'string', 'enum' => ['customers', 'team', 'all'], 'description' => 'customers (default), team or all.'],
                    'order_number' => $str('Only this order.'), 'q' => $str('Only messages containing this text.'), 'limit' => $limit,
                ]), 'write' => false, 'handler' => fn (array $a) => $this->inbox($a),
            ],
            'get_conversation' => [
                'description' => 'The chat of one order, oldest to newest (the latest messages first loaded). Page back with before_message_id. Read this before replying to a customer.',
                'schema' => $obj([
                    'order_number' => $str('For example ARC-1005.'), 'limit' => ['type' => 'integer', 'description' => 'Messages to return (default 20, max 50).'],
                    'before_message_id' => $id('Return the messages before this message id (older ones).'),
                    'mark_as_read' => ['type' => 'boolean', 'description' => 'Also mark the conversation as read for you (default false).'],
                ], ['order_number']), 'write' => false, 'handler' => fn (array $a) => $this->conversation($a),
            ],

            /* ------------------------------------------------------------ writing */
            'create_project' => [
                'description' => 'Create a project. You become its lead. Optionally link it to a customer order and add team members.',
                'schema' => $obj([
                    'name' => $str('Project name.'), 'description' => $str('Optional description.'), 'status' => $projectStatus, 'color' => $str('Optional colour like #FFD60A.'),
                    'start_date' => $date('Optional start.'), 'due_date' => $date('Optional due date (not before the start).'),
                    'order_number' => $str('Optional order to link, e.g. ARC-1005.'),
                    'visible_to_customer' => ['type' => 'boolean', 'description' => 'Show the project (read-only) to the order\'s customer. Only works with order_number. Default false.'],
                    'member_ids' => $ids('Optional team member ids to add (see list_team_members).'), 'request_id' => $rid,
                ], ['name']), 'write' => true, 'handler' => fn (array $a) => $this->createProject($a),
            ],
            'update_project' => [
                'description' => 'Change a project. Send only what should change; everything else stays. Use null for start_date/due_date/description to clear them.',
                'schema' => $obj([
                    'project_id' => $id('Project id.'), 'name' => $str('New name.'), 'description' => ['type' => ['string', 'null']], 'status' => $projectStatus, 'color' => $str('Colour like #FFD60A.'),
                    'start_date' => $date('New start.'), 'due_date' => $date('New due date.'), 'visible_to_customer' => ['type' => 'boolean', 'description' => 'Show to the linked customer (needs a linked order).'],
                ], ['project_id']), 'write' => true, 'handler' => fn (array $a) => $this->updateProject($a),
            ],
            'set_project_members' => [
                'description' => 'Add or remove team members of a project, or choose its lead. Nobody else is touched.',
                'schema' => $obj([
                    'project_id' => $id('Project id.'), 'add_user_ids' => $ids('Team member ids to add.'), 'remove_user_ids' => $ids('Team member ids to remove (the lead cannot be removed; pick another lead first).'),
                    'lead_id' => $id('Make this member the lead (added if needed).'),
                ], ['project_id']), 'write' => true, 'handler' => fn (array $a) => $this->projectMembers($a),
            ],
            'create_task' => [
                'description' => 'Add a task to a project. Assignees get an e-mail.',
                'schema' => $obj([
                    'project_id' => $id('Project id.'), 'title' => $str('Task title.'), 'description' => $str('Optional details.'), 'status' => $taskStatus, 'priority' => $priority,
                    'due_date' => $date('Optional due date.'), 'assignee_ids' => $ids('Optional team member ids to assign.'), 'request_id' => $rid,
                ], ['project_id', 'title']), 'write' => true, 'handler' => fn (array $a) => $this->createTask($a),
            ],
            'update_task' => [
                'description' => 'Change a task: title, description, priority, due date and/or status. Send only what should change. Use null for due_date to remove it. To change who it is assigned to use assign_task.',
                'schema' => $obj([
                    'task_id' => $id('Task id.'), 'title' => $str('New title.'), 'description' => ['type' => ['string', 'null']], 'status' => $taskStatus, 'priority' => $priority, 'due_date' => $date('New due date.'),
                ], ['task_id']), 'write' => true, 'handler' => fn (array $a) => $this->updateTask($a),
            ],
            'set_task_status' => [
                'description' => 'Move one task to another status (backlog, todo, in_progress, review, done).',
                'schema' => $obj(['task_id' => $id('Task id.'), 'status' => $taskStatus], ['task_id', 'status']), 'write' => true,
                'handler' => fn (array $a) => $this->updateTask(['task_id' => $a['task_id'] ?? null, 'status' => $a['status'] ?? null]),
            ],
            'assign_task' => [
                'description' => 'Change who a task is assigned to. Use add_user_ids / remove_user_ids to adjust, or set_user_ids to replace everybody. Newly assigned people get an e-mail.',
                'schema' => $obj([
                    'task_id' => $id('Task id.'), 'add_user_ids' => $ids('Add these team members.'), 'remove_user_ids' => $ids('Remove these team members.'),
                    'set_user_ids' => $ids('Replace all assignees with exactly these (empty list = unassign everybody). Do not combine with add/remove.'),
                ], ['task_id']), 'write' => true, 'handler' => fn (array $a) => $this->assignTask($a),
            ],
            'add_task_comment' => [
                'description' => 'Write a comment on a task (visible to the team, not to customers).',
                'schema' => $obj(['task_id' => $id('Task id.'), 'comment' => $str('The comment text.')], ['task_id', 'comment']), 'write' => true, 'handler' => fn (array $a) => $this->taskComment($a),
            ],
            'reply_to_customer' => [
                'description' => 'Send a chat message in an order\'s conversation AS YOU. The customer sees it immediately and may get an e-mail, so read get_conversation first and write exactly what should be sent. Use reply_to_message_id to answer inside a thread.',
                'schema' => $obj([
                    'order_number' => $str('For example ARC-1005.'), 'message' => $str('The message text (max 5000 characters).'),
                    'reply_to_message_id' => $id('Optional: a message id of this conversation to reply to in a thread.'), 'request_id' => $rid,
                ], ['order_number', 'message']), 'write' => true, 'handler' => fn (array $a) => $this->reply($a),
            ],
            'mark_conversation_read' => [
                'description' => 'Mark an order\'s conversation as read for you.',
                'schema' => $obj(['order_number' => $str('For example ARC-1005.')], ['order_number']), 'write' => true, 'handler' => fn (array $a) => $this->markRead($a),
            ],
            'set_chat_members' => [
                'description' => 'Add or remove team members in an order\'s chat (group chat). Customers cannot be added or removed here.',
                'schema' => $obj([
                    'order_number' => $str('For example ARC-1005.'), 'add_user_ids' => $ids('Team member ids to add.'), 'remove_user_ids' => $ids('Team member ids to remove.'),
                ], ['order_number']), 'write' => true, 'handler' => fn (array $a) => $this->chatMembers($a),
            ],
        ];
    }

    /** Run one tool. Returns the data for the assistant; problems become ToolError / ValidationException for the server to report. */
    public function call(string $name, array $args): array
    {
        $tool = $this->tools()[$name] ?? null;
        if (! $tool) {
            throw new ToolError("Unknown tool “{$name}”.");
        }
        if (! $this->user->is_active || ! $this->user->isTeam()) {
            throw new ToolError('This account is no longer allowed to use the portal.');
        }

        $rid = $tool['write'] && isset($args['request_id']) && is_string($args['request_id']) && $args['request_id'] !== '' ? substr($args['request_id'], 0, 64) : null;
        $key = $rid ? "mcp:rid:{$this->tokenId}:{$name}:" . md5($rid) : null;
        if ($key && ($seen = Cache::get($key))) {
            return $seen + ['duplicate_request_ignored' => true, 'note' => 'This request_id was already processed, nothing was repeated.'];
        }

        $result = ($tool['handler'])($args);

        if ($key) {
            Cache::put($key, $result, now()->addMinutes(10));
        }

        return $result;
    }

    /* ================================================================ reading */

    private function whoami(): array
    {
        $u = $this->user;
        $today = Carbon::today();
        $mine = Task::visibleTo($u)->whereIn('tasks.id', DB::table('task_assignees')->where('user_id', $u->id)->select('task_id'))->open();

        return [
            'user' => $this->person($u) + ['is_super_admin' => $u->isAdmin()],
            'today' => $today->toDateString(),
            'unread_customer_messages' => app(Inbox::class)->unreadCount($u),
            'my_open_tasks' => (clone $mine)->count(),
            'my_overdue_tasks' => (clone $mine)->whereNotNull('due_date')->where('due_date', '<', $today)->count(),
            'task_statuses' => array_keys(Task::STATUSES),
            'task_priorities' => array_keys(Task::PRIORITIES),
            'project_statuses' => array_keys(Project::STATUSES),
        ];
    }

    private function teamMembers(array $a): array
    {
        $a = $this->valid($a, ['q' => 'nullable|string|max:80']);
        $q = trim((string) ($a['q'] ?? ''));
        $rows = User::team()->where('is_active', true)->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
            ->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderBy('first_name')->limit(self::MAX_LIST)->get();

        return ['count' => $rows->count(), 'members' => $rows->map(fn (User $m) => $this->person($m) + ['email' => $m->email, 'job_title' => $m->job_title])->all()];
    }

    private function customers(array $a): array
    {
        $this->need('customers.view');
        $a = $this->valid($a, ['q' => 'nullable|string|max:80', 'limit' => 'nullable|integer|min:1|max:100']);
        $q = trim((string) ($a['q'] ?? ''));
        $limit = $this->limit($a);
        $rows = User::customers()->withCount('orders')->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
            ->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderByDesc('id')->limit($limit + 1)->get();

        return [
            'has_more' => $rows->count() > $limit,
            'customers' => $rows->take($limit)->map(fn (User $c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'orders' => $c->orders_count])->values()->all(),
        ];
    }

    private function projects(array $a): array
    {
        $a = $this->valid($a, ['status' => ['nullable', Rule::in(array_keys(Project::STATUSES))], 'q' => 'nullable|string|max:80', 'limit' => 'nullable|integer|min:1|max:100']);
        $limit = $this->limit($a);
        $rows = Project::visibleTo($this->user)->withCount(['tasks', 'tasks as done_tasks_count' => fn ($t) => $t->where('status', 'done')])->with('customer')
            ->when(! empty($a['status']), fn ($b) => $b->where('status', $a['status']))
            ->when(! empty($a['q']), fn ($b) => $b->where('name', 'like', '%' . $a['q'] . '%'))
            ->orderByDesc('id')->limit($limit + 1)->get();

        return ['has_more' => $rows->count() > $limit, 'projects' => $rows->take($limit)->map(fn (Project $p) => $this->projectRow($p))->values()->all()];
    }

    private function project(array $a): array
    {
        $a = $this->valid($a, ['project_id' => 'required|integer']);
        $p = $this->findProject($a['project_id'], 'view');
        $p->load(['members', 'customer', 'order']);
        $tasks = $p->tasks()->with('assignees')->orderBy('position')->get();

        return $this->projectRow($p->loadCount(['tasks', 'tasks as done_tasks_count' => fn ($t) => $t->where('status', 'done')])) + [
            'description' => $p->description,
            'members' => $p->members->map(fn (User $m) => $this->person($m) + ['project_role' => $m->pivot->role])->all(),
            'tasks_by_status' => collect(Task::STATUSES)->map(fn ($d, $k) => $tasks->where('status', $k)->map(fn (Task $t) => $this->taskRow($t, false))->values()->all())->all(),
        ];
    }

    private function tasks(array $a): array
    {
        $a = $this->valid($a, [
            'project_id' => 'nullable|integer', 'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))], 'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))],
            'assigned_to' => 'nullable|integer', 'assigned_to_me' => 'nullable|boolean', 'overdue' => 'nullable|boolean', 'due_before' => 'nullable|date_format:Y-m-d', 'due_after' => 'nullable|date_format:Y-m-d',
            'include_done' => 'nullable|boolean', 'q' => 'nullable|string|max:120', 'limit' => 'nullable|integer|min:1|max:100',
        ]);
        $limit = $this->limit($a);
        $assignee = ! empty($a['assigned_to_me']) ? $this->user->id : ($a['assigned_to'] ?? null);

        $q = Task::visibleTo($this->user)->with(['project', 'assignees'])
            ->when(! empty($a['project_id']), fn ($b) => $b->where('project_id', $a['project_id']))
            ->when(! empty($a['status']), fn ($b) => $b->where('status', $a['status']))
            ->when(empty($a['status']) && empty($a['include_done']), fn ($b) => $b->where('status', '!=', 'done'))
            ->when(! empty($a['priority']), fn ($b) => $b->where('priority', $a['priority']))
            ->when($assignee, fn ($b) => $b->whereIn('tasks.id', DB::table('task_assignees')->where('user_id', $assignee)->select('task_id')))
            ->when(! empty($a['overdue']), fn ($b) => $b->where('status', '!=', 'done')->whereNotNull('due_date')->where('due_date', '<', Carbon::today()))
            ->when(! empty($a['due_before']), fn ($b) => $b->whereDate('due_date', '<=', $a['due_before']))
            ->when(! empty($a['due_after']), fn ($b) => $b->whereDate('due_date', '>=', $a['due_after']))
            ->when(! empty($a['q']), fn ($b) => $b->where('title', 'like', '%' . $a['q'] . '%'))
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderByDesc('id');

        $rows = $q->limit($limit + 1)->get();

        return ['has_more' => $rows->count() > $limit, 'tasks' => $rows->take($limit)->map(fn (Task $t) => $this->taskRow($t))->values()->all()];
    }

    private function task(array $a): array
    {
        $a = $this->valid($a, ['task_id' => 'required|integer']);
        $t = $this->findTask($a['task_id'], 'view');
        $t->load(['project', 'assignees', 'creator']);
        $activity = $t->comments()->with('user')->latest('id')->limit(20)->get()->reverse()->values();

        return $this->taskRow($t) + [
            'description' => $t->description,
            'created_by' => $t->creator ? $this->person($t->creator) : null,
            'completed_at' => $t->completed_at?->toIso8601String(),
            'activity' => $activity->map(fn ($c) => ['type' => $c->kind, 'by' => $c->user?->name, 'text' => $c->body, 'at' => $c->created_at->toIso8601String()])->all(),
        ];
    }

    private function orders(array $a): array
    {
        $a = $this->valid($a, ['q' => 'nullable|string|max:80', 'status' => 'nullable|in:request,pending,active,delivered,completed,cancelled', 'customer_id' => 'nullable|integer', 'limit' => 'nullable|integer|min:1|max:100']);
        $limit = $this->limit($a);
        $rows = Order::visibleTo($this->user)->with('customer')
            ->when(! empty($a['q']), fn ($b) => $b->where(fn ($w) => $w->where('number', 'like', '%' . $a['q'] . '%')->orWhere('title', 'like', '%' . $a['q'] . '%')))
            ->when(! empty($a['status']), fn ($b) => $b->where('status', $a['status']))
            ->when(! empty($a['customer_id']), fn ($b) => $b->where('customer_id', $a['customer_id']))
            ->orderByDesc('id')->limit($limit + 1)->get();

        return ['has_more' => $rows->count() > $limit, 'orders' => $rows->take($limit)->map(fn (Order $o) => $this->orderRow($o))->values()->all()];
    }

    private function order(array $a): array
    {
        $a = $this->valid($a, ['order_number' => 'required|string|max:40']);
        $o = $this->findOrder($a['order_number'], 'view');
        $o->load('customer');

        return $this->orderRow($o) + [
            'brief' => $this->clip((string) ($o->brief ?? ''), 3000),
            'service' => $o->service ?? null,
            'requested_deadline' => $o->requested_deadline ? Carbon::parse($o->requested_deadline)->toDateString() : null,
            'chat_open' => $o->isChatOpen(),
            'projects' => Project::visibleTo($this->user)->where('order_id', $o->id)->get()->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name, 'status' => $p->status])->all(),
        ];
    }

    private function inbox(array $a): array
    {
        $a = $this->valid($a, ['state' => 'nullable|in:unread,all', 'from' => 'nullable|in:customers,team,all', 'order_number' => 'nullable|string|max:40', 'q' => 'nullable|string|max:120', 'limit' => 'nullable|integer|min:1|max:100']);
        $inbox = app(Inbox::class);
        $limit = $this->limit($a);
        $from = $a['from'] ?? 'customers';

        $q = $inbox->query($this->user)
            ->when($from === 'customers', fn ($b) => $b->whereRaw("order_messages.user_id IN (SELECT id FROM users WHERE role = 'customer')"))
            ->when($from === 'team', fn ($b) => $b->whereRaw("order_messages.user_id IN (SELECT id FROM users WHERE role IN ('admin', 'staff'))"))
            ->when(! empty($a['order_number']), fn ($b) => $b->whereHas('order', fn ($o) => $o->where('number', $a['order_number'])))
            ->when(! empty($a['q']), fn ($b) => $b->where('order_messages.body', 'like', '%' . $a['q'] . '%'));
        if (($a['state'] ?? 'unread') === 'unread') {
            $inbox->onlyUnread($q, $this->user);
        }
        $rows = $q->orderByDesc('order_messages.id')->limit($limit + 1)->get();

        return [
            'has_more' => $rows->count() > $limit,
            'messages' => $rows->take($limit)->map(fn (OrderMessage $m) => [
                'message_id' => $m->id, 'order_number' => $m->order?->number, 'order_title' => $m->order?->title, 'customer' => $m->order?->customer?->name,
                'from' => $m->user?->name, 'from_type' => $m->user?->isTeam() ? 'team' : 'customer', 'unread' => (bool) $m->is_unread,
                'in_thread_of' => $m->thread_root_id, 'sent_at' => $m->created_at->toIso8601String(),
                'text' => $this->clip($this->plain((string) $m->body), 600), 'attachments' => $m->files->map(fn ($f) => $f->original_name ?: 'file')->all(),
            ])->values()->all(),
        ];
    }

    private function conversation(array $a): array
    {
        $a = $this->valid($a, ['order_number' => 'required|string|max:40', 'limit' => 'nullable|integer|min:1|max:50', 'before_message_id' => 'nullable|integer', 'mark_as_read' => 'nullable|boolean']);
        $o = $this->findOrder($a['order_number'], 'view');
        $chat = app(ChatService::class);
        $feed = $chat->feed($o, $this->user, ['limit' => $a['limit'] ?? 20, 'before' => $a['before_message_id'] ?? 0]);
        if (! empty($a['mark_as_read'])) {
            $chat->markRead($o, $this->user);
        }
        $messages = collect($feed['messages']);

        return [
            'order_number' => $o->number, 'order_title' => $o->title, 'chat_open' => $o->isChatOpen(), 'has_older' => (bool) $feed['has_more'],
            'older_before_message_id' => $messages->first()['id'] ?? null,
            'messages' => $messages->map(fn (array $m) => [
                'message_id' => $m['id'], 'from' => $m['author'], 'from_type' => $m['kind'] === 'system' ? 'system' : ($m['role'] === 'admin' ? 'team' : 'customer'),
                'is_me' => $m['mine'], 'sent_at' => $m['time'], 'text' => $this->plain((string) $m['body']),
                'attachments' => collect($m['files'])->map(fn ($f) => trim(($f['name'] ?? 'file') . ' (' . ($f['kind'] ?? 'file') . ')'))->all(),
                'thread_replies' => $m['thread']['count'] ?? 0, 'in_thread_of' => $m['reply_to'],
                'reactions' => collect($m['reactions'])->map(fn ($r) => $r['emoji'] . ' ×' . $r['count'])->all(),
            ])->all(),
        ];
    }

    /* ================================================================ writing */

    private function createProject(array $a): array
    {
        $this->need('projects.create');
        $a = $this->valid($a, [
            'name' => 'required|string|max:160', 'description' => 'nullable|string|max:4000', 'status' => ['nullable', Rule::in(array_keys(Project::STATUSES))], 'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'start_date' => 'nullable|date_format:Y-m-d', 'due_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date', 'order_number' => 'nullable|string|max:40',
            'visible_to_customer' => 'nullable|boolean', 'member_ids' => 'nullable|array|max:50', 'member_ids.*' => 'integer',
        ]);
        $order = ! empty($a['order_number']) ? $this->findOrder($a['order_number'], 'view') : null;
        $members = $this->activeTeamIds($a['member_ids'] ?? []);

        $project = DB::transaction(function () use ($a, $order, $members) {
            $p = Project::create([
                'name' => trim($a['name']), 'description' => $a['description'] ?? null, 'status' => $a['status'] ?? 'active', 'color' => $a['color'] ?? '#FFD60A',
                'start_date' => $a['start_date'] ?? null, 'due_date' => $a['due_date'] ?? null, 'order_id' => $order?->id, 'customer_id' => $order?->customer_id,
                'visible_to_customer' => $order ? ! empty($a['visible_to_customer']) : false, 'created_by' => $this->user->id,
            ]);
            $sync = [$this->user->id => ['role' => 'lead']];
            foreach ($members as $id) {
                $sync[$id] ??= ['role' => 'member'];
            }
            $p->members()->sync($sync);

            return $p;
        });
        $this->log('create_project', ['project' => $project->id]);

        return ['created' => true, 'project' => $this->projectRow($project->fresh('customer')->loadCount(['tasks', 'tasks as done_tasks_count' => fn ($t) => $t->where('status', 'done')]))];
    }

    private function updateProject(array $a): array
    {
        $raw = $a;
        $a = $this->valid($a, [
            'project_id' => 'required|integer', 'name' => 'sometimes|required|string|max:160', 'description' => 'sometimes|nullable|string|max:4000', 'status' => ['sometimes', Rule::in(array_keys(Project::STATUSES))],
            'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'start_date' => 'sometimes|nullable|date_format:Y-m-d', 'due_date' => 'sometimes|nullable|date_format:Y-m-d', 'visible_to_customer' => 'sometimes|boolean',
        ]);
        $p = $this->findProject($a['project_id'], 'update');

        $start = array_key_exists('start_date', $raw) ? $a['start_date'] ?? null : $p->start_date?->toDateString();
        $due = array_key_exists('due_date', $raw) ? $a['due_date'] ?? null : $p->due_date?->toDateString();
        if ($start && $due && $due < $start) {
            throw new ToolError('The due date cannot be before the start date.');
        }

        $changes = [];
        foreach (['name', 'description', 'status', 'color', 'start_date', 'due_date'] as $f) {
            if (array_key_exists($f, $raw)) {
                $changes[$f] = is_string($a[$f] ?? null) && $f === 'name' ? trim($a[$f]) : ($a[$f] ?? null);
            }
        }
        if (array_key_exists('visible_to_customer', $raw)) {
            if (! empty($a['visible_to_customer']) && ! $p->order_id) {
                throw new ToolError('This project is not linked to a customer order, so it cannot be shown to a customer.');
            }
            $changes['visible_to_customer'] = (bool) $a['visible_to_customer'];
        }
        if (! $changes) {
            throw new ToolError('Nothing to change: send at least one field to update.');
        }
        $p->update($changes);
        $this->log('update_project', ['project' => $p->id, 'fields' => array_keys($changes)]);

        return ['updated' => true, 'changed' => array_keys($changes), 'project' => $this->projectRow($p->fresh('customer')->loadCount(['tasks', 'tasks as done_tasks_count' => fn ($t) => $t->where('status', 'done')]))];
    }

    private function projectMembers(array $a): array
    {
        $a = $this->valid($a, ['project_id' => 'required|integer', 'add_user_ids' => 'nullable|array|max:50', 'add_user_ids.*' => 'integer', 'remove_user_ids' => 'nullable|array|max:50', 'remove_user_ids.*' => 'integer', 'lead_id' => 'nullable|integer']);
        $p = $this->findProject($a['project_id'], 'update');
        $add = $this->activeTeamIds($a['add_user_ids'] ?? [], true);
        $remove = array_map('intval', $a['remove_user_ids'] ?? []);
        $lead = $a['lead_id'] ?? null;
        if (! $add && ! $remove && ! $lead) {
            throw new ToolError('Nothing to change: send add_user_ids, remove_user_ids or lead_id.');
        }
        if ($lead) {
            $this->activeTeamIds([$lead], true);
        }

        DB::transaction(function () use ($p, $add, $remove, $lead) {
            $current = $p->members()->get()->mapWithKeys(fn (User $m) => [$m->id => $m->pivot->role])->all();
            $currentLead = array_search('lead', $current, true) ?: 0;
            $newLead = $lead ?: $currentLead;
            if ($newLead && in_array($newLead, $remove, true)) {
                throw new ToolError('The lead cannot be removed. Choose another lead_id in the same call.');
            }
            foreach ($remove as $id) {
                unset($current[$id]);
            }
            foreach ($add as $id) {
                $current[$id] ??= 'member';
            }
            if ($newLead) {
                $current[$newLead] ??= 'member';
                foreach ($current as $id => $role) {
                    $current[$id] = $id === $newLead ? 'lead' : 'member';
                }
            }
            $p->members()->sync(collect($current)->map(fn ($role) => ['role' => $role])->all());
        });
        $this->log('set_project_members', ['project' => $p->id]);

        return ['updated' => true, 'members' => $p->members()->get()->map(fn (User $m) => $this->person($m) + ['project_role' => $m->pivot->role])->all()];
    }

    private function createTask(array $a): array
    {
        $a = $this->valid($a, [
            'project_id' => 'required|integer', 'title' => 'required|string|max:200', 'description' => 'nullable|string|max:6000', 'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))], 'due_date' => 'nullable|date_format:Y-m-d', 'assignee_ids' => 'nullable|array|max:20', 'assignee_ids.*' => 'integer',
        ]);
        $p = $this->findProject($a['project_id'], 'view');
        if (! Gate::forUser($this->user)->allows('addTask', $p)) {
            throw new ToolError('You are not allowed to add tasks to this project (or it is archived).');
        }
        $input = array_filter(['title' => $a['title'], 'description' => $a['description'] ?? null, 'status' => $a['status'] ?? null, 'priority' => $a['priority'] ?? null, 'due_date' => $a['due_date'] ?? null], fn ($v) => $v !== null);
        $input['assignee_ids'] = $this->activeTeamIds($a['assignee_ids'] ?? [], true);

        $res = $this->controller(fn (TaskController $c, Request $r) => $c->store($r, $p), $input);
        $id = $res['id'] ?? null;
        $this->log('create_task', ['task' => $id]);

        return ['created' => true, 'task' => $id ? $this->taskRow(Task::with(['project', 'assignees'])->find($id)) : null];
    }

    private function updateTask(array $a): array
    {
        $raw = $a;
        $a = $this->valid($a, [
            'task_id' => 'required|integer', 'title' => 'sometimes|required|string|max:200', 'description' => 'sometimes|nullable|string|max:6000', 'status' => ['sometimes', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['sometimes', Rule::in(array_keys(Task::PRIORITIES))], 'due_date' => 'sometimes|nullable|date_format:Y-m-d',
        ]);
        $t = $this->findTask($a['task_id'], 'view');
        $input = [];
        foreach (['title', 'description', 'status', 'priority', 'due_date'] as $f) {
            if (array_key_exists($f, $raw)) {
                $input[$f] = $a[$f] ?? null;
            }
        }
        if (! $input) {
            throw new ToolError('Nothing to change: send at least one field to update.');
        }

        $this->controller(fn (TaskController $c, Request $r) => $c->update($r, $t), $input);
        $this->log('update_task', ['task' => $t->id, 'fields' => array_keys($input)]);

        return ['updated' => true, 'changed' => array_keys($input), 'task' => $this->taskRow($t->fresh(['project', 'assignees']))];
    }

    private function assignTask(array $a): array
    {
        $a = $this->valid($a, [
            'task_id' => 'required|integer', 'add_user_ids' => 'nullable|array|max:20', 'add_user_ids.*' => 'integer', 'remove_user_ids' => 'nullable|array|max:20', 'remove_user_ids.*' => 'integer',
            'set_user_ids' => 'nullable|array|max:20', 'set_user_ids.*' => 'integer',
        ]);
        $t = $this->findTask($a['task_id'], 'view');
        $hasSet = array_key_exists('set_user_ids', $a) && $a['set_user_ids'] !== null;
        if ($hasSet && (! empty($a['add_user_ids']) || ! empty($a['remove_user_ids']))) {
            throw new ToolError('Use either set_user_ids, or add_user_ids / remove_user_ids, not both.');
        }
        if (! $hasSet && empty($a['add_user_ids']) && empty($a['remove_user_ids'])) {
            throw new ToolError('Nothing to change: send add_user_ids, remove_user_ids or set_user_ids.');
        }

        $current = $t->assignees()->pluck('users.id')->map(fn ($i) => (int) $i)->all();
        if ($hasSet) {
            $final = $this->activeTeamIds($a['set_user_ids'], true);
        } else {
            $final = array_values(array_unique(array_merge(
                array_diff($current, array_map('intval', $a['remove_user_ids'] ?? [])),
                $this->activeTeamIds($a['add_user_ids'] ?? [], true)
            )));
        }

        $this->controller(fn (TaskController $c, Request $r) => $c->update($r, $t), ['assignee_ids' => $final]);
        $this->log('assign_task', ['task' => $t->id]);

        return ['updated' => true, 'task' => $this->taskRow($t->fresh(['project', 'assignees']))];
    }

    private function taskComment(array $a): array
    {
        $a = $this->valid($a, ['task_id' => 'required|integer', 'comment' => 'required|string|max:3000']);
        $t = $this->findTask($a['task_id'], 'view');
        $this->controller(fn (TaskController $c, Request $r) => $c->comment($r, $t), ['body' => $a['comment']]);
        $this->log('add_task_comment', ['task' => $t->id]);

        return ['commented' => true, 'task_id' => $t->id];
    }

    private function reply(array $a): array
    {
        $a = $this->valid($a, ['order_number' => 'required|string|max:40', 'message' => 'required|string|max:5000', 'reply_to_message_id' => 'nullable|integer']);
        $o = $this->findOrder($a['order_number'], 'view');
        if (! Gate::forUser($this->user)->allows('chat', $o)) {
            throw new ToolError('This conversation is closed or you cannot write in it.');
        }
        if (trim($a['message']) === '') {
            throw new ToolError('The message is empty.');
        }
        $replyTo = $a['reply_to_message_id'] ?? null;
        if ($replyTo && ! OrderMessage::where('order_id', $o->id)->where('kind', 'text')->whereKey($replyTo)->exists()) {
            throw new ToolError("Message {$replyTo} is not a message of order {$o->number}.");
        }

        $m = app(ChatService::class)->post($o, $this->user, $a['message'], [], ['reply_to' => $replyTo]);
        $this->log('reply_to_customer', ['order' => $o->number, 'message' => $m->id]);

        return ['sent' => true, 'order_number' => $o->number, 'message_id' => $m->id, 'sent_at' => $m->created_at->toIso8601String(), 'in_thread_of' => $m->thread_root_id];
    }

    private function markRead(array $a): array
    {
        $a = $this->valid($a, ['order_number' => 'required|string|max:40']);
        $o = $this->findOrder($a['order_number'], 'view');
        app(ChatService::class)->markRead($o, $this->user);

        return ['marked_read' => true, 'order_number' => $o->number];
    }

    private function chatMembers(array $a): array
    {
        $a = $this->valid($a, ['order_number' => 'required|string|max:40', 'add_user_ids' => 'nullable|array|max:20', 'add_user_ids.*' => 'integer', 'remove_user_ids' => 'nullable|array|max:20', 'remove_user_ids.*' => 'integer']);
        $o = $this->findOrder($a['order_number'], 'view');
        if (! Gate::forUser($this->user)->allows('manageMembers', $o)) {
            throw new ToolError('You are not allowed to change who is in this chat.');
        }
        $add = $this->activeTeamIds($a['add_user_ids'] ?? [], true);
        $remove = array_map('intval', $a['remove_user_ids'] ?? []);
        if (! $add && ! $remove) {
            throw new ToolError('Nothing to change: send add_user_ids or remove_user_ids.');
        }
        $chat = app(ChatService::class);
        foreach (User::whereIn('id', $add)->get() as $m) {
            $chat->addMember($o, $m, $this->user);
        }
        foreach (User::team()->whereIn('id', $remove)->get() as $m) {
            $chat->removeMember($o, $m, $this->user);
        }
        $this->log('set_chat_members', ['order' => $o->number]);

        return ['updated' => true, 'people_in_chat' => $chat->participants($o)->map(fn (User $p) => $this->person($p) + ['type' => $p->isTeam() ? 'team' : 'customer'])->values()->all()];
    }

    /* ================================================================ helpers */

    /** Run a portal controller action as this person (same validation, permission checks, activity log and e-mails as the screen). */
    private function controller(callable $call, array $input): array
    {
        $request = Request::create('/mcp', 'POST', $input);
        $request->headers->set('Accept', 'application/json');
        $user = $this->user;
        $request->setUserResolver(fn () => $user);

        $res = $call(app(TaskController::class), $request);

        return $res instanceof JsonResponse ? (array) $res->getData(true) : [];
    }

    /** Active team member ids from a list. With $strict an unknown / inactive / customer id is an error instead of being skipped. */
    private function activeTeamIds(array $ids, bool $strict = false): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (! $ids) {
            return [];
        }
        $ok = User::team()->where('is_active', true)->whereIn('id', $ids)->pluck('id')->map(fn ($i) => (int) $i)->all();
        $bad = array_values(array_diff($ids, $ok));
        if ($bad && $strict) {
            throw new ToolError('These ids are not active team members: ' . implode(', ', $bad) . '. Use list_team_members to find valid ids.');
        }

        return array_values(array_intersect($ids, $ok));
    }

    private function valid(array $args, array $rules): array
    {
        $v = Validator::make($args, $rules);
        if ($v->fails()) {
            throw new ValidationException($v);
        }

        return $v->validated() + array_intersect_key($args, $rules);
    }

    private function need(string $permission): void
    {
        if (! Gate::forUser($this->user)->allows($permission)) {
            throw new ToolError('Your role does not include this permission (' . $permission . ').');
        }
    }

    private function limit(array $a): int
    {
        return max(1, min(self::MAX_LIST, (int) ($a['limit'] ?? 25)));
    }

    private function findProject(int $id, string $ability): Project
    {
        $p = Project::find($id);
        if (! $p || ! Gate::forUser($this->user)->allows('view', $p)) {
            throw new ToolError("No project with id {$id} that you can access. Use list_projects.");
        }
        if ($ability !== 'view' && ! Gate::forUser($this->user)->allows($ability, $p)) {
            throw new ToolError('You are not allowed to change this project.');
        }

        return $p;
    }

    private function findTask(int $id, string $ability): Task
    {
        $t = Task::with('project')->find($id);
        if (! $t || ! Gate::forUser($this->user)->allows($ability, $t)) {
            throw new ToolError("No task with id {$id} that you can access. Use list_tasks.");
        }

        return $t;
    }

    private function findOrder(string $number, string $ability): Order
    {
        $o = Order::where('number', trim($number))->first();
        if (! $o || ! Gate::forUser($this->user)->allows($ability, $o)) {
            throw new ToolError("No order “{$number}” that you can access. Use list_orders.");
        }

        return $o;
    }

    private function person(User $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'role' => $u->isAdmin() ? 'Admin' : ($u->isStaff() ? ($u->teamRole?->name ?? 'Team') : 'Customer')];
    }

    private function projectRow(Project $p): array
    {
        $total = (int) ($p->tasks_count ?? 0);
        $done = (int) ($p->done_tasks_count ?? 0);

        return [
            'id' => $p->id, 'name' => $p->name, 'status' => $p->status, 'start_date' => $p->start_date?->toDateString(), 'due_date' => $p->due_date?->toDateString(),
            'tasks' => $total, 'tasks_done' => $done, 'progress_percent' => $total ? (int) round($done / $total * 100) : 0,
            'customer' => $p->customer?->name, 'order_id' => $p->order_id, 'visible_to_customer' => (bool) $p->visible_to_customer, 'url' => route('admin.projects.show', $p),
        ];
    }

    private function taskRow(Task $t, bool $withProject = true): array
    {
        $overdue = $t->due_date && $t->status !== 'done' && $t->due_date->lt(Carbon::today());

        return [
            'id' => $t->id, 'title' => $t->title, 'status' => $t->status, 'priority' => $t->priority, 'due_date' => $t->due_date?->toDateString(), 'overdue' => (bool) $overdue,
            'assignees' => $t->assignees->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->all(),
        ] + ($withProject ? ['project' => ['id' => $t->project_id, 'name' => $t->project?->name], 'url' => route('admin.tasks.show', $t)] : []);
    }

    private function orderRow(Order $o): array
    {
        $row = [
            'order_number' => $o->number, 'title' => $o->title, 'status' => $o->status->value, 'status_label' => $o->status->label(),
            'customer' => $o->customer ? ['id' => $o->customer->id, 'name' => $o->customer->name, 'email' => $o->customer->email] : null,
            'created_at' => $o->created_at?->toIso8601String(), 'url' => route('admin.orders.show', $o),
        ];
        if (Gate::forUser($this->user)->allows('billing', $o)) {
            $row['total'] = money($o->total());
        }

        return $row;
    }

    /** Chat tags like @[Name](u:3) / #[Title](t:9) as readable text. */
    private function plain(string $body): string
    {
        return preg_replace_callback('/([@#])\[([^\]\n]{1,160})\]\((u|t|p):(\d+)\)/', fn ($m) => $m[1] . $m[2] . ($m[3] === 'u' ? '' : ($m[3] === 't' ? ' (task ' . $m[4] . ')' : ' (project ' . $m[4] . ')')), $body) ?? $body;
    }

    private function clip(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }

    private function log(string $action, array $context): void
    {
        \Log::info('MCP ' . $action, ['user' => $this->user->id, 'token' => $this->tokenId] + $context);
    }
}
