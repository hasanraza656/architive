<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/** A file attached to a chat message or a delivery. Stored on a private disk and only served through an authorised route. */
class OrderFile extends Model
{
    protected $guarded = ['id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function isImage(): bool
    {
        return in_array($this->extension(), config('portal.uploads.inline_image_extensions'), true);
    }

    public function humanSize(): string
    {
        $b = (int) $this->size;
        if ($b >= 1048576) {
            return number_format($b / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($b / 1024)) . ' KB';
    }

    /** Compact array used by the chat JSON feed. */
    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'size' => $this->humanSize(),
            'ext' => $this->extension(),
            'image' => $this->isImage(),
            'url' => route('portal.files.show', $this),
            'preview' => $this->isImage() ? route('portal.files.show', [$this, 'inline' => 1]) : null,
        ];
    }
}
