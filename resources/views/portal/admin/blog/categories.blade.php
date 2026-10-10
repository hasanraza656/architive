@extends('portal.layouts.app')
@section('title', 'Blog categories')
@section('heading', 'Blog')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-blog.css') }}">
@endpush

@section('content')
    <a class="crumb" href="{{ route('admin.blog.posts.index') }}"><x-icon name="arrow-left" /> All posts</a>
    <div class="phead">
        <div>
            <h1 class="phead__title">Categories</h1>
            <p class="phead__sub">Group articles by topic. Readers can browse each category on the blog.</p>
        </div>
    </div>

    <div class="pgrid pgrid--main">
        <section class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="layers" /> All categories ({{ $categories->count() }})</h2></div>
            @forelse ($categories as $c)
                <form class="cat-row" method="post" action="{{ route('admin.blog.categories.update', $c) }}" data-loading>
                    @csrf @method('PUT')
                    <div class="cat-row__fields">
                        <input class="pinput" name="name" value="{{ $c->name }}" maxlength="80" aria-label="Category name" required>
                        <input class="pinput" name="description" value="{{ $c->description }}" maxlength="300" placeholder="Short description (optional)" aria-label="Description">
                    </div>
                    <span class="cat-row__count">{{ $c->posts_count }} {{ \Illuminate\Support\Str::plural('post', $c->posts_count) }}</span>
                    <div class="cat-row__act">
                        <button class="pbtn pbtn--ghost pbtn--sm" type="submit">Save</button>
                        <button class="pbtn pbtn--danger pbtn--sm" type="submit" form="delcat{{ $c->id }}" aria-label="Delete {{ $c->name }}"><x-icon name="trash" /></button>
                    </div>
                </form>
                <form id="delcat{{ $c->id }}" method="post" action="{{ route('admin.blog.categories.destroy', $c) }}" data-confirm="Delete the category “{{ $c->name }}”? Its articles are kept, they just lose this category.">@csrf @method('DELETE')</form>
            @empty
                <div class="empty"><x-icon name="layers" /><b>No categories yet</b><span>Add the first one on the right.</span></div>
            @endforelse
        </section>

        <aside class="pcard">
            <div class="pcard__head"><h2 class="pcard__title"><x-icon name="plus" /> Add a category</h2></div>
            <form class="pcard__body pform" method="post" action="{{ route('admin.blog.categories.store') }}" data-loading>
                @csrf
                <x-portal.field name="name" label="Name"><input class="pinput" id="name" name="name" maxlength="80" value="{{ old('name') }}" placeholder="e.g. Architectural Visualization" required></x-portal.field>
                <x-portal.field name="description" label="Description" optional><textarea class="ptextarea" id="description" name="description" rows="3" maxlength="300">{{ old('description') }}</textarea></x-portal.field>
                <div class="pactions"><button class="pbtn pbtn--primary" type="submit">Add category</button></div>
            </form>
        </aside>
    </div>
@endsection
