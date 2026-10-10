<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Admin: blog categories. Articles keep existing when a category is removed (they become "Uncategorised"). */
class BlogCategoryController extends Controller
{
    public function index()
    {
        return view('portal.admin.blog.categories', ['categories' => BlogCategory::withCount('posts')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:300']]);
        $slug = $this->slug($data['name']);
        BlogCategory::create(['name' => $data['name'], 'slug' => $slug, 'description' => $data['description'] ?? null]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, BlogCategory $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:300']]);
        $category->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);

        return back()->with('success', 'Category saved.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        BlogPost::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return back()->with('success', 'Category deleted. Its articles were kept.');
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; BlogCategory::where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
