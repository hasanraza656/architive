<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'float'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Line total in cents (qty can be fractional, e.g. 2.5 hours). */
    public function lineCents(): int
    {
        return (int) round($this->quantity * $this->unit_price_cents);
    }
}
