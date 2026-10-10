@extends('portal.layouts.app')
@php $isNew = ! $post->exists; @endphp
@section('title', $isNew ? 'New post' : 'Edit post')
@section('heading', 'Blog')

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/vendor/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/portal-blog.css') }}">
@endpush

@section('content')
    @php
        $siteHost = parse_url(url('/'), PHP_URL_HOST);
        $stage = $isNew ? 'draft' : $post->stage();
        $publishIso = old('published_at', $post->published_at?->toIso8601String());
    @endphp

    <a class="crumb" href="{{ route('admin.blog.posts.index') }}"><x-icon name="arrow-left" /> All posts</a>

    <form id="postForm" class="bform" method="post" enctype="multipart/form-data" novalidate
          action="{{ $isNew ? route('admin.blog.posts.store') : route('admin.blog.posts.update', $post) }}"
          data-media-url="{{ route('admin.blog.media') }}" data-site="{{ $siteHost }}" data-suffix="{{ config('seo.title_suffix') }}">
        @csrf
        @unless ($isNew) @method('PUT') @endunless
        <input type="hidden" name="action" value="draft" data-action>
        <input type="hidden" name="published_at" value="{{ $publishIso }}" data-publish-utc>

        <div class="bform__main">
            {{-- title + address --}}
            <div class="pcard bcard">
                <div class="bcard__body">
                    <label class="visually-hidden" for="title">Title</label>
                    <input class="btitle" id="title" name="title" value="{{ old('title', $post->title) }}" maxlength="200" placeholder="Article title" autocomplete="off" required data-title>
                    @error('title')<p class="pfield__error">{{ $message }}</p>@enderror
                    <div class="bslug">
                        <span>Web address:</span>
                        <span class="bslug__url">{{ $siteHost }}/blog/<input id="slug" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="150" aria-label="Web address ending" placeholder="auto-from-title" data-slug data-touched="{{ $isNew ? '0' : '1' }}">/</span>
                    </div>
                    @error('slug')<p class="pfield__error">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- editor --}}
            <div class="pcard bcard">
                <div class="bcard__head"><h2>Article</h2><span class="bcard__hint" data-wordcount>0 words</span></div>
                <div class="bcard__body bcard__body--flush">
                    <div id="editor" class="beditor" data-editor></div>
                    <textarea name="content" id="content" hidden data-content>{{ old('content', $post->content) }}</textarea>
                </div>
                @error('content')<p class="pfield__error" style="padding:0 1.2rem 1rem">{{ $message }}</p>@enderror
                <p class="bcard__tip"><x-icon name="info" /> Tip: use <b>Heading 2</b> for the main sections and <b>Heading 3</b> inside them. Readers (and Google) use them to find their way. Drag a picture into the editor, or click the picture button, to add it.</p>
            </div>

            {{-- excerpt --}}
            <div class="pcard bcard">
                <div class="bcard__head"><h2>Short summary</h2><span class="bcard__hint"><span data-count-for="excerpt">0</span>/400</span></div>
                <div class="bcard__body">
                    <label class="visually-hidden" for="excerpt">Short summary</label>
                    <textarea class="ptextarea" id="excerpt" name="excerpt" rows="3" maxlength="400" placeholder="One or two sentences that make someone want to read the article. Shown on the blog list and in search results when no meta description is set." data-counter>{{ old('excerpt', $post->excerpt) }}</textarea>
                    @error('excerpt')<p class="pfield__error">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- SEO --}}
            <div class="pcard bcard" id="seo">
                <div class="bcard__head"><h2><x-icon name="search" /> Search engine (SEO)</h2><span class="seo-score" data-seo-score>…</span></div>
                <div class="bcard__body">
                    <div class="serp" aria-label="How this looks in Google">
                        <span class="serp__url" data-serp-url></span>
                        <span class="serp__title" data-serp-title></span>
                        <span class="serp__desc" data-serp-desc></span>
                    </div>

                    <div class="pfield">
                        <label for="focus_keyword">Focus keyword <span class="opt">(the phrase people would search for)</span></label>
                        <input class="pinput" id="focus_keyword" name="focus_keyword" maxlength="120" value="{{ old('focus_keyword', $post->focus_keyword) }}" placeholder="e.g. architectural visualization cost" data-focus>
                    </div>
                    <div class="pfield">
                        <label for="meta_title">SEO title <span class="opt">(<span data-count-for="meta_title">0</span>/60 · leave empty to use the article title)</span></label>
                        <input class="pinput" id="meta_title" name="meta_title" maxlength="160" value="{{ old('meta_title', $post->meta_title) }}" placeholder="Article title | Architive" data-counter data-meta-title>
                        <span class="meter"><i data-meter-for="meta_title" data-min="30" data-max="60"></i></span>
                    </div>
                    <div class="pfield">
                        <label for="meta_description">Meta description <span class="opt">(<span data-count-for="meta_description">0</span>/160)</span></label>
                        <textarea class="ptextarea" id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="A clear, honest summary that makes people click." data-counter data-meta-desc>{{ old('meta_description', $post->meta_description) }}</textarea>
                        <span class="meter"><i data-meter-for="meta_description" data-min="110" data-max="160"></i></span>
                    </div>
                    <div class="pfield">
                        <label for="keywords">Keywords <span class="opt">(separate with commas)</span></label>
                        <input class="pinput" id="keywords" name="keywords" maxlength="500" value="{{ old('keywords', $post->keywords) }}" placeholder="architectural rendering, 3D visualization, render cost">
                    </div>
                    <details class="bmore">
                        <summary>Advanced</summary>
                        <div class="pfield">
                            <label for="canonical_url">Canonical address <span class="opt">(only if this article was first published somewhere else)</span></label>
                            <input class="pinput" id="canonical_url" name="canonical_url" type="url" maxlength="255" value="{{ old('canonical_url', $post->canonical_url) }}" placeholder="https://">
                            @error('canonical_url')<span class="pfield__error">{{ $message }}</span>@enderror
                        </div>
                        <label class="bcheck"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $post->noindex))> Hide this article from search engines (noindex)</label>
                    </details>

                    <div class="seo-check" data-seo-list aria-live="polite"></div>
                </div>
            </div>
        </div>

        <aside class="bform__side">
            {{-- publish --}}
            <div class="pcard bcard bcard--sticky">
                <div class="bcard__head"><h2>Publish</h2><span class="bstage bstage--{{ $stage }}">{{ ['live' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft'][$stage] }}</span></div>
                <div class="bcard__body bpub">
                    <div class="pfield">
                        <label for="publish_local">Publish date <span class="opt">(empty = now)</span></label>
                        <input class="pinput" type="datetime-local" id="publish_local" data-publish-local>
                        <span class="pfield__hint" data-publish-hint>Pick a future date to schedule the article. It goes live by itself.</span>
                        @error('published_at')<span class="pfield__error">{{ $message }}</span>@enderror
                    </div>
                    <label class="bcheck"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))> Feature on the blog page <small>(big card at the top)</small></label>
                    <div class="bpub__btns">
                        <button class="pbtn pbtn--primary pbtn--block" type="submit" data-submit="publish">
                            <x-icon name="send" /> {{ $stage === 'live' ? 'Update article' : 'Publish' }}
                        </button>
                        <button class="pbtn pbtn--ghost pbtn--block" type="submit" data-submit="draft">{{ $stage === 'live' ? 'Switch back to draft' : 'Save draft' }}</button>
                    </div>
                    @unless ($isNew)
                        <div class="bpub__links">
                            <a class="plink" href="{{ $post->url() }}" target="_blank" rel="noopener"><x-icon name="external" /> {{ $post->isLive() ? 'View article' : 'Preview' }}</a>
                            <button class="plink bpub__del" type="button" data-dialog-open="deletePost"><x-icon name="trash" /> Delete</button>
                        </div>
                    @endunless
                </div>
            </div>

            {{-- featured image --}}
            <div class="pcard bcard">
                <div class="bcard__head"><h2>Featured image</h2></div>
                <div class="bcard__body" data-image-box>
                    <label class="bdrop {{ $post->featured_image ? 'has-image' : '' }}" for="featured_image" data-drop-zone>
                        <img src="{{ $post->thumbUrl() }}" alt="" data-image-preview @if (! $post->featured_image) hidden @endif>
                        <span class="bdrop__empty" @if ($post->featured_image) hidden @endif><x-icon name="image" /><b>Add a picture</b><small>Click or drop a JPG, PNG or WebP. Wide pictures (16:9) look best.</small></span>
                        <span class="bdrop__change" @if (! $post->featured_image) hidden @endif><x-icon name="upload" /> Change</span>
                        <input class="visually-hidden" type="file" id="featured_image" name="featured_image" accept="image/png,image/jpeg,image/webp,image/gif" data-image-input>
                    </label>
                    @error('featured_image')<p class="pfield__error">{{ $message }}</p>@enderror
                    @if ($post->featured_image)<label class="bcheck bcheck--remove"><input type="checkbox" name="remove_image" value="1"> Remove the picture</label>@endif
                    <div class="pfield">
                        <label for="featured_image_alt">Picture description <span class="opt">(for screen readers and Google Images)</span></label>
                        <input class="pinput" id="featured_image_alt" name="featured_image_alt" maxlength="200" value="{{ old('featured_image_alt', $post->featured_image_alt) }}" placeholder="Describe what the picture shows" data-alt>
                    </div>
                    <div class="pfield">
                        <label for="image_credit">Credit <span class="opt">(optional)</span></label>
                        <input class="pinput" id="image_credit" name="image_credit" maxlength="200" value="{{ old('image_credit', $post->image_credit) }}" placeholder="e.g. Photo by Name / Pexels">
                    </div>
                </div>
            </div>

            {{-- category + tags --}}
            <div class="pcard bcard">
                <div class="bcard__head"><h2>Category and tags</h2></div>
                <div class="bcard__body">
                    <div class="pfield">
                        <label for="category_id">Category</label>
                        <select class="pselect" id="category_id" name="category_id" data-category>
                            <option value="">Uncategorised</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) old('category_id', $post->category_id) === $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                        <input class="pinput" name="new_category" maxlength="80" value="{{ old('new_category') }}" placeholder="…or type a new category" aria-label="New category name">
                    </div>
                    <div class="pfield">
                        <label for="tag_input">Tags</label>
                        <div class="tagbox" data-tagbox>
                            <div class="tagbox__chips" data-tag-chips></div>
                            <input id="tag_input" class="tagbox__input" type="text" list="knownTags" placeholder="Type a tag, press Enter" autocomplete="off" data-tag-input>
                        </div>
                        <datalist id="knownTags">@foreach ($knownTags as $t)<option value="{{ $t }}">@endforeach</datalist>
                        <input type="hidden" name="tags" value="{{ old('tags', $post->exists ? $post->tags->pluck('name')->implode(', ') : '') }}" data-tags>
                        <span class="pfield__hint">Short topics like “BIM” or “Rendering”. Up to 15.</span>
                    </div>
                </div>
            </div>

            {{-- author --}}
            <div class="pcard bcard">
                <div class="bcard__head"><h2>Author</h2></div>
                <div class="bcard__body">
                    <select class="pselect" name="author_id" aria-label="Author">
                        @foreach ($authors as $a)<option value="{{ $a->id }}" @selected((int) old('author_id', $post->author_id ?? auth()->id()) === $a->id)>{{ $a->name }}</option>@endforeach
                    </select>
                    <span class="pfield__hint">Shown with a photo and short bio under the article. Change yours in <a class="plink" href="{{ route('admin.settings') }}">Settings</a>.</span>
                </div>
            </div>
        </aside>
    </form>

    @unless ($isNew)
        <dialog class="pmodal" id="deletePost" aria-labelledby="deletePostTitle">
            <form method="post" action="{{ route('admin.blog.posts.destroy', $post) }}" data-loading>
                @csrf @method('DELETE')
                <div class="pmodal__head"><h3 id="deletePostTitle">Delete this article?</h3><p>“{{ $post->title }}” and its featured picture are removed for good. Visitors who open its link will see a “page not found”.</p></div>
                <div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-dialog-close>Keep it</button><button class="pbtn pbtn--danger" type="submit"><x-icon name="trash" /> Delete article</button></div>
            </form>
        </dialog>
    @endunless
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
    <script src="{{ asset_v('assets/js/portal-blog.js') }}"></script>
@endpush
