<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OrderFile;
use App\Services\Drive\DriveSync;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves uploaded files only to people who may see the order. Pictures (and previews) show inline, voice and video stream
 * with byte-range support so they can be played and skipped while loading, everything else downloads.
 */
class FileController extends Controller
{
    public function show(OrderFile $file): BinaryFileResponse
    {
        $this->authorize('view', $file->order);

        $disk = Storage::disk(config('portal.uploads.disk'));
        $thumb = request()->boolean('thumb') && $file->thumb_path;
        $path = $thumb ? $file->thumb_path : $file->path;
        if (! $disk->exists($path)) {
            // the local original was moved to Google Drive after a while: fetch it back (and keep it for the next visitors)
            abort_unless(! $thumb && $file->drive_file_id, 404);
            try {
                app(DriveSync::class)->ensureLocal($file);
            } catch (\Throwable $e) {
                report($e);
                abort(503, 'This file is being brought back from storage. Please try again in a moment.');
            }
            abort_unless($disk->exists($path), 404);
        } elseif (! $thumb && $file->drive_file_id && (! $file->last_opened_at || $file->last_opened_at->lt(now()->subHour()))) {
            $file->forceFill(['last_opened_at' => now()])->saveQuietly();      // recently used files are not removed from the server
        }

        $kind = $file->displayKind();
        $inline = $thumb || (request()->boolean('inline') && $kind === 'image') || in_array($kind, ['voice', 'video'], true);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ];
        if ($thumb) {
            $headers['Content-Type'] = 'image/webp';
        } elseif ($inline && $file->mime && preg_match('#^(image|audio|video)/#', $file->mime)) {
            $headers['Content-Type'] = $file->mime;
        }

        // BinaryFileResponse answers Range requests (206), which audio/video elements and PDF.js rely on
        return response()->file($disk->path($path), $headers)
            ->setContentDisposition($inline ? 'inline' : 'attachment', $file->original_name);
    }
}
