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

    protected $casts = [
        'meta' => 'array',
        'drive_synced_at' => 'datetime', 'drive_next_attempt_at' => 'datetime', 'local_pruned_at' => 'datetime', 'last_opened_at' => 'datetime',
    ];

    /** What this file is for the chat: image | voice | video | pdf | file (set when it is uploaded, old rows fall back to the extension). */
    public function displayKind(): string
    {
        if (($this->kind ?? 'file') !== 'file') {
            return $this->kind;
        }

        return $this->isImage() ? 'image' : ($this->extension() === 'pdf' ? 'pdf' : 'file');
    }

    /** Compact array used by the chat JSON feed. */
    public function toChatArray(): array
    {
        $kind = $this->displayKind();
        $meta = $this->meta ?? [];

        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'size' => $this->humanSize(),
            'ext' => $this->extension(),
            'kind' => $kind,
            'image' => $kind === 'image',
            'url' => route('portal.files.show', $this),
            'preview' => $kind === 'image' ? route('portal.files.show', [$this, 'inline' => 1]) : null,
            'thumb' => $this->thumb_path ? route('portal.files.show', [$this, 'thumb' => 1]) : ($kind === 'image' ? route('portal.files.show', [$this, 'inline' => 1]) : null),
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'peaks' => $meta['peaks'] ?? null,
            'pages' => $meta['pages'] ?? null,
            'annotated_of' => $this->annotated_of,
        ];
    }
}
