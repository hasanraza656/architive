<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($user->isCustomer()) {
            return $task->project->visible_to_customer && (int) $task->project->customer_id === (int) $user->id;
        }
        if (! $user->isStaff()) {
            return false;
        }

        return $user->hasPermission('tasks.view') || Task::visibleTo($user)->whereKey($task->id)->exists();
    }

    /** Change any field: title, description, due date, assignees, priority. */
    public function update(User $user, Task $task): bool
    {
        return $user->isStaff() && ($user->hasPermission('tasks.edit') || $task->project->isLead($user));
    }

    /** Move through the statuses: also allowed for the people the task is assigned to. */
    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task) || ($user->isStaff() && $task->isAssignedTo($user));
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->isStaff() && $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isStaff() && ($user->hasPermission('tasks.delete') || $task->project->isLead($user));
    }
}
