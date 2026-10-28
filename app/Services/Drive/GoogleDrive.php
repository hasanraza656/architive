<?php

namespace App\Services\Drive;

use App\Models\DriveConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Small Google OAuth + Drive v3 client (plain HTTPS, no extra packages). Scope: drive.file, so the portal can only ever see
 * the files and folders it created itself, never the rest of the person's Drive.
 */
class GoogleDrive
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    private const API = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    /** Must be a multiple of 256 KiB (Google's rule for resumable uploads). */
    private const CHUNK = 8 * 1024 * 1024;

    /* ------------------------------------------------------------ OAuth */

    public function configured(): bool
    {
        return (bool) (config('services.google.client_id') && config('services.google.client_secret') && config('services.google.redirect'));
    }

    public function authUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email ' . self::SCOPE,
            'access_type' => 'offline',          // gives a refresh token so the connection keeps working
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);
    }

    /** @return array{access_token:string, refresh_token?:string, expires_in:int, scope?:string} */
    public function exchangeCode(string $code): array
    {
        $res = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'), 'grant_type' => 'authorization_code',
        ]);
        if (! $res->successful() || ! $res->json('access_token')) {
            throw new DriveException('Google did not accept the sign-in (' . ($res->json('error_description') ?: $res->json('error') ?: $res->status()) . ').');
        }

        return $res->json();
    }

    /** @return array{email:string, name:?string} */
    public function userInfo(string $accessToken): array
    {
        $res = Http::withToken($accessToken)->timeout(30)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (! $res->successful() || ! $res->json('email')) {
            throw new DriveException('Could not read the Google account details.');
        }

        return ['email' => (string) $res->json('email'), 'name' => $res->json('name')];
    }

    public function revoke(string $token): void
    {
        try {
            Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        } catch (\Throwable) {
            // best effort: the token also stops working when the person removes the app in their Google account
        }
    }

    /** A valid access token for this connection (cached for its lifetime). A revoked / expired grant marks the connection "needs reconnect". */
    public function accessToken(DriveConnection $c, bool $forceNew = false): string
    {
        $key = 'drive:at:' . $c->id;
        if ($forceNew) {
            Cache::forget($key);
        }
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $res = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'), 'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $c->refresh_token, 'grant_type' => 'refresh_token',
        ]);
        if (! $res->successful() || ! $res->json('access_token')) {
            if (in_array($res->json('error'), ['invalid_grant', 'unauthorized_client', 'invalid_client'], true)) {
                $c->forceFill(['status' => 'needs_reconnect', 'last_error' => 'Google no longer accepts this connection. Connect the account again.'])->save();
                throw new DriveAuthException('The Google connection for ' . $c->account_email . ' has to be reconnected.');
            }
            throw new DriveException('Could not get access to Google Drive right now (' . ($res->json('error') ?: $res->status()) . ').');
        }
        Cache::put($key, $res->json('access_token'), max(60, (int) $res->json('expires_in', 3600) - 120));

        return $res->json('access_token');
    }

    /* ------------------------------------------------------------ folders */

    /** Creates a folder; returns its id. */
    public function createFolder(DriveConnection $c, string $name, ?string $parentId = null): string
    {
        $res = $this->send($c, fn (PendingRequest $r) => $r->asJson()->post(self::API . '/files?fields=id', array_filter([
            'name' => $name, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => $parentId ? [$parentId] : null,
        ])));
        $this->ok($res, 'create a folder');

        return (string) $res->json('id');
    }

    public function exists(DriveConnection $c, string $fileId): bool
    {
        $res = $this->send($c, fn (PendingRequest $r) => $r->get(self::API . '/files/' . rawurlencode($fileId), ['fields' => 'id,trashed']));
        if ($res->status() === 404) {
            return false;
        }
        $this->ok($res, 'check a file');

        return ! (bool) $res->json('trashed');
    }

    /** The Drive folder for one order (created on first use, remembered). */
    public function orderFolder(DriveConnection $c, int $orderId, string $label): string
    {
        $map = $c->folder_map ?? [];
        if (! empty($map[$orderId])) {
            return $map[$orderId];
        }
        $root = $this->rootFolder($c);
        $id = $this->createFolder($c, $label, $root);
        $c->folder_map = $map + [$orderId => $id];
        $c->save();

        return $id;
    }

    public function forgetOrderFolder(DriveConnection $c, int $orderId): void
    {
        $map = $c->folder_map ?? [];
        unset($map[$orderId]);
        $c->folder_map = $map;
        $c->save();
    }

    public function rootFolder(DriveConnection $c): string
    {
        if ($c->root_folder_id && $this->exists($c, $c->root_folder_id)) {
            return $c->root_folder_id;
        }
        $c->root_folder_id = $this->createFolder($c, 'Architive');
        $c->folder_map = [];                 // old order folders were inside the lost root
        $c->save();

        return $c->root_folder_id;
    }

    /* ------------------------------------------------------------ files */

    /**
     * Uploads a local file (resumable, in chunks) and returns the Drive file id.
     *
     * @param  array<string,string>  $props  stored on the Drive file so it can be traced back (order, file id)
     */
    public function upload(DriveConnection $c, string $path, string $name, string $mime, string $parentId, array $props = []): string
    {
        $size = (int) filesize($path);
        $meta = ['name' => $name, 'parents' => [$parentId], 'appProperties' => (object) $props];

        if ($size === 0) {                  // nothing to send: just create the empty file
            $res = $this->send($c, fn (PendingRequest $r) => $r->asJson()->post(self::API . '/files?fields=id', $meta));
            $this->ok($res, 'create a file');

            return (string) $res->json('id');
        }

        $init = $this->send($c, fn (PendingRequest $r) => $r->asJson()->withHeaders(['X-Upload-Content-Type' => $mime ?: 'application/octet-stream', 'X-Upload-Content-Length' => (string) $size])
            ->post(self::UPLOAD . '?uploadType=resumable&fields=id', $meta));
        if ($init->status() === 404) {
            throw new DriveFolderMissing('The Drive folder is gone.');
        }
        $this->ok($init, 'start an upload');
        $url = $init->header('Location');
        if (! $url) {
            throw new DriveException('Google did not return an upload address.');
        }

        $fh = fopen($path, 'rb');
        if (! $fh) {
            throw new DriveException('The local file could not be read.');
        }
        try {
            for ($start = 0; $start < $size; $start += self::CHUNK) {
                $chunk = (string) stream_get_contents($fh, self::CHUNK, $start);
                $end = $start + strlen($chunk) - 1;
                $res = Http::withToken($this->accessToken($c))->timeout(300)->withOptions(['allow_redirects' => false])
                    ->withHeaders(['Content-Range' => "bytes {$start}-{$end}/{$size}"])->withBody($chunk, 'application/octet-stream')->put($url);
                if ($end + 1 >= $size) {
                    if (! in_array($res->status(), [200, 201], true) || ! $res->json('id')) {
                        throw new DriveException('Google did not confirm the upload (' . $res->status() . ').');
                    }

                    return (string) $res->json('id');
                }
                if ($res->status() !== 308) {
                    throw new DriveException('The upload was interrupted (' . $res->status() . ').');
                }
            }
        } finally {
            fclose($fh);
        }

        throw new DriveException('The upload did not finish.');
    }

    /** Downloads a Drive file to $sinkPath (written to a temporary name first, so a half-finished download never looks complete). */
    public function download(DriveConnection $c, string $fileId, string $sinkPath): void
    {
        $tmp = $sinkPath . '.part';
        @mkdir(dirname($sinkPath), 0775, true);
        $res = $this->send($c, fn (PendingRequest $r) => $r->timeout(300)->withOptions(['sink' => $tmp])->get(self::API . '/files/' . rawurlencode($fileId), ['alt' => 'media']));
        if (! $res->successful()) {
            @unlink($tmp);
            throw new DriveException('The file could not be fetched from Google Drive (' . $res->status() . ').');
        }
        if (! @rename($tmp, $sinkPath)) {
            @unlink($tmp);
            throw new DriveException('The downloaded file could not be saved.');
        }
    }

    /** Permanently deletes a Drive file. A file that is already gone counts as deleted. */
    public function delete(DriveConnection $c, string $fileId): void
    {
        $res = $this->send($c, fn (PendingRequest $r) => $r->delete(self::API . '/files/' . rawurlencode($fileId)));
        if ($res->status() === 404 || $res->successful()) {
            return;
        }
        $this->ok($res, 'delete a file');
    }

    /** @return array{used:?int, total:?int} total is null when the account has unlimited storage */
    public function quota(DriveConnection $c): array
    {
        $res = $this->send($c, fn (PendingRequest $r) => $r->get(self::API . '/about', ['fields' => 'storageQuota']));
        $this->ok($res, 'read the storage size');

        return [
            'used' => $res->json('storageQuota.usage') !== null ? (int) $res->json('storageQuota.usage') : null,
            'total' => $res->json('storageQuota.limit') !== null ? (int) $res->json('storageQuota.limit') : null,
        ];
    }

    /* ------------------------------------------------------------ plumbing */

    /** One API call with the access token; refreshes the token once on 401 and retries short hiccups (429 / 5xx). */
    private function send(DriveConnection $c, callable $call): Response
    {
        $res = null;
        for ($try = 0; $try < 3; $try++) {
            $res = $call(Http::withToken($this->accessToken($c, $try > 0 && $res && $res->status() === 401))->timeout(60)->acceptJson());
            if ($res->status() === 429 || $res->status() >= 500) {
                usleep(400000 * ($try + 1));

                continue;
            }
            if ($res->status() !== 401) {
                break;
            }
        }

        return $res;
    }

    private function ok(Response $res, string $doing): void
    {
        if ($res->successful()) {
            return;
        }
        $why = $res->json('error.message') ?: $res->status();
        $reason = $res->json('error.errors.0.reason');
        if ($reason === 'storageQuotaExceeded') {
            throw new DriveException('The Google Drive is full. Free up space or use a bigger plan.');
        }

        throw new DriveException('Google Drive could not ' . $doing . ' (' . $why . ').');
    }
}
