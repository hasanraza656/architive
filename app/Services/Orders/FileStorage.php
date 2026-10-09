<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** Saves uploads for an order on the private disk (never publicly reachable; served through PortalFileController). */
class FileStorage
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array{message_id?:int,delivery_id?:int}  $link
     * @return array<int, OrderFile>
     */
    public function storeMany(array $files, Order $order, User $user, array $link = []): array
    {
        $saved = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $disk = config('portal.uploads.disk');
            $ext = strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs('orders/' . $order->id, Str::uuid() . ($ext ? '.' . $ext : ''), $disk);

            $saved[] = $order->files()->create($link + [
                'user_id' => $user->id,
                'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $saved;
    }
}
