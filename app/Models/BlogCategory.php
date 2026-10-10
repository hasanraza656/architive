<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $guarded = ['id'];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    public function url(): string
    {
        return pu('blog.category', ['slug' => $this->slug]);
    }

    public function absoluteUrl(): string
    {
        return abs_pu('blog.category', ['slug' => $this->slug]);
    }
}
