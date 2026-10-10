<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Services\Blog\ContentProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter articles for the blog (8 SEO-optimised guides). Safe to run again: posts are matched by their web address,
 * so nothing is duplicated and the author/picture you set later is kept.
 *   php artisan db:seed --class=BlogSeeder
 */
class BlogSeeder extends Seeder
{
    private const CATEGORIES = [
        'Architectural Visualization' => 'Rendering, interiors, exteriors and 3D floor plans: how to brief, budget and get the most from architectural visualization.',
        'BIM and Revit' => 'Revit modeling, scan to BIM and families: practical advice for design teams working in BIM.',
        'CAD Drafting' => 'Drawing sets, conversions and permit documentation: clear, accurate CAD drafting guidance.',
        'Studio and Process' => 'How production support works with architecture firms, designers and developers.',
    ];

    public function run(): void
    {
        $author = User::where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        if ($author && ! $author->job_title) {
            $author->forceFill(['job_title' => 'Architive Studio Team'])->save();
        }
        if ($author && ! $author->bio) {
            $author->forceFill(['bio' => 'The Architive team writes about architectural visualization, BIM and Revit, and CAD drafting, based on the production work we do for architecture firms, interior studios, developers and homeowners worldwide.'])->save();
        }

        $categories = [];
        foreach (self::CATEGORIES as $name => $description) {
            $categories[$name] = BlogCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $description]);
        }

        $credits = [];
        $creditsFile = public_path('assets/img/blog/credits.json');
        if (is_file($creditsFile)) {
            $credits = json_decode((string) file_get_contents($creditsFile), true) ?: [];
        }

        $content = app(ContentProcessor::class);

        foreach (glob(database_path('seeders/data/blog/*.php')) as $file) {
            $d = require $file;
            $slug = $d['slug'];
            $exists = BlogPost::where('slug', $slug)->first();
            $html = $content->sanitize($d['content']);
            $credit = $credits[$slug][0] ?? null;

            $attrs = [
                'title' => $d['title'],
                'excerpt' => $d['excerpt'],
                'content' => $html,
                'category_id' => $categories[$d['category']]->id,
                'featured_image' => "assets/img/blog/{$slug}.webp",
                'featured_image_alt' => $d['image_alt'],
                'image_credit' => $credit ? 'Photo by ' . $credit['photographer'] . ' on Pexels' : null,
                'meta_title' => $d['meta_title'],
                'meta_description' => $d['meta_description'],
                'focus_keyword' => $d['focus_keyword'],
                'keywords' => $d['keywords'],
                'is_featured' => $d['featured'] ?? false,
                'reading_minutes' => $content->readingMinutes($html),
            ];
            if (! $exists) {
                $attrs += ['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->subDays($d['days_ago'])->setTime(9, 0), 'author_id' => $author?->id, 'slug' => $slug];
            }

            $post = BlogPost::updateOrCreate(['slug' => $slug], $attrs);
            if (! $exists) {
                $post->timestamps = false;                      // the "Updated" date should not say seeding day
                $post->forceFill(['created_at' => $post->published_at, 'updated_at' => $post->published_at])->save();
            }
            $post->tags()->sync(collect($d['tags'])->map(fn ($t) => BlogTag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t])->id)->all());
        }
    }
}
