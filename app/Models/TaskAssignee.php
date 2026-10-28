<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pivot row task <-> assigned team member, used in sub-queries. */
class TaskAssignee extends Model
{
    protected $table = 'task_assignees';

    protected $guarded = [];

    public $incrementing = false;
}
