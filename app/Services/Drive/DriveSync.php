<?php

namespace App\Services\Drive;

use App\Models\DriveConnection;
use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps order files in two places without ever risking a file:
 *   1. a new file is saved on the server (as before) and used from there;
 *   2. a background run copies it to Google Drive and remembers the Drive file id;
 *   3. after N days (and only after checking the Drive copy is really there) the local original is removed;
 *   4. when someone opens a file that is no longer local, it is fetched back from Drive and kept locally again.
 * Nothing here ever runs when no Google account is connected, so the portal behaves exactly as before.
 */
class DriveSync
{
    public function __construct(private GoogleDrive $drive)
    {
    }

    private function disk()
    {
        return Storage::disk(config('portal.uploads.disk'));
    }

    /* ------------------------------------------------------------ copy to Drive */

    /** Copies waiting files to the active Drive account for up to $seconds. Returns how many were copied. */
    public function syncPending(?int $seconds = null): int
    {
        $seconds ??= (int) config('portal.drive.sync_seconds');
        $conn = DriveConnection::active();
        if (! $conn) {
            return 0;
        }
        $lock = Cache::lock('drive-sync', $seconds + 60);
        if (! $lock->get()) {
            return 0;                       // another run is busy
        }

        $done = 0;
        $started = time();
        try {
            while (time() - $started < $seconds) {
                $file = $this->nextWaiting();
                if (! $file) {
                    break;
                }
                try {
                    $this->copy($conn, $file);
                    $done++;
                } catch (DriveAuthException) {
                    break;                  // the connection is marked "needs reconnect"; nothing else can work
                } catch (DriveException $e) {
                    $this->failed($file, $e->getMessage());
                } catch (\Throwable $e) {
                    Log::error('Drive sync crashed for file ' . $file->id . ': ' . $e->getMessage());
                    $this->failed($file, 'Unexpected problem: ' . $e->getMessage());
                }
            }
            $this->processDeletions($seconds - (time() - $started));
            $this->moveFiles(max(0, $seconds - (time() - $started)));
        } finally {
            $lock->release();
        }

        return $done;
    }

    private function nextWaiting(): ?OrderFile
    {
        return OrderFile::whereNull('drive_file_id')->whereNull('local_pruned_at')
            ->where('created_at', '<=', now()->subSeconds(20))
            ->where('drive_attempts', '<', (int) config('portal.drive.max_attempts'))
            ->where(fn ($q) => $q->whereNull('drive_next_attempt_at')->orWhere('drive_next_attempt_at', '<=', now()))
            ->orderBy('id')->first();
    }

    private function copy(DriveConnection $conn, OrderFile $file): void
    {
        $path = $this->disk()->path($file->path);
        if (! is_file($path)) {
            $this->failed($file, 'The local file is missing, so it cannot be copied.', true);

            return;
        }
        $order = Order::find($file->order_id);
        if (! $order) {
            return;
        }

        $attempt = function () use ($conn, $file, $order, $path) {
            $folder = $this->drive->orderFolder($conn, $order->id, $order->number ?: ('order-' . $order->id));

            return $this->drive->upload($conn, $path, $this->driveName($file), $file->mime ?: 'application/octet-stream', $folder, ['order' => (string) $order->number, 'portal_file' => (string) $file->id]);
        };
        try {
            $driveId = $attempt();
        } catch (DriveFolderMissing) {      // someone deleted the folder in Drive: make a new one and try once more
            $this->drive->forgetOrderFolder($conn, $order->id);
            $driveId = $attempt();
        }

        $file->forceFill([
            'drive_connection_id' => $conn->id, 'drive_file_id' => $driveId, 'drive_synced_at' => now(),
            'drive_attempts' => 0, 'drive_next_attempt_at' => null, 'drive_error' => null,
        ])->save();
    }

    private function failed(OrderFile $file, string $why, bool $final = false): void
    {
        $attempts = $final ? (int) config('portal.drive.max_attempts') : $file->drive_attempts + 1;
        $file->forceFill([
            'drive_attempts' => $attempts, 'drive_error' => mb_substr($why, 0, 500),
            'drive_next_attempt_at' => now()->addMinutes(min(90, 2 ** min($attempts, 6))),
        ])->save();
    }

    private function driveName(OrderFile $file): string
    {
        return $file->id . '-' . preg_replace('/[\\\\\/:*?"<>|\x00-\x1f]+/', '_', (string) $file->original_name);
    }

    /* ------------------------------------------------------------ remove local copies */

    /** Removes the local original of files that have been safe in Drive for a while. The Drive copy is checked first, every time. */
    public function pruneLocal(int $limit = 100): int
    {
        $days = (int) config('portal.drive.keep_local_days');
        if ($days < 1) {
            return 0;
        }
        $cut = now()->subDays($days);
        $removed = 0;

        OrderFile::whereNotNull('drive_file_id')->whereNull('local_pruned_at')->where('drive_synced_at', '<=', $cut)
            ->where(fn ($q) => $q->whereNull('last_opened_at')->orWhere('last_opened_at', '<=', $cut))
            ->orderBy('id')->limit($limit)->get()->each(function (OrderFile $file) use (&$removed) {
                $conn = DriveConnection::find($file->drive_connection_id);
                if (! $conn || $conn->status !== 'connected') {
                    return;                 // cannot confirm the Drive copy: keep the local one
                }
                try {
                    $there = $this->drive->exists($conn, $file->drive_file_id);
                } catch (DriveException) {
                    return;
                }
                if (! $there) {             // the Drive copy vanished: upload it again instead of deleting anything
                    $file->forceFill(['drive_connection_id' => null, 'drive_file_id' => null, 'drive_synced_at' => null, 'drive_attempts' => 0, 'drive_next_attempt_at' => null])->save();

                    return;
                }
                $this->disk()->delete($file->path);
                $file->forceFill(['local_pruned_at' => now()])->save();
                $removed++;
            });

        return $removed;
    }

