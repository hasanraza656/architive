<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Super admins can touch every order. Customers only see their own (never drafts). Team members see an order when their
 * role allows it (orders.view or chat.access_all) or when they were added to its conversation.
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isCustomer()) {
            return (int) $user->id === (int) $order->customer_id && $order->isVisibleToCustomer();
        }
        if (! $user->isStaff()) {
            return false;
        }

        return $user->hasAnyPermission('orders.view', 'chat.access_all') || ($order->isVisibleToCustomer() && $order->hasChatMember($user));
    }

    public function chat(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->isChatOpen();
    }

    /** Prices, the invoice tab, offers and payment details. */
    public function billing(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($user->isStaff() && $user->hasPermission('orders.view'));
    }

    public function manageMembers(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $user->isTeam() && $user->hasPermission('chat.manage_members');
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->isCustomer() && (int) $user->id === (int) $order->customer_id && $order->isPayable();
    }
}
