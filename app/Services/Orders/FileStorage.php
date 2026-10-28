<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Saves uploads for an order inside /public/uploads/orders (never reachable by URL, served through an authorised route).
 * Besides the file itself it works out what it is (picture, voice message, screen recording, PDF), keeps a small preview
 * image so long chats stay fast, and stores sizes / duration / waveform for the players.
 */
class FileStorage
{
    private const AUDIO_EXT = ['webm', 'weba', 'ogg', 'oga', 'opus', 'm4a', 'mp4', 'mp3', 'wav', 'aac'];

    private const VIDEO_EXT = ['webm', 'mp4', 'mov', 'm4v', 'ogv'];

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array{message_id?:int,delivery_id?:int}  $link
     * @param  array<int, array{kind?:string,duration?:int,meta?:array,preview?:UploadedFile|null,annotated_of?:int|null}>  $extras  per file, same index as $files
     * @return array<int, OrderFile>
     */
    public function storeMany(array $files, Order $order, User $user, array $link = [], array $extras = []): array
    {
        $saved = [];
        $this->guardPrivateFolder();
        $disk = config('portal.uploads.disk');
        $images = app(\App\Services\Blog\ImageStore::class);

        foreach ($files as $i => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $extra = $extras[$i] ?? [];
            $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
            $ext = strtolower($file->getClientOriginalExtension());
            $kind = $this->kind($mime, $ext, $extra['kind'] ?? null);
            if (in_array($kind, ['voice', 'video'], true)) {
                $ext = $this->mediaExtension($kind, $ext, $mime);
            }

            $uuid = (string) Str::uuid();
            $path = $file->storeAs('orders/' . $order->id, $uuid . ($ext ? '.' . $ext : ''), $disk);

            $width = $height = null;
            $thumb = null;
            if ($kind === 'image' || ! empty($extra['preview'])) {
                $source = $kind === 'image' ? (string) file_get_contents($file->getRealPath()) : (string) file_get_contents($extra['preview']->getRealPath());
                $info = @getimagesizefromstring($source);
                if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true) && $info[0] * $info[1] < 40000000) {
                    if ($kind === 'image') {
                        [$width, $height] = [$info[0], $info[1]];
                    }
                    $small = $images->thumbnail($source, 640);
                    if ($small) {
                        $thumb = 'orders/' . $order->id . '/' . $uuid . '-t.webp';
                        Storage::disk($disk)->put($thumb, $small);
                    }
                }
            }

            $meta = $this->cleanMeta($extra['meta'] ?? []);

            $saved[] = $order->files()->create($link + [
                'user_id' => $user->id,
                'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                'path' => $path,
                'thumb_path' => $thumb,
                'mime' => Str::limit($mime, 120, ''),
                'kind' => $kind,
                'duration' => isset($extra['duration']) ? min(86400, max(0, (int) $extra['duration'])) : null,
                'width' => $width ?? (isset($extra['width']) ? (int) $extra['width'] : null),
                'height' => $height ?? (isset($extra['height']) ? (int) $extra['height'] : null),
                'size' => $file->getSize(),
                'meta' => $meta ?: null,
                'annotated_of' => $extra['annotated_of'] ?? null,
            ]);
        }

        return $saved;
    }

    /** Removes every stored file of an order (used when the order is deleted). */
    public function purge(Order $order): void
    {
        Storage::disk(config('portal.uploads.disk'))->deleteDirectory('orders/' . $order->id);
    }

    private function kind(string $mime, string $ext, ?string $wanted): string
    {
        if (str_starts_with($mime, 'image/') && in_array($ext, config('portal.uploads.inline_image_extensions'), true)) {
            return 'image';
        }
        // browsers record audio as audio/webm, which file-type detection often reports as video/webm: accept both for voice notes
        if ($wanted === 'voice' && (str_starts_with($mime, 'audio/') || in_array($mime, ['video/webm', 'video/ogg', 'video/mp4', 'application/ogg'], true))) {
            return 'voice';
        }
        if ($wanted === 'video' && str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }

        return 'file';
    }

    private function mediaExtension(string $kind, string $ext, string $mime): string
    {
        $allowed = $kind === 'voice' ? self::AUDIO_EXT : self::VIDEO_EXT;
        if (in_array($ext, $allowed, true)) {
            return $ext;
        }

        return match (true) {
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'mp4') => $kind === 'voice' ? 'm4a' : 'mp4',
            str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => $kind === 'voice' ? 'webm' : 'mp4',
        };
    }

    /** Only small, plain data survives: waveform peaks (0-100) and a page count. */
    private function cleanMeta(array $meta): array
    {
        $out = [];
        if (! empty($meta['peaks']) && is_array($meta['peaks'])) {
            $out['peaks'] = array_map(fn ($v) => max(0, min(100, (int) $v)), array_slice(array_values($meta['peaks']), 0, 96));
        }
        if (! empty($meta['pages'])) {
            $out['pages'] = max(1, min(5000, (int) $meta['pages']));
        }

        return $out;
    }

    /**
     * Order files sit inside /public/uploads/orders (no storage:link needed) but are private: this .htaccess stops anyone
     * opening them by URL, while the portal still streams them to the right people through an authorised route.
     */
    private function guardPrivateFolder(): void
    {
        $dir = public_path('uploads/orders');
        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (config('portal.uploads.disk') !== 'uploads' || is_file($htaccess)) {
            return;
        }
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($htaccess, "# Private order files: only the portal may read them.\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
    }
}
