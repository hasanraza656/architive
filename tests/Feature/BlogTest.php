<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\Blog\ContentProcessor;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // the public blog URLs end with a slash; the test client drops it, so skip the redirect middleware
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class);
    }

    private function admin(): User
    {
        return User::where('email', 'ada@example.test')->first() ?? User::create(['role' => 'admin', 'first_name' => 'Ada', 'last_name' => 'Writer', 'email' => 'ada@example.test', 'password' => bcrypt('x'), 'is_active' => true,
            'job_title' => 'Founder', 'bio' => 'Ada writes about drawings.']);
    }

    private function makePost(array $over = []): BlogPost
    {
        $cat = BlogCategory::firstOrCreate(['slug' => 'bim'], ['name' => 'BIM']);

        return BlogPost::create($over + [
            'author_id' => $this->admin_id ??= $this->admin()->id, 'category_id' => $cat->id, 'title' => 'Scan to BIM basics', 'slug' => 'scan-to-bim-basics',
            'excerpt' => 'A short summary.', 'content' => '<h2>One</h2><p>Hello <a href="/contact/">contact</a>.</p><h2>Two</h2><p>More.</p><h3>Three</h3><p>End.</p>',
            'status' => 'published', 'published_at' => now()->subDay(), 'reading_minutes' => 1,
        ]);
    }

    private ?int $admin_id = null;

    public function test_public_blog_lists_only_live_posts_and_shows_seo_tags(): void
    {
        $live = $this->makePost();
        $this->makePost(['title' => 'Draft idea', 'slug' => 'draft-idea', 'status' => 'draft']);
        $this->makePost(['title' => 'Coming soon', 'slug' => 'coming-soon', 'published_at' => now()->addDays(3)]);

        $this->get('/blog/')->assertOk()->assertSee('Scan to BIM basics')->assertDontSee('Draft idea')->assertDontSee('Coming soon')
            ->assertSee('application/ld+json', false);

        $this->get('/blog/scan-to-bim-basics/')->assertOk()
            ->assertSee('<link rel="canonical" href="' . $live->absoluteUrl() . '">', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('Founder')                       // author box
            ->assertSee('id="one"', false)               // heading anchors for the table of contents
            ->assertSee('Ada Writer');
    }

    public function test_drafts_and_scheduled_posts_are_hidden_from_the_public_but_previewable_by_admins(): void
    {
        $this->makePost(['title' => 'Draft idea', 'slug' => 'draft-idea', 'status' => 'draft']);

        $this->get('/blog/draft-idea/')->assertNotFound();
        $this->actingAs(User::where('role', 'admin')->first())->get('/blog/draft-idea/')->assertOk()->assertSee('Preview')->assertSee('noindex', false);
        $this->get('/blog/missing-article/')->assertNotFound();
    }

    public function test_sitemap_feed_and_category_pages(): void
    {
        $this->makePost();
        $this->makePost(['title' => 'Hidden', 'slug' => 'hidden', 'noindex' => true]);
        $this->makePost(['title' => 'Draft idea', 'slug' => 'draft-idea', 'status' => 'draft']);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/blog/scan-to-bim-basics/', false)->assertDontSee('/blog/hidden/', false)->assertDontSee('draft-idea', false)->assertSee('/blog/category/bim/', false);
        $this->get('/blog/feed.xml')->assertOk()->assertSee('<item>', false)->assertSee('Scan to BIM basics')->assertDontSee('Draft idea');
        $this->get('/blog/category/bim/')->assertOk()->assertSee('Scan to BIM basics');
        $this->get('/blog/category/nope/')->assertNotFound();
        $this->get('/blog/?q=basics')->assertOk()->assertSee('Scan to BIM basics')->assertSee('noindex', false);
    }

    public function test_content_is_sanitised_when_saved(): void
    {
        $clean = app(ContentProcessor::class)->sanitize('<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">bad</a> <a href="https://example.com" target="_blank">ok</a></p><iframe src="https://evil"></iframe><img src="/a.webp" onerror="x()">');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('<img src="/a.webp" alt="">', $clean);
    }

    public function test_admin_can_create_edit_publish_and_delete_a_post(): void
    {
        Storage::fake('uploads');
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->get('/admin/blog/posts')->assertOk();
        $this->get('/admin/blog/posts/create')->assertOk()->assertSee('Search engine');

        $this->post('/admin/blog/posts', [
            'action' => 'draft', 'title' => 'Hello World Post', 'content' => '<p>Body of the article.</p>', 'tags' => 'BIM, Revit, bim',
            'new_category' => 'Studio Notes', 'focus_keyword' => 'hello', 'meta_description' => 'Short.',
        ])->assertRedirect();
        $post = BlogPost::where('slug', 'hello-world-post')->firstOrFail();
        $this->assertSame('draft', $post->stage());
        $this->assertSame(2, $post->tags()->count());                  // duplicate tag collapsed
        $this->assertSame('Studio Notes', $post->category->name);
        $this->assertSame($admin->id, $post->author_id);
        $this->get('/blog/hello-world-post/')->assertOk();            // admin preview works (signed in)

        $this->put('/admin/blog/posts/' . $post->id, ['action' => 'publish', 'title' => 'Hello World Post', 'slug' => 'hello', 'content' => '<p>Updated body.</p>'])->assertRedirect();
        $post->refresh();
        $this->assertSame('live', $post->stage());
        $this->assertSame('hello', $post->slug);

        // scheduling
        $this->put('/admin/blog/posts/' . $post->id, ['action' => 'publish', 'title' => 'Hello', 'content' => '<p>x</p>', 'published_at' => now()->addDays(2)->toIso8601String()])->assertRedirect();
        $this->assertSame('scheduled', $post->fresh()->stage());

        $this->delete('/admin/blog/posts/' . $post->id)->assertRedirect(route('admin.blog.posts.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_validation_and_reserved_addresses(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/blog/posts', ['action' => 'publish', 'title' => '', 'content' => ''])->assertSessionHasErrors(['title', 'content']);
        $this->post('/admin/blog/posts', ['action' => 'draft', 'title' => 'X', 'content' => '<p>a</p>', 'slug' => 'category'])->assertSessionHasErrors('slug');
        $this->post('/admin/blog/posts', ['action' => 'draft', 'title' => 'Same', 'content' => '<p>a</p>']);
        $this->post('/admin/blog/posts', ['action' => 'draft', 'title' => 'Same', 'content' => '<p>b</p>']);
        $this->assertSame(['same', 'same-2'], BlogPost::orderBy('id')->pluck('slug')->all());   // unique addresses
    }

    public function test_image_upload_goes_to_public_uploads_and_replaces_cleanly(): void
    {
        Storage::fake('uploads');
        $this->actingAs($this->admin());

        $res = $this->post('/admin/blog/media', ['image' => UploadedFile::fake()->image('pic.jpg', 900, 500)])->assertCreated();
        $path = $res->json('path');
        $this->assertStringStartsWith('uploads/blog/', $path);
        Storage::disk('uploads')->assertExists(substr($path, strlen('uploads/')));

        $this->post('/admin/blog/media', ['image' => UploadedFile::fake()->create('evil.php', 5, 'text/plain')])->assertSessionHasErrors('image');

        $this->post('/admin/blog/posts', ['action' => 'draft', 'title' => 'With picture', 'content' => '<p>a</p>', 'featured_image' => UploadedFile::fake()->image('hero.png', 1800, 900), 'featured_image_alt' => 'Alt'])->assertRedirect();
        $post = BlogPost::where('slug', 'with-picture')->firstOrFail();
        $this->assertStringStartsWith('uploads/blog/', $post->featured_image);
        Storage::disk('uploads')->assertExists(substr($post->featured_image, strlen('uploads/')));
        Storage::disk('uploads')->assertExists(substr(preg_replace('/\.webp$/', '-800.webp', $post->featured_image), strlen('uploads/')));

        $this->delete('/admin/blog/posts/' . $post->id);
        Storage::disk('uploads')->assertMissing(substr($post->featured_image, strlen('uploads/')));   // picture removed with the post
    }

    public function test_admin_profile_photo_and_bio_show_in_the_author_box(): void
    {
        Storage::fake('uploads');
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/settings/profile', [
            'first_name' => 'Ada', 'last_name' => 'Writer', 'email' => 'ada@example.test', 'job_title' => 'Lead architect', 'bio' => 'Short bio here.',
            'avatar' => UploadedFile::fake()->image('me.jpg', 800, 800),
        ])->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertStringStartsWith('uploads/avatars/', $admin->avatar);
        $this->makePost();
        $this->get('/blog/scan-to-bim-basics/')->assertOk()->assertSee($admin->avatar, false)->assertSee('Lead architect')->assertSee('Short bio here.');
    }

    public function test_blog_admin_is_for_admins_only_and_categories_work(): void
    {
        $this->get('/admin/blog/posts')->assertRedirect();
        $customer = User::create(['role' => 'customer', 'first_name' => 'C', 'email' => 'c@example.test', 'is_active' => true]);
        $this->actingAs($customer)->get('/admin/blog/posts')->assertRedirect();

        $this->actingAs($this->admin());
        $this->post('/admin/blog/categories', ['name' => 'Rendering'])->assertRedirect();
        $cat = BlogCategory::where('slug', 'rendering')->firstOrFail();
        $post = $this->makePost(['category_id' => $cat->id]);
        $this->delete('/admin/blog/categories/' . $cat->id)->assertRedirect();
        $this->assertNull($post->fresh()->category_id);                // articles survive
        $this->get('/admin/blog/categories')->assertOk();
    }

    public function test_footer_and_files_live_in_public_uploads(): void
    {
        $this->get('/')->assertOk()->assertSee('/blog/', false);
        $this->assertSame(public_path('uploads'), config('filesystems.disks.uploads.root'));
        $this->assertSame('uploads', config('portal.uploads.disk'));
    }
}
