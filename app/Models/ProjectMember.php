<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pivot row project <-> team member, used in sub-queries. */
class ProjectMember extends Model
{
    protected $table = 'project_members';

    protected $guarded = [];

    public $incrementing = false;
}
