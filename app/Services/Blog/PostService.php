<?php

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Creating and updating articles: clean HTML, unique slug, tags, category, picture, publish/schedule rules. */
class PostService
{
    public function __construct(private ContentProcessor $content, private ImageStore $images)
    {
    }

    /**
     * @param  array<string,mixed>  $data  validated form data (see SavePostRequest)
     */
    public function save(BlogPost $post, array $data, ?UploadedFile $image, User $actor): BlogPost
    {
        return DB::transaction(function () use ($post, $data, $image, $actor) {
            $isNew = ! $post->exists;
            $html = $this->content->sanitize((string) $data['content']);

            $post->fill([
                'title' => trim($data['title']),
                'excerpt' => $this->nullable($data['excerpt'] ?? null),
                'content' => $html,
                'featured_image_alt' => $this->nullable($data['featured_image_alt'] ?? null),
                'image_credit' => $this->nullable($data['image_credit'] ?? null),
                'meta_title' => $this->nullable($data['meta_title'] ?? null),
                'meta_description' => $this->nullable($data['meta_description'] ?? null),
                'focus_keyword' => $this->nullable($data['focus_keyword'] ?? null),
                'keywords' => $this->nullable($this->cleanList($data['keywords'] ?? '')),
                'canonical_url' => $this->nullable($data['canonical_url'] ?? null),
                'noindex' => ! empty($data['noindex']),
                'is_featured' => ! empty($data['is_featured']),
                'reading_minutes' => $this->content->readingMinutes($html),
                'category_id' => $this->category($data),
            ]);

            if ($isNew) {
                $post->author_id = $data['author_id'] ?? $actor->id;
            } elseif (! empty($data['author_id'])) {
                $post->author_id = $data['author_id'];
            }

            $post->slug = $this->uniqueSlug($data['slug'] ?? '', $post->title, $post->exists ? $post->id : null);
            $this->applyStatus($post, $data);

            if (! empty($data['remove_image']) && $post->featured_image) {
                $this->images->delete($post->featured_image);
                $post->featured_image = null;
            }
            if ($image) {
                $this->images->delete($post->featured_image);
                $post->featured_image = $this->images->store($image, 'blog');
            }

            $post->save();
            $post->tags()->sync($this->tagIds((string) ($data['tags'] ?? '')));

            return $post;
        });
    }

    public function delete(BlogPost $post): void
    {
        $this->images->delete($post->featured_image);
        $post->delete();
    }

    /* ------------------------------------------------------------ pieces */

    private function applyStatus(BlogPost $post, array $data): void
    {
        $when = ! empty($data['published_at']) ? Carbon::parse($data['published_at'])->setTimezone(config('app.timezone')) : null;

        if (($data['action'] ?? 'draft') === 'publish') {
            $post->status = BlogPost::STATUS_PUBLISHED;
            // no date given = publish now; keep the original date when updating an already published article
            $post->published_at = $when ?? ($post->published_at && $post->isLive() ? $post->published_at : now());
        } else {
            $post->status = BlogPost::STATUS_DRAFT;
            $post->published_at = $when ?? $post->published_at;
        }
    }

    private function category(array $data): ?int
    {
        $new = trim((string) ($data['new_category'] ?? ''));
        if ($new !== '') {
            $slug = Str::slug($new);
            $cat = BlogCategory::firstOrCreate(['slug' => $slug], ['name' => Str::limit($new, 80, '')]);

            return $cat->id;
        }

        return ! empty($data['category_id']) ? (int) $data['category_id'] : null;
    }

    /** @return array<int,int> */
    private function tagIds(string $csv): array
    {
        $ids = [];
        foreach (array_slice(array_unique(array_filter(array_map('trim', explode(',', $csv)))), 0, 15) as $name) {
            $slug = Str::slug($name);
            if ($slug === '') {
                continue;
            }
            $ids[] = BlogTag::firstOrCreate(['slug' => $slug], ['name' => Str::limit($name, 60, '')])->id;
        }

        return $ids;
    }

    public function uniqueSlug(string $wanted, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($wanted !== '' ? $wanted : $title) ?: 'post';
        $base = Str::limit($base, 150, '');
        if (in_array($base, BlogPost::RESERVED_SLUGS, true)) {
            $base .= '-post';
        }

        $slug = $base;
        for ($i = 2; BlogPost::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    private function nullable(?string $v): ?string
    {
        $v = $v === null ? null : trim($v);

        return $v === '' ? null : $v;
    }

    private function cleanList(string $csv): string
    {
        return implode(', ', array_unique(array_filter(array_map('trim', explode(',', $csv)))));
    }
}
