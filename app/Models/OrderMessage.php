<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One chat message. kind: text | offer | system. A message with thread_root_id set is a reply inside that message's thread
 * (the main timeline only shows root messages; the root shows how many replies it has).
 */
class OrderMessage extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['edited_at' => 'datetime', 'user_id' => 'integer', 'order_id' => 'integer', 'thread_root_id' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class, 'message_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(OrderMessageReaction::class, 'message_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(OrderMessageMention::class, 'message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'thread_root_id')->orderBy('id');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'thread_root_id');
    }

    /** Messages that appear in the main timeline (not thread replies). */
    public function scopeTimeline(Builder $q): Builder
    {
        return $q->whereNull('thread_root_id');
    }

    public function isReply(): bool
    {
        return $this->thread_root_id !== null;
    }
}
