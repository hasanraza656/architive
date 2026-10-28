{{-- The list view of one project: tasks grouped by status. Props: $columns (status => tasks) --}}
@php $any = collect($columns)->flatten()->isNotEmpty(); @endphp
@foreach (\App\Models\Task::STATUSES as $key => $def)
    @if ($columns[$key]->isNotEmpty())
        <section class="tgroup tgroup--{{ $def['tone'] }}">
            <h2 class="tgroup__head"><span>{{ $def['label'] }}</span><small>{{ $columns[$key]->count() }}</small></h2>
            <div class="pcard tlist">@foreach ($columns[$key] as $t)@include('portal.tasks._row', ['task' => $t, 'showProject' => false])@endforeach</div>
        </section>
    @endif
@endforeach
@unless ($any)<div class="pcard empty"><x-icon name="check-circle" /><b>No tasks yet</b><span>Add the first one from the board.</span></div>@endunless
