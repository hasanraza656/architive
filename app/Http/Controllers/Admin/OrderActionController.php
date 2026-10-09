<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliverOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin buttons on an order: send invoice, deliver, complete, cancel. All rules live in OrderWorkflow. */
class OrderActionController extends Controller
{
    public function __construct(private OrderWorkflow $workflow)
    {
    }

    public function send(Request $request, Order $order): RedirectResponse
    {
        return $this->run($order, function () use ($request, $order) {
            $mailed = $this->workflow->send($order, $request->user());

            return $mailed
                ? ['success', 'Invoice sent to ' . $order->customer->email . '.']
                : ['error', 'The order is payable now, but the e-mail could not be sent. Check the mail settings, or copy the customer link and share it yourself.'];
        });
    }

    public function deliver(DeliverOrderRequest $request, Order $order): RedirectResponse
    {
        return $this->run($order, function () use ($request, $order) {
            $this->workflow->deliver($order, $request->user(), $request->input('note'), $request->file('files', []));

            return ['success', 'Work delivered. The customer has been notified.'];
        });
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        return $this->run($order, function () use ($request, $order) {
            $this->workflow->complete($order, $request->user());

            return ['success', 'Order marked as completed.'];
        });
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:300']]);

        return $this->run($order, function () use ($request, $order) {
            $this->workflow->cancel($order, $request->user(), $request->input('reason'));

            return ['success', 'Order cancelled.' . ($order->paid_at ? ' It was already paid: refund it from your Stripe dashboard.' : '')];
        });
    }

    /** Runs an action and turns a rule violation into a friendly message instead of an error page. */
    private function run(Order $order, \Closure $action): RedirectResponse
    {
        try {
            [$type, $message] = $action();
        } catch (\DomainException $e) {
            [$type, $message] = ['error', $e->getMessage()];
        }

        return redirect()->route('admin.orders.show', $order)->with($type, $message);
    }
}
