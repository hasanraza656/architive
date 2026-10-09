<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OrderFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serves uploaded files only to people who may see the order. Images can be shown inline; everything else downloads. */
class FileController extends Controller
{
    public function show(OrderFile $file): StreamedResponse
    {
        $this->authorize('view', $file->order);

        $disk = Storage::disk(config('portal.uploads.disk'));
        abort_unless($disk->exists($file->path), 404);

        $inline = request()->boolean('inline') && $file->isImage();
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ];
        if ($inline) {
            $headers['Content-Type'] = $file->mime ?: 'application/octet-stream';
        }

        return $disk->response($file->path, $file->original_name, $headers, $inline ? 'inline' : 'attachment');
    }
}
