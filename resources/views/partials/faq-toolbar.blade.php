{{-- Live search + category chips for a [data-faq] list on the same page --}}
<div class="faq-tools" data-faq-tools data-reveal>
    <label class="faq-search">
        <x-icon name="search" />
        <span class="visually-hidden">Search questions</span>
        <input type="search" placeholder="Search questions (e.g. AutoCAD, permit drawings, NDA, pricing, revisions)…" autocomplete="off" data-faq-search>
    </label>
    <div class="faq-cats" role="group" aria-label="Filter questions by topic">
        <button type="button" class="is-active" data-faq-cat="">All FAQs</button>
        @foreach (\App\Support\Content::faqCategories() as $cat)
            <button type="button" data-faq-cat="{{ $cat }}">{{ $cat }}</button>
        @endforeach
    </div>
</div>
