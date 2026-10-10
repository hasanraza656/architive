<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin: write, schedule, publish and delete blog articles. */
class BlogPostController extends Controller
{
    public function __construct(private PostService $posts)
    {
    }

    public function index(Request $request)
    {
        $stage = in_array($request->query('stage'), ['live', 'scheduled', 'draft'], true) ? $request->query('stage') : null;
        $category = (int) $request->query('category');
        $q = trim((string) $request->query('q'));

        $query = BlogPost::with(['author', 'category'])
            ->when($stage === 'live', fn ($b) => $b->published())
            ->when($stage === 'scheduled', fn ($b) => $b->where('status', 'published')->where('published_at', '>', now()))
            ->when($stage === 'draft', fn ($b) => $b->where('status', 'draft'))
            ->when($category, fn ($b) => $b->where('category_id', $category))
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('slug', 'like', "%$q%")->orWhere('keywords', 'like', "%$q%")))
            ->orderByDesc(\DB::raw('COALESCE(published_at, created_at)'));

        return view('portal.admin.blog.index', [
            'posts' => $query->paginate(15)->withQueryString(),
            'stage' => $stage,
            'category' => $category,
            'q' => $q,
            'categories' => BlogCategory::orderBy('name')->get(),
            'counts' => [
                'all' => BlogPost::count(),
                'live' => BlogPost::published()->count(),
                'scheduled' => BlogPost::where('status', 'published')->where('published_at', '>', now())->count(),
                'draft' => BlogPost::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        return view('portal.admin.blog.form', $this->formData(new BlogPost(['author_id' => $request->user()->id])));
    }

    public function store(SavePostRequest $request): RedirectResponse
    {
        try {
            $post = $this->posts->save(new BlogPost, $request->validated(), $request->file('featured_image'), $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['featured_image' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', $this->message($post, true));
    }

    public function edit(BlogPost $post)
    {
        return view('portal.admin.blog.form', $this->formData($post->load('tags', 'category', 'author')));
    }

    public function update(SavePostRequest $request, BlogPost $post): RedirectResponse
    {
        try {
            $post = $this->posts->save($post, $request->validated(), $request->file('featured_image'), $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['featured_image' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', $this->message($post, false));
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $title = $post->title;
        $this->posts->delete($post);

        return redirect()->route('admin.blog.posts.index')->with('success', "“{$title}” was deleted.");
    }

    /* ------------------------------------------------------------ */

    private function message(BlogPost $post, bool $created): string
    {
        return match ($post->stage()) {
            'live' => $created ? 'Published. Your article is live on the blog.' : 'Saved. The live article is updated.',
            'scheduled' => 'Scheduled for ' . $post->published_at->format('M j, Y \a\t H:i') . '. It goes live automatically.',
            default => $created ? 'Draft saved. Only you can see it.' : 'Draft saved.',
        };
    }

    private function formData(BlogPost $post): array
    {
        return [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
            'authors' => User::where('role', User::ROLE_ADMIN)->orderBy('first_name')->get(),
            'knownTags' => \App\Models\BlogTag::orderBy('name')->pluck('name'),
        ];
    }
}
