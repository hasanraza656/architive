<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Orders\OrderService;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin: list, create/edit (invoice builder) and open orders. Status changes are in OrderActionController. */
class OrderController extends Controller
{
    public function __construct(private OrderService $orders, private OrderWorkflow $workflow, private ChatService $chat)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $orders = Order::with('customer')
            ->when($status && OrderStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('number', 'like', "%$q%")->orWhere('title', 'like', "%$q%")
                ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))))
            ->latest('id')->paginate(15)->withQueryString();

        $counts = Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('portal.admin.orders.index', compact('orders', 'status', 'q', 'counts'));
    }

    public function create(Request $request)
    {
        return view('portal.admin.orders.form', $this->formData(new Order(['tax_rate' => 0]), (int) $request->query('customer')));
    }

    public function store(SaveOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create($request->orderData(), $request->user());

        return $this->afterSave($request, $order, 'Order created.');
    }

    public function show(Order $order, Request $request, \App\Services\Payments\StripeCheckout $stripe)
    {
        $stripe->reconcile($order);
        $order->refresh();
        $order->load(['customer', 'items', 'events.user', 'deliveries.files', 'deliveries.user']);
        $this->chat->markRead($order, $request->user());

        return view('portal.admin.orders.show', [
            'order' => $order,
            'feed' => $this->chat->feed($order, $request->user()),
            'maxUploadMb' => min(config('portal.uploads.max_kb') / 1024, (int) ini_get('upload_max_filesize')),
        ]);
    }

    public function edit(Order $order)
    {
        abort_unless($order->isEditable(), 403, 'Paid or closed orders cannot be edited.');

        return view('portal.admin.orders.form', $this->formData($order->load('items')));
    }

    public function update(SaveOrderRequest $request, Order $order): RedirectResponse
    {
        abort_unless($order->isEditable(), 403, 'Paid or closed orders cannot be edited.');
        $order = $this->orders->update($order, $request->orderData(), $request->user());

        return $this->afterSave($request, $order, 'Order saved.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        abort_unless($order->status === OrderStatus::Draft, 403, 'Only drafts can be deleted. Cancel the order instead.');
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Draft deleted.');
    }

    /* ------------------------------------------------------------ */

    private function afterSave(SaveOrderRequest $request, Order $order, string $message): RedirectResponse
    {
        if ($request->input('action') !== 'send') {
            return redirect()->route('admin.orders.show', $order)->with('success', $message);
        }

        try {
            $mailed = $this->workflow->send($order, $request->user());
        } catch (\DomainException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)->with(
            $mailed ? 'success' : 'error',
            $mailed ? 'Invoice sent to ' . $order->customer->email . '.' : 'The order is saved and payable, but the e-mail could not be sent. Check the mail settings, or copy the customer link and share it yourself.'
        );
    }

    private function formData(Order $order, ?int $presetCustomer = null): array
    {
        return [
            'order' => $order,
            'customers' => User::customers()->where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values(),
            'countries' => config('countries'),
            'presetCustomer' => $presetCustomer ?: $order->customer_id,
        ];
    }
}
