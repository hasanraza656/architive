<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    protected $guarded = ['id'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function url(): string
    {
        return pu('blog.tag', ['slug' => $this->slug]);
    }

    public function absoluteUrl(): string
    {
        return abs_pu('blog.tag', ['slug' => $this->slug]);
    }
}
