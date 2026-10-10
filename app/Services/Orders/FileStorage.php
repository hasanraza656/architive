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
        $this->guardPrivateFolder();
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
        @file_put_contents($htaccess, "# Private order files: only the portal may read them.
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
");
    }
}
