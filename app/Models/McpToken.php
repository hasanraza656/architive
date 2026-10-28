<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** One access key for the MCP endpoint. The key itself is shown once when created; only its SHA-256 hash is kept. */
class McpToken extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['last_used_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: McpToken, 1: string} the saved token and the plain key (never stored) */
    public static function issue(User $user, string $name): array
    {
        $plain = 'arc_' . Str::random(48);
        $token = static::create([
            'user_id' => $user->id, 'name' => $name, 'token_hash' => hash('sha256', $plain), 'hint' => substr($plain, -4),
        ]);

        return [$token, $plain];
    }

    public static function findByKey(string $plain): ?self
    {
        if (! preg_match('/^arc_[A-Za-z0-9]{48}$/', $plain)) {
            return null;
        }

        return static::where('token_hash', hash('sha256', $plain))->whereNull('revoked_at')->with('user')->first();
    }
}
