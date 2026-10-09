<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/** Creates and edits orders (invoices): line items, totals, numbering. Status changes live in OrderWorkflow. */
class OrderService
{
    /**
     * @param  array{customer_id:int,title:string,notes?:?string,due_at?:mixed,discount?:mixed,tax_rate?:mixed,items:array}  $data
     */
    public function create(array $data, User $admin): Order
    {
        return DB::transaction(function () use ($data, $admin) {
            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'created_by' => $admin->id,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'currency' => config('portal.currency'),
                'status' => OrderStatus::Draft,
                'due_at' => $data['due_at'] ?? null,
            ]);

            $order->update(['number' => config('portal.order_prefix') . (config('portal.order_number_start') + $order->id)]);
            $this->fill($order, $data);
            $order->events()->create(['type' => 'created', 'message' => 'Order created', 'user_id' => $admin->id]);

            return $order->fresh(['items', 'customer']);
        });
    }

    public function update(Order $order, array $data, User $admin): Order
    {
        return DB::transaction(function () use ($order, $data, $admin) {
            $order->update([
                'customer_id' => $data['customer_id'],
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);
            $this->fill($order, $data);
            $order->events()->create(['type' => 'edited', 'message' => 'Order details updated', 'user_id' => $admin->id]);

            return $order->fresh(['items', 'customer']);
        });
    }

    /** Replace the line items and recompute totals. */
    private function fill(Order $order, array $data): void
    {
        $order->items()->delete();

        $subtotal = 0;
        foreach (array_values($data['items']) as $i => $row) {
            $qty = max(0, (float) str_replace(',', '', (string) ($row['quantity'] ?? 1)));
            $unit = Money::toCents($row['unit_price'] ?? 0);
            $item = $order->items()->create([
                'description' => trim((string) $row['description']),
                'quantity' => $qty,
                'unit_price_cents' => $unit,
                'position' => $i,
            ]);
            $subtotal += $item->lineCents();
        }

        $discount = min($subtotal, Money::toCents($data['discount'] ?? 0));
        $rate = max(0, min(100, (float) ($data['tax_rate'] ?? 0)));
        $tax = (int) round(($subtotal - $discount) * $rate / 100);

        $order->update([
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'tax_rate' => $rate,
            'tax_cents' => $tax,
            'total_cents' => $subtotal - $discount + $tax,
        ]);
    }
}
