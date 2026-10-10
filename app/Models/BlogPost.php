<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/** A blog article. "Live" = status published AND published_at in the past (a future date means scheduled). */
class BlogPost extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /** URL words that would clash with blog routes. */
    public const RESERVED_SLUGS = ['category', 'tag', 'feed', 'feed-xml', 'page', 'author', 'search'];

    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'noindex' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /* ------------------------------------------------------------ Relations */

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag')->orderBy('name');
    }

    /* ------------------------------------------------------------ Scopes */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    /* ------------------------------------------------------------ State */

    public function isLive(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at && $this->published_at->gt(now());
    }

    /** 'live' | 'scheduled' | 'draft' */
    public function stage(): string
    {
        return $this->isLive() ? 'live' : ($this->isScheduled() ? 'scheduled' : 'draft');
    }

    /* ------------------------------------------------------------ Presentation */

    public function url(): string
    {
        return pu('blog.show', ['slug' => $this->slug]);
    }

    public function absoluteUrl(): string
    {
        return abs_pu('blog.show', ['slug' => $this->slug]);
    }

    public function imageUrl(): ?string
    {
        return $this->featured_image ? asset($this->featured_image) : null;
    }

    /** Smaller copy (name-800.ext) when it exists, otherwise the full image. */
    public function thumbUrl(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }
        $thumb = preg_replace('/\.(webp|jpe?g|png)$/i', '-800.$1', $this->featured_image);

        return is_file(public_path($thumb)) ? asset($thumb) : asset($this->featured_image);
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title . config('seo.title_suffix', ' | Architive');
    }

    public function seoDescription(): string
    {
        return $this->meta_description ?: $this->summary(158);
    }

    public function summary(int $limit = 170): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->excerpt ?: $this->content))), $limit);
    }

    public function keywordList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->keywords))));
    }
}
