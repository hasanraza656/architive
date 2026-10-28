<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** Board columns, in order. */
    public const STATUSES = [
        'backlog' => ['label' => 'Backlog', 'tone' => 'grey'],
        'todo' => ['label' => 'To do', 'tone' => 'blue'],
        'in_progress' => ['label' => 'In progress', 'tone' => 'amber'],
        'review' => ['label' => 'In review', 'tone' => 'violet'],
        'done' => ['label' => 'Done', 'tone' => 'green'],
    ];

    public const PRIORITIES = [
        'low' => ['label' => 'Low', 'rank' => 1],
        'normal' => ['label' => 'Normal', 'rank' => 2],
        'high' => ['label' => 'High', 'rank' => 3],
        'urgent' => ['label' => 'Urgent', 'rank' => 4],
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /* ------------------------------------------------------------ Relations */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps()->orderBy('first_name');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ------------------------------------------------------------ Scopes */

    /** Tasks a person may open: all with tasks.view, otherwise assigned to them or inside a project they can open. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->isAdmin() || ($user->isStaff() && $user->hasPermission('tasks.view'))) {
            return $q;
        }
        if ($user->isStaff()) {
            return $q->where(fn ($w) => $w
                ->whereIn('tasks.id', TaskAssignee::where('user_id', $user->id)->select('task_id'))
                ->orWhereIn('tasks.project_id', ProjectMember::where('user_id', $user->id)->select('project_id')));
        }
        if ($user->isCustomer()) {
            return $q->whereIn('tasks.project_id', Project::visibleTo($user)->select('projects.id'));
        }

        return $q->whereRaw('1 = 0');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', '!=', 'done');
    }

    /* ------------------------------------------------------------ Helpers */

    public function number(): string
    {
        return 'T-' . $this->id;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'grey';
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority]['label'] ?? ucfirst($this->priority);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && ! $this->isDone() && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assignees()->where('users.id', $user->id)->exists();
    }

    public function url(): string
    {
        return route('admin.tasks.show', $this);
    }
}
