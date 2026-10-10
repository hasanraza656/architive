<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/** Customer home: their orders, with the ones that need action (unpaid / delivered) first. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('customer_id', $user->id)
            ->where('status', '!=', OrderStatus::Draft->value)
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)
                    ->whereRaw('order_messages.id > COALESCE((select last_read_message_id from order_read_states where order_read_states.order_id = order_messages.order_id and order_read_states.user_id = ?), 0)', [$user->id]);
            }])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'delivered' THEN 1 WHEN 'active' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
            ->latest('id')->get();

        $requests = $orders->filter(fn ($o) => $o->status->isLead())->values();
        $orders = $orders->reject(fn ($o) => $o->status->isLead())->values();

        $stats = [
            'requests' => $requests->count(),
            'awaiting' => $orders->where('status', OrderStatus::Pending)->count(),
            'running' => $orders->filter(fn ($o) => $o->status->isRunning())->count(),
            'completed' => $orders->where('status', OrderStatus::Completed)->count(),
        ];

        return view('portal.customer.dashboard', compact('orders', 'requests', 'stats'));
    }
}
