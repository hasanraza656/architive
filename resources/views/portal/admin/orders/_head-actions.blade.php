{{-- Buttons in the order header (team side). Each button needs the matching permission. Prop: $order, optional $canBilling --}}
@php use App\Enums\OrderStatus as S; $canBilling = $canBilling ?? true; @endphp
@if ($order->status === S::Request)
    @can('orders.edit')<a class="pbtn pbtn--primary pbtn--sm" href="{{ route('admin.orders.edit', $order) }}"><x-icon name="send" /> Create offer</a>@endcan
@elseif ($order->isEditable())
    @can('orders.edit')<a class="pbtn pbtn--ghost pbtn--sm" href="{{ route('admin.orders.edit', $order) }}"><x-icon name="edit" /> {{ $order->status === S::Pending && $order->isRequestOrigin() ? 'Edit offer' : 'Edit' }}</a>@endcan
@endif
@if ($order->status === S::Draft || $order->status === S::Pending)
    @can('orders.send')
        <form method="post" action="{{ route('admin.orders.send', $order) }}" data-loading @if ($order->status === S::Pending) data-confirm="Send the invoice e-mail to {{ $order->customer->email }} again?" @endif>
            @csrf
            <button class="pbtn pbtn--primary pbtn--sm" type="submit"><x-icon name="send" /> {{ $order->status === S::Draft ? 'Send invoice' : 'Re-send' }}</button>
        </form>
    @endcan
@endif
@if ($order->status !== S::Draft && $canBilling)
    <button class="pbtn pbtn--ghost pbtn--sm" type="button" data-copy="{{ $order->customerUrl() }}"><x-icon name="copy" /> <span data-copy-label>Copy customer link</span></button>
@endif
@if ($order->status === S::Active)
    @can('orders.deliver')<button class="pbtn pbtn--primary pbtn--sm" type="button" data-dialog-open="deliverDialog"><x-icon name="package" /> Deliver work</button>@endcan
@endif
@if ($order->status->isRunning())
    @can('orders.complete')
        <form method="post" action="{{ route('admin.orders.complete', $order) }}" data-loading data-confirm="Mark this order as completed?">@csrf
            <button class="pbtn pbtn--dark pbtn--sm" type="submit"><x-icon name="check" /> Mark completed</button>
        </form>
    @endcan
@endif
@unless ($order->status->isClosed())
    @can('orders.cancel')<button class="pbtn pbtn--danger pbtn--sm" type="button" data-dialog-open="cancelDialog"><x-icon name="x" /> {{ $order->status === S::Request ? 'Close request' : 'Cancel order' }}</button>@endcan
@endunless
@can('orders.delete')<button class="pbtn pbtn--ghost pbtn--sm" type="button" data-dialog-open="deleteDialog"><x-icon name="trash" /> Delete</button>@endcan
