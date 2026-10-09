<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderMessage;
use App\Models\OrderReadState;
use App\Models\User;

/** Admin home: headline numbers, what is due next, and what needs attention. */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $count = fn (OrderStatus ...$s) => Order::whereIn('status', array_map(fn ($x) => $x->value, $s))->count();

        $stats = [
            'customers' => User::customers()->count(),
            'orders' => Order::where('status', '!=', OrderStatus::Draft->value)->count(),
            'active' => $count(OrderStatus::Active, OrderStatus::Delivered),
            'completed' => $count(OrderStatus::Completed),
            'cancelled' => $count(OrderStatus::Cancelled),
            'awaiting' => $count(OrderStatus::Pending),
            'revenue' => (int) Order::whereNotNull('paid_at')->where('status', '!=', OrderStatus::Cancelled->value)->sum('total_cents'),
        ];

        $upcoming = Order::with('customer')->running()->whereNotNull('due_at')->orderBy('due_at')->limit(8)->get();
        $recent = Order::with('customer')->where('status', '!=', OrderStatus::Draft->value)->latest('id')->limit(6)->get();
        $activity = OrderEvent::with(['order', 'user'])->latest('id')->limit(8)->get();

        // orders where the customer wrote something no admin has read yet
        $adminIds = User::where('role', User::ROLE_ADMIN)->pluck('id');
        $unread = Order::with('customer')
            ->whereHas('messages', function ($q) use ($adminIds) {
                $q->whereHas('user', fn ($u) => $u->where('role', User::ROLE_CUSTOMER))
                    ->whereRaw('order_messages.id > COALESCE((select max(last_read_message_id) from order_read_states where order_read_states.order_id = order_messages.order_id and order_read_states.user_id in (' . ($adminIds->implode(',') ?: '0') . ')), 0)');
            })->latest('updated_at')->limit(6)->get();

        return view('portal.admin.dashboard', compact('stats', 'upcoming', 'recent', 'activity', 'unread'));
    }
}