    /* ------------------------------------------------------------ bring a file back */

    /** Makes sure the local copy exists, fetching it from Drive when it was removed. Throws DriveException when that is impossible. */
    public function ensureLocal(OrderFile $file): void
    {
        if ($this->disk()->exists($file->path)) {
            return;
        }
        if (! $file->drive_file_id) {
            throw new DriveException('This file is no longer available.');
        }
        $conn = DriveConnection::find($file->drive_connection_id);
        if (! $conn) {
            throw new DriveException('The Google Drive that holds this file is not connected any more.');
        }

        $lock = Cache::lock('drive-restore-' . $file->id, 120);
        $lock->block(60);                   // a second visitor waits for the first download instead of starting another
        try {
            if (! $this->disk()->exists($file->path)) {
                $this->drive->download($conn, $file->drive_file_id, $this->disk()->path($file->path));
            }
            $file->forceFill(['local_pruned_at' => null, 'last_opened_at' => now()])->save();
        } finally {
            optional($lock)->release();
        }
    }

    /* ------------------------------------------------------------ deleting */

    /** Remember to delete an order's Drive copies (the order, and with it its file rows, is about to be removed). */
    public function queueOrderDeletion(Order $order): void
    {
        OrderFile::where('order_id', $order->id)->whereNotNull('drive_file_id')->get(['drive_connection_id', 'drive_file_id'])->each(function ($f) {
            DB::table('drive_deletions')->insert(['drive_connection_id' => $f->drive_connection_id, 'drive_file_id' => $f->drive_file_id, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function processDeletions(int $seconds = 20): void
    {
        $started = time();
        foreach (DB::table('drive_deletions')->orderBy('id')->limit(50)->get() as $row) {
            if (time() - $started >= max(5, $seconds)) {
                break;
            }
            $conn = DriveConnection::find($row->drive_connection_id);
            try {
                if ($conn && $conn->status === 'connected') {
                    $this->drive->delete($conn, $row->drive_file_id);
                }
                DB::table('drive_deletions')->where('id', $row->id)->delete();
            } catch (DriveException $e) {
                $row->attempts >= 9
                    ? DB::table('drive_deletions')->where('id', $row->id)->delete()
                    : DB::table('drive_deletions')->where('id', $row->id)->update(['attempts' => $row->attempts + 1, 'updated_at' => now()]);
            }
        }
    }

    /* ------------------------------------------------------------ move to another account */

    /**
     * For connections marked "moving": fetch each file (if needed) and hand it back to the normal copy job, which uploads it
     * to the ACTIVE account. The old Drive is never touched.
     */
    public function moveFiles(int $seconds = 20): void
    {
        $active = DriveConnection::active();
        $started = time();
        foreach (DriveConnection::where('moving_files', true)->get() as $old) {
            if (! $active || $active->id === $old->id) {
                $old->forceFill(['moving_files' => false])->save();

                continue;
            }
            while (time() - $started < max(5, $seconds)) {
                $file = OrderFile::where('drive_connection_id', $old->id)->whereNotNull('drive_file_id')->orderBy('id')->first();
                if (! $file) {
                    $old->forceFill(['moving_files' => false])->save();
                    break;
                }
                try {
                    $this->ensureLocal($file);
                    $file->forceFill(['drive_connection_id' => null, 'drive_file_id' => null, 'drive_synced_at' => null, 'drive_attempts' => 0, 'drive_next_attempt_at' => null, 'drive_error' => null, 'local_pruned_at' => null])->save();
                } catch (DriveAuthException) {
                    return;
                } catch (\Throwable $e) {
                    // cannot be moved (gone from both places): leave it where it is and stop trying this one
                    $file->forceFill(['drive_error' => 'Could not be moved: ' . mb_substr($e->getMessage(), 0, 300), 'drive_attempts' => 99])->save();
                    $file->forceFill(['drive_connection_id' => $old->id])->save();
                    $old->forceFill(['moving_files' => false, 'last_error' => 'Some files could not be moved: ' . $file->original_name])->save();
                    break;
                }
            }
        }
    }

    /* ------------------------------------------------------------ numbers for the admin page */

    public function stats(): array
    {
        $local = OrderFile::whereNull('local_pruned_at');

        return [
            'total' => OrderFile::count(),
            'in_drive' => OrderFile::whereNotNull('drive_file_id')->count(),
            'waiting' => OrderFile::whereNull('drive_file_id')->whereNull('local_pruned_at')->where('drive_attempts', '<', (int) config('portal.drive.max_attempts'))->count(),
            'failed' => OrderFile::whereNull('drive_file_id')->where('drive_attempts', '>=', (int) config('portal.drive.max_attempts'))->count(),
            'local_bytes' => (int) $local->sum('size'),
            'drive_only' => OrderFile::whereNotNull('local_pruned_at')->count(),
        ];
    }
}
