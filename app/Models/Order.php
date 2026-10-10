<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An order is also the invoice: line items + totals, then the shared workspace (chat, deliveries, timeline) once it is paid. */
class Order extends Model
{
    protected $guarded = ['id'];

    /** Deleting an order also removes its uploaded files from the private disk (database rows go via cascade). */
    protected static function booted(): void
    {
        static::deleting(function (Order $order) {
            \Illuminate\Support\Facades\Storage::disk(config('portal.uploads.disk'))->deleteDirectory('orders/' . $order->id);
        });
    }

    protected $casts = [
        'status' => OrderStatus::class,
        'tax_rate' => 'decimal:2',
        'requested_deadline' => 'date',
        'due_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** Customers see /account/orders/ARC-1001 */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /* ------------------------------------------------------------ Relations */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->orderBy('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderDelivery::class)->latest('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function readStates(): HasMany
    {
        return $this->hasMany(OrderReadState::class);
    }

    /* ------------------------------------------------------------ Scopes */

    public function scopeRunning($query)
    {
        return $query->whereIn('status', [OrderStatus::Active->value, OrderStatus::Delivered->value]);
    }

    /* ------------------------------------------------------------ State helpers */

    public function isPayable(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [OrderStatus::Draft, OrderStatus::Request, OrderStatus::Pending], true);
    }

    /** Draft orders are invisible to customers; everything else can be opened by its customer. */
    public function isVisibleToCustomer(): bool
    {
        return $this->status !== OrderStatus::Draft;
    }

    public function isChatOpen(): bool
    {
        return ! in_array($this->status, [OrderStatus::Draft, OrderStatus::Cancelled], true);
    }

    /** Came from a visitor/customer request (website form or portal) rather than an admin-made invoice. */
    public function isRequestOrigin(): bool
    {
        return $this->source !== 'admin';
    }

    public function serviceLabel(): ?string
    {
        return \App\Mail\ContactEnquiry::SERVICES[$this->service] ?? null;
    }

    public function audienceLabel(): ?string
    {
        return \App\Mail\ContactEnquiry::AUDIENCES[$this->audience] ?? null;
    }

    public function total(): int
    {
        return (int) $this->total_cents;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->status === OrderStatus::Active && $this->due_at->isPast();
    }

    /** Where the customer opens this order (sign-in is requested first if needed). */
    public function customerUrl(): string
    {
        return route('customer.orders.show', $this);
    }

    public function adminUrl(): string
    {
        return route('admin.orders.show', $this);
    }
}
