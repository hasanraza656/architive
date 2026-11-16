<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of a to-do checklist inside a task or a project (owner_type = task | project). */
class ChecklistItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_done' => 'boolean', 'done_at' => 'datetime', 'position' => 'integer'];

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /** Shape sent to the browser (also used to compute the version). */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'done' => (bool) $this->is_done,
            'by' => $this->is_done && $this->doneBy ? ['id' => $this->doneBy->id, 'name' => $this->doneBy->name] : null,
            'at' => $this->is_done ? $this->done_at?->toIso8601String() : null,
        ];
    }
}
