<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/** Super admins: everything (Gate::before). Team members: by role permission or because they belong to the project. Customers: read-only, only when shared. */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($user->isCustomer()) {
            return $project->visible_to_customer && (int) $project->customer_id === (int) $user->id;
        }
        if (! $user->isStaff()) {
            return false;
        }

        return $user->hasPermission('projects.view') || Project::visibleTo($user)->whereKey($project->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isStaff() && ($user->hasPermission('projects.edit') || $project->isLead($user));
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isStaff() && $user->hasPermission('projects.delete');
    }

    /** Add tasks to this project. */
    public function addTask(User $user, Project $project): bool
    {
        return $user->isStaff() && $this->view($user, $project) && ($user->hasPermission('tasks.create') || $project->isLead($user));
    }
}
