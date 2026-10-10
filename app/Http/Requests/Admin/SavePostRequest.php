<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;

class SavePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:draft,publish'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'content' => ['required', 'string', 'max:400000'],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'new_category' => ['nullable', 'string', 'max:80'],
            'tags' => ['nullable', 'string', 'max:600'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'featured_image_alt' => ['nullable', 'string', 'max:200'],
            'image_credit' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'noindex' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Give the article a title.',
            'content.required' => 'The article is empty. Write something in the editor first.',
            'slug.regex' => 'The web address can only use lowercase letters, numbers and dashes.',
            'featured_image.image' => 'The featured image must be a picture (JPG, PNG or WebP).',
            'featured_image.max' => 'The featured image is bigger than 8 MB. Please choose a smaller one.',
            'canonical_url.url' => 'The canonical address must be a full link starting with https://',
            'published_at.date' => 'That publish date is not valid.',
        ];
    }

    /** The web address cannot be one of the words the blog itself uses. */
    protected function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->filled('slug') && in_array($this->input('slug'), BlogPost::RESERVED_SLUGS, true)) {
                $v->errors()->add('slug', 'That web address is reserved. Please choose another.');
            }
        });
    }
}
