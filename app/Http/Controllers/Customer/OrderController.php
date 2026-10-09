<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Chat\ChatService;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** A customer's view of one order: invoice (and Pay button), progress, conversation, deliveries. */
class OrderController extends Controller
{
    public function __construct(private OrderWorkflow $workflow, private ChatService $chat, private \App\Services\Payments\StripeCheckout $stripe)
    {
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $this->stripe->reconcile($order);          // paid in Stripe but tab was closed? record it now
        $order->refresh()->load(['customer', 'items', 'events.user', 'deliveries.files', 'deliveries.user']);
        $this->chat->markRead($order, $request->user());

        return view('portal.customer.order', [
            'order' => $order,
            'feed' => $this->chat->feed($order, $request->user()),
            'canceledCheckout' => $request->boolean('canceled'),
        ]);
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        return $this->run($order, fn () => $this->workflow->acceptDelivery($order, $request->user()), 'Thank you! The order is now completed.');
    }

    public function revision(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:3000']], ['reason.required' => 'Tell us what you would like changed.']);

        return $this->run($order, fn () => $this->workflow->requestRevision($order, $request->user(), $data['reason']), 'Revision requested. We will get back to you in the conversation.');
    }

    private function run(Order $order, \Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (\DomainException $e) {
            return redirect()->to($order->customerUrl())->with('error', $e->getMessage());
        }

        return redirect()->to($order->customerUrl())->with('success', $success);
    }
}
