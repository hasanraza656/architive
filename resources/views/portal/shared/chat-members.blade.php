{{-- "People in this chat" drawer. Props: $order, $people, $memberIds, $canManage, $candidates, $viewer --}}
<div class="cmembers" data-members data-order="{{ $order->number }}"
     data-add-url="{{ route('portal.chat.members.store', $order) }}" data-remove-template="{{ route('portal.chat.members.destroy', ['order' => $order, 'user' => '__ID__']) }}">
    <p class="cmembers__lead">{{ $viewer->isCustomer() ? 'The people you are talking with in this conversation.' : 'Everyone here can read and write in this conversation and can be tagged with @.' }}</p>

    <ul class="cmembers__list">
        @foreach ($people as $p)
            @php $isMember = in_array($p->id, $memberIds, true); @endphp
            <li data-member="{{ $p->id }}">
                <span class="pavatar">@if ($p->avatarUrl())<img src="{{ $p->avatarUrl() }}" alt="">@else{{ $p->initials }}@endif</span>
                <div class="cmembers__who">
                    <b>{{ $p->name ?: $p->email }}@if ($p->id === $viewer->id) <small>(you)</small>@endif</b>
                    <small>
                        @if ($p->isCustomer())Customer
                        @elseif ($p->isAdmin()){{ $p->job_title ?: 'Super admin' }} · always in every chat
                        @else{{ $p->job_title ?: ($p->teamRole?->name ?? 'Team') }}@if (! $isMember) · can open every chat @endif
                        @endif
                    </small>
                </div>
                @if ($isMember && ($canManage || $p->id === $viewer->id))
                    <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-remove="{{ $p->id }}">{{ $p->id === $viewer->id ? 'Leave' : 'Remove' }}</button>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($canManage)
        <div class="cmembers__add">
            <h3>Add a team member</h3>
            @if ($candidates->isEmpty())
                <p class="muted" style="margin:0;font-size:.88rem">Everyone on the team is already here.</p>
            @else
                <div class="cmembers__pick">
                    @foreach ($candidates as $c)
                        <button type="button" class="cmembers__cand" data-add="{{ $c->id }}">
                            <span class="pavatar pavatar--sm">@if ($c->avatarUrl())<img src="{{ $c->avatarUrl() }}" alt="">@else{{ $c->initials }}@endif</span>
                            <span><b>{{ $c->name }}</b><small>{{ $c->job_title ?: $c->teamRole?->name }}</small></span>
                            <x-icon name="plus" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
