<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Blog\ContentProcessor;
use Illuminate\Http\Request;

/** Public blog: list, categories, tags, article pages and the RSS feed. */
class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $page = max(1, (int) $request->query('page', 1));

        $query = $this->listQuery();
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('excerpt', 'like', "%$q%")->orWhere('content', 'like', "%$q%")->orWhere('keywords', 'like', "%$q%"));
        }

        // the big "featured" card only shows on the plain first page
        $featured = null;
        if ($q === '' && $page === 1) {
            $featured = $this->listQuery()->where('is_featured', true)->first() ?: null;
            if ($featured) {
                $query->where('id', '!=', $featured->id);
            }
        }

        $posts = $query->paginate(self::PER_PAGE)->withPath(pu('blog.index'))->withQueryString();

        return view('pages.blog.index', array_merge($this->sidebar(), [
            'posts' => $posts,
            'featured' => $featured,
            'q' => $q,
            'category' => null,
            'tag' => null,
            'crumbs' => $this->crumbs(),
            'seo' => $this->listSeo($posts, $q, null, null),
        ]));
    }

    public function category(Request $request, string $slug)
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();
        $posts = $this->listQuery()->where('category_id', $category->id)->paginate(self::PER_PAGE)->withPath($category->url())->withQueryString();

        return view('pages.blog.index', array_merge($this->sidebar(), [
            'posts' => $posts, 'featured' => null, 'q' => '', 'category' => $category, 'tag' => null,
            'crumbs' => $this->crumbs($category->name),
            'seo' => $this->listSeo($posts, '', $category, null),
        ]));
    }

    public function tag(Request $request, string $slug)
    {
        $tag = BlogTag::where('slug', $slug)->firstOrFail();
        $posts = $this->listQuery()->whereHas('tags', fn ($t) => $t->where('blog_tags.id', $tag->id))->paginate(self::PER_PAGE)->withPath($tag->url())->withQueryString();

        return view('pages.blog.index', array_merge($this->sidebar(), [
            'posts' => $posts, 'featured' => null, 'q' => '', 'category' => null, 'tag' => $tag,
            'crumbs' => $this->crumbs($tag->name),
            'seo' => $this->listSeo($posts, '', null, $tag),
        ]));
    }

    public function show(Request $request, ContentProcessor $content, string $slug)
    {
        $post = BlogPost::with(['author', 'category', 'tags'])->where('slug', $slug)->firstOrFail();
        $isAdmin = (bool) $request->user()?->isAdmin();
        $preview = ! $post->isLive();
        abort_if($preview && ! $isAdmin, 404);

        if (! $preview && ! $isAdmin && ! $request->session()->has('blog_seen_' . $post->id)) {
            BlogPost::whereKey($post->id)->increment('views');
            $request->session()->put('blog_seen_' . $post->id, true);
        }

        $rendered = $content->render((string) $post->content);

        $related = BlogPost::published()->with(['author', 'category'])->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$post->category_id]))
            ->latest('published_at')->limit(3)->get();

        $prev = $preview ? null : BlogPost::published()->where('published_at', '<', $post->published_at)->latest('published_at')->first();
        $next = $preview ? null : BlogPost::published()->where('published_at', '>', $post->published_at)->oldest('published_at')->first();

        [$w, $h] = $this->imageSize($post->featured_image);

        $seo = [
            'title' => $post->seoTitle(),
            'description' => $post->seoDescription(),
            'type' => 'article',
            'robots' => ($post->noindex || $preview)
                ? 'noindex,follow'
                : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'canonical' => $post->canonical_url ?: $post->absoluteUrl(),
        ];
        if ($post->featured_image) {
            $seo['image'] = $post->featured_image;
            $seo['image_w'] = $w;
            $seo['image_h'] = $h;
            $seo['image_alt'] = $post->featured_image_alt ?: $post->title;
        }

        return view('pages.blog.show', [
            'post' => $post, 'body' => $rendered['html'], 'toc' => $rendered['toc'], 'related' => $related,
            'prev' => $prev, 'next' => $next, 'preview' => $preview, 'imageSize' => [$w, $h],
            'words' => $content->wordCount((string) $post->content), 'seo' => $seo,
            'crumbs' => array_values(array_filter([
                ['label' => 'Home', 'url' => abs_pu('home')],
                ['label' => 'Blog', 'url' => abs_pu('blog.index')],
                $post->category ? ['label' => $post->category->name, 'url' => $post->category->absoluteUrl()] : null,
                ['label' => \Illuminate\Support\Str::limit($post->title, 48), 'url' => $post->absoluteUrl()],
            ])),
        ]);
    }

    public function feed()
    {
        $posts = BlogPost::published()->with(['author', 'category'])->latest('published_at')->limit(20)->get();

        return response()->view('seo.blog-feed', ['posts' => $posts])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /* ------------------------------------------------------------ helpers */

    /** Visible breadcrumb trail (the structured-data one is built in AppServiceProvider). */
    private function crumbs(?string $last = null): array
    {
        $trail = [['label' => 'Home', 'url' => abs_pu('home')], ['label' => 'Blog', 'url' => abs_pu('blog.index')]];
        if ($last) {
            $trail[] = ['label' => $last, 'url' => rtrim(url()->current(), '/') . '/'];
        }

        return $trail;
    }

    private function listQuery()
    {
        return BlogPost::published()->with(['author', 'category'])->orderByDesc('published_at')->orderByDesc('id');
    }

    private function sidebar(): array
    {
        return [
            'categories' => BlogCategory::withCount(['posts' => fn ($q) => $q->published()])->orderBy('name')->get()->filter(fn ($c) => $c->posts_count > 0)->values(),
            'popularTags' => BlogTag::withCount(['posts' => fn ($q) => $q->published()])->get()->filter(fn ($t) => $t->posts_count > 0)->sortByDesc('posts_count')->take(12)->values(),
        ];
    }

    private function listSeo($posts, string $q, ?BlogCategory $category, ?BlogTag $tag): array
    {
        $page = $posts->currentPage();
        $base = $category ? $category->url() : ($tag ? $tag->url() : pu('blog.index'));
        $absolute = rtrim(url('/'), '/') . $base;

        $seo = [];
        if ($category) {
            $seo['title'] = $category->name . ' Articles | Architive Blog';
            $seo['description'] = $category->description ?: "Articles on {$category->name} from the Architive studio: practical advice for architecture firms, designers and homeowners.";
        } elseif ($tag) {
            $seo['title'] = 'Articles tagged “' . $tag->name . '” | Architive Blog';
            $seo['description'] = "Everything we have written about {$tag->name}: guides and practical advice from the Architive production studio.";
        } elseif ($q !== '') {
            $seo['title'] = 'Search results for “' . $q . '” | Architive Blog';
            $seo['description'] = 'Search the Architive blog.';
        }
        if ($page > 1 && isset($seo['title'])) {
            $seo['title'] = preg_replace('/ \| /', ' – Page ' . $page . ' | ', $seo['title'], 1);
        } elseif ($page > 1) {
            $seo['title'] = 'Architive Blog – Page ' . $page . ' | Architive';
        }
        $seo['canonical'] = $page > 1 ? $absolute . '?page=' . $page : $absolute;
        if ($q !== '' || ($tag && $posts->total() < 3)) {
            $seo['robots'] = 'noindex,follow';   // search results and thin tag pages stay out of Google
            $seo['canonical'] = $absolute;
        }

        return $seo;
    }

    /** @return array{0:int,1:int} width and height of a picture inside /public (used for stable layout and og:image size). */
    private function imageSize(?string $path): array
    {
        if ($path && is_file(public_path($path))) {
            $info = @getimagesize(public_path($path));
            if ($info) {
                return [(int) $info[0], (int) $info[1]];
            }
        }

        return [1200, 675];
    }
}
