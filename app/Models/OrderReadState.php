<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What one person has read in one order's chat, when they were last on the page and when we last emailed them. */
class OrderReadState extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_emailed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds(config('portal.chat.online_window_seconds')));
    }
}
