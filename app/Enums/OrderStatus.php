<?php

namespace App\Enums;

/** Life of an order: draft -> pending (invoice sent) -> active (paid) -> delivered -> completed, or cancelled at any open stage. */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting payment',
            self::Active => 'In progress',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** CSS modifier used by the status badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'grey',
            self::Pending => 'amber',
            self::Active => 'blue',
            self::Delivered => 'violet',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /** Paid and not finished yet. */
    public function isRunning(): bool
    {
        return in_array($this, [self::Active, self::Delivered], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
