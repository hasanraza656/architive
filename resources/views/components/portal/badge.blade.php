@props(['status'])
<span class="pbadge pbadge--{{ $status->tone() }}">{{ $status->label() }}</span>
