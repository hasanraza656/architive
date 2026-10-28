<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A project groups tasks and people. Optionally linked to a customer order (then it can be shown to that customer, read-only). */
class Project extends Model
{
    public const STATUSES = [
        'active' => ['label' => 'Active', 'tone' => 'blue'],
        'on_hold' => ['label' => 'On hold', 'tone' => 'amber'],
        'completed' => ['label' => 'Completed', 'tone' => 'green'],
        'archived' => ['label' => 'Archived', 'tone' => 'grey'],
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'visible_to_customer' => 'boolean',
        'customer_id' => 'integer', 'order_id' => 'integer', 'created_by' => 'integer',
    ];

    /* ------------------------------------------------------------ Relations */

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withPivot('role')->withTimestamps()->orderBy('first_name');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ------------------------------------------------------------ Scopes */

    /** Projects a person may open: everything with projects.view, otherwise the ones they belong to or have a task in. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->isAdmin() || ($user->isStaff() && $user->hasPermission('projects.view'))) {
            return $q;
        }
        if ($user->isStaff()) {
            return $q->where(fn ($w) => $w
                ->whereIn('projects.id', ProjectMember::where('user_id', $user->id)->select('project_id'))
                ->orWhereIn('projects.id', Task::whereIn('id', TaskAssignee::where('user_id', $user->id)->select('task_id'))->select('project_id')));
        }
        if ($user->isCustomer()) {
            return $q->where('visible_to_customer', true)->where('customer_id', $user->id);
        }

        return $q->whereRaw('1 = 0');
    }

    /* ------------------------------------------------------------ Helpers */

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status]['tone'] ?? 'grey';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status !== 'completed' && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /** Done tasks / all tasks as a whole percentage (uses loaded counts when available). */
    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->done_tasks_count ?? $this->tasks()->where('status', 'done')->count();

        return $total ? (int) round($done / $total * 100) : 0;
    }

    public function url(): string
    {
        return route('admin.projects.show', $this);
    }

    public function isLead(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->wherePivot('role', 'lead')->exists();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }
}
