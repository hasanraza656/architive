<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/** Admins can touch every order; a customer can only see their own orders, and never drafts. */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $order->customer_id && $order->isVisibleToCustomer();
    }

    public function chat(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->isChatOpen();
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->isCustomer() && $user->id === $order->customer_id && $order->isPayable();
    }
}
