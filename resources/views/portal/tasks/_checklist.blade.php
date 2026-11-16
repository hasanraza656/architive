{{-- To-do checklist (Notion style) for a task or a project. Drawn and kept up to date by public/assets/js/portal-checklist.js.
     Props: $owner ('task' | 'project'), $ownerId, $canManage (may tick / add / edit), optional $title --}}
@php
    $clItems = \App\Models\ChecklistItem::with('doneBy')->where('owner_type', $owner)->where('owner_id', $ownerId)->orderBy('position')->orderBy('id')->get()->map->present()->values();
    $clVersion = md5($clItems->toJson());
    $me = auth()->user();
@endphp
<section class="clist {{ $canManage ? '' : 'is-readonly' }}" data-checklist data-owner="{{ $owner }}" data-owner-id="{{ $ownerId }}"
         data-url="{{ route('admin.checklists.index', ['type' => $owner, 'id' => $ownerId]) }}"
         data-reorder-url="{{ route('admin.checklists.reorder', ['type' => $owner, 'id' => $ownerId]) }}"
         data-item-url="{{ route('admin.checklists.update', ['item' => '__ID__']) }}"
         data-can="{{ $canManage ? 1 : 0 }}" data-me="{{ $me->id }}" data-me-name="{{ $me->first_name }}" data-v="{{ $clVersion }}">
    <script type="application/json" data-cl-seed>{!! json_encode($clItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    <header class="clist__head">
        <h3 class="clist__title">{{ $title ?? 'Checklist' }}</h3>
        <span class="clist__count" data-cl-count aria-live="polite"></span>
    </header>
    <div class="clist__bar" aria-hidden="true"><i data-cl-bar></i></div>
    <ul class="clist__list" data-cl-list aria-label="{{ $title ?? 'Checklist' }}"></ul>
    @if ($canManage)
        <p class="clist__hint">Type and press <kbd>Enter</kbd> for the next one. <kbd>Ctrl</kbd>+<kbd>Enter</kbd> ticks a line. Paste a list to add many at once.</p>
    @endif
</section>
