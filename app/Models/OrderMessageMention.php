<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A @person, #task or #project tag inside a chat message. */
class OrderMessageMention extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}
