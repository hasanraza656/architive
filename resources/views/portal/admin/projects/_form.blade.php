{{-- Fields of the project dialog (create + edit). Props: $project (new Project for create), $form (people, orders) --}}
@php
    $isNew = ! $project->exists;
    $memberIds = old('member_ids', $isNew ? [auth()->id()] : $project->members->pluck('id')->all());
    $leadId = old('lead_id', $isNew ? auth()->id() : $project->members->firstWhere('pivot.role', 'lead')?->id);
@endphp
<div class="pmodal__body pform">
    <x-portal.field name="name" label="Project name"><input class="pinput" id="p_name_{{ $project->id ?? 'new' }}" name="name" maxlength="160" value="{{ old('name', $project->name) }}" placeholder="e.g. Riverside house renders" required></x-portal.field>
    <x-portal.field name="description" label="What is it about?" optional><textarea class="ptextarea" name="description" rows="3" maxlength="4000" data-autosize>{{ old('description', $project->description) }}</textarea></x-portal.field>
    <div class="prow">
        <x-portal.field name="start_date" label="Start" optional><input class="pinput" type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"></x-portal.field>
        <x-portal.field name="due_date" label="Due" optional><input class="pinput" type="date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}"></x-portal.field>
    </div>
    <div class="prow">
        <x-portal.field name="status" label="Status">
            <select class="pselect" name="status">@foreach (\App\Models\Project::STATUSES as $k => $d)<option value="{{ $k }}" @selected(old('status', $project->status ?: 'active') === $k)>{{ $d['label'] }}</option>@endforeach</select>
        </x-portal.field>
        <x-portal.field name="color" label="Colour"><input class="pinput pinput--color" type="color" name="color" value="{{ old('color', $project->color ?: '#FFD60A') }}"></x-portal.field>
    </div>

    <div class="pfield">
        <label>People on this project</label>
        <div class="memberpick">
            @foreach ($form['people'] as $p)
                <label class="memberpick__opt">
                    <input type="checkbox" name="member_ids[]" value="{{ $p->id }}" @checked(in_array($p->id, $memberIds, true))>
                    <span class="pavatar pavatar--xs">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>
                    <span>{{ $p->name }}<small>{{ $p->job_title ?: ($p->isAdmin() ? 'Super admin' : $p->teamRole?->name) }}</small></span>
                    <span class="memberpick__lead" title="Project lead"><input type="radio" name="lead_id" value="{{ $p->id }}" @checked((int) $leadId === $p->id) aria-label="Make {{ $p->name }} the lead"><i>Lead</i></span>
                </label>
            @endforeach
        </div>
        <span class="pfield__hint">People on a project can see its tasks. The lead can edit the project and its tasks.</span>
    </div>

    <details class="bmore" @if (old('order_id', $project->order_id)) open @endif>
        <summary>Link to a customer order (optional)</summary>
        <div class="pfield">
            <label for="p_order_{{ $project->id ?? 'new' }}">Order</label>
            <select class="pselect" id="p_order_{{ $project->id ?? 'new' }}" name="order_id">
                <option value="">Not linked</option>
                @foreach ($form['orders'] as $o)<option value="{{ $o->id }}" @selected((int) old('order_id', $project->order_id) === $o->id)>{{ $o->number }} · {{ \Illuminate\Support\Str::limit($o->title, 40) }} · {{ $o->customer?->name }}</option>@endforeach
            </select>
        </div>
        <label class="bcheck"><input type="checkbox" name="visible_to_customer" value="1" @checked(old('visible_to_customer', $project->visible_to_customer))> Let this customer open the tasks and project when they are tagged in their chat <small>(read-only, comments stay private)</small></label>
    </details>
</div>
