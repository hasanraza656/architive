<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

/** HTML for the side drawer when a #task or #project tag is opened inside a chat. Customers get a read-only view of shared items. */
class CardController extends Controller
{
    public function task(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $me = $request->user();
        $task->load(['project', 'assignees', 'creator']);
        $team = $me->isTeam();

        return view('portal.tasks._panel', [
            'task' => $task, 'mode' => 'drawer',
            'canEdit' => $team && $me->can('update', $task),
            'canStatus' => $team && $me->can('updateStatus', $task),
            'canComment' => $team && $me->can('comment', $task),
            'canDelete' => $team && $me->can('delete', $task),
            'readonly' => ! $team,
            'people' => $team ? User::team()->where('is_active', true)->orderBy('first_name')->get() : collect(),
            'activity' => $team ? $task->comments()->with('user')->get() : collect(),
        ]);
    }

    public function project(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $project->load(['members', 'customer']);
        $tasks = $project->tasks()->with('assignees')->get();

        return view('portal.projects._card', [
            'project' => $project, 'tasks' => $tasks, 'team' => $request->user()->isTeam(),
            'progress' => $tasks->count() ? (int) round($tasks->where('status', 'done')->count() / $tasks->count() * 100) : 0,
        ]);
    }
}
