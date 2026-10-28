<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveConnection;
use App\Models\OrderFile;
use App\Services\Drive\DriveException;
use App\Services\Drive\DriveSync;
use App\Services\Drive\GoogleDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** Admin > Integrations (super admin only): connect Google Drive for long-term file storage. */
class IntegrationsController extends Controller
{
    public function __construct(private GoogleDrive $drive, private DriveSync $sync)
    {
    }

    public function index()
    {
        $connections = DriveConnection::orderByDesc('is_active')->orderBy('id')->withCount('files')->get();
        foreach ($connections as $c) {                       // storage size, looked up at most every 10 minutes
            if ($c->status === 'connected' && (! $c->quota_checked_at || $c->quota_checked_at->lt(now()->subMinutes(10)))) {
                try {
                    $q = $this->drive->quota($c);
                    $c->forceFill(['quota_used' => $q['used'], 'quota_total' => $q['total'], 'quota_checked_at' => now()])->save();
                } catch (Throwable) {
                    // the page still works without the numbers
                }
            }
        }

        return view('portal.admin.integrations', [
            'configured' => $this->drive->configured(),
            'connections' => $connections,
            'stats' => $this->sync->stats(),
            'keepDays' => (int) config('portal.drive.keep_local_days'),
            'redirect' => config('services.google.redirect'),
        ]);
    }

    /** Step 1: send the admin to Google's "choose an account" screen. */
    public function connect(Request $request): RedirectResponse
    {
        if (! $this->drive->configured()) {
            return back()->with('error', 'Google keys are missing. Add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_REDIRECT_URI to the server settings first.');
        }
        $state = Str::random(40);
        $request->session()->put('google_drive_state', $state);

        return redirect()->away($this->drive->authUrl($state));
    }

    /** Step 2: Google sends the admin back here with a one-time code. */
    public function callback(Request $request): RedirectResponse
    {
        $back = redirect()->route('admin.integrations.index');
        $expected = (string) $request->session()->pull('google_drive_state');
        if ($request->filled('error')) {
            return $back->with('error', $request->query('error') === 'access_denied' ? 'Google Drive was not connected: you chose not to allow access.' : 'Google stopped the connection (' . e($request->query('error')) . ').');
        }
        if ($expected === '' || ! hash_equals($expected, (string) $request->query('state')) || ! $request->filled('code')) {
            return $back->with('error', 'The connection link expired or was not valid. Please click Connect again.');
        }

        try {
            $tokens = $this->drive->exchangeCode((string) $request->query('code'));
            if (! str_contains((string) ($tokens['scope'] ?? ''), GoogleDrive::SCOPE)) {
                return $back->with('error', 'The Drive permission was not ticked on Google\'s screen. Connect again and keep every box ticked.');
            }
            if (empty($tokens['refresh_token'])) {
                return $back->with('error', 'Google did not give a long-lasting key. Remove “Architive” at myaccount.google.com/permissions and connect again.');
            }
            $who = $this->drive->userInfo($tokens['access_token']);

            $conn = DriveConnection::firstOrNew(['account_email' => $who['email']]);
            $conn->fill([
                'account_name' => $who['name'], 'refresh_token' => $tokens['refresh_token'], 'status' => 'connected', 'last_error' => null, 'connected_by' => $request->user()->id,
            ])->save();
            Cache::put('drive:at:' . $conn->id, $tokens['access_token'], max(60, (int) ($tokens['expires_in'] ?? 3600) - 120));

            $this->drive->rootFolder($conn);                    // creates the "Architive" folder (or reuses it)
            DriveConnection::query()->update(['is_active' => false]);
            $conn->forceFill(['is_active' => true])->save();
            $q = $this->drive->quota($conn);
            $conn->forceFill(['quota_used' => $q['used'], 'quota_total' => $q['total'], 'quota_checked_at' => now()])->save();
        } catch (DriveException $e) {
            return $back->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Google Drive connect failed: ' . $e->getMessage());

            return $back->with('error', 'Something went wrong while connecting. Nothing was changed. Please try again.');
        }

        return $back->with('success', 'Connected to ' . $who['email'] . '. New files will be copied to its Google Drive in the background.');
    }

    public function syncNow(): RedirectResponse
    {
        if (! DriveConnection::active()) {
            return back()->with('error', 'Connect a Google account first.');
        }
        try {
            $n = $this->sync->syncPending(20);
        } catch (Throwable $e) {
            Log::error('Manual Drive sync failed: ' . $e->getMessage());

            return back()->with('error', 'The copy stopped with a problem. It will try again automatically.');
        }

        return back()->with('success', $n ? "Copied {$n} file" . ($n === 1 ? '' : 's') . ' to Google Drive. The rest continues in the background.' : 'Nothing waiting to be copied right now.');
    }

    public function retryFailed(): RedirectResponse
    {
        $n = OrderFile::whereNull('drive_file_id')->where('drive_attempts', '>=', (int) config('portal.drive.max_attempts'))->update(['drive_attempts' => 0, 'drive_next_attempt_at' => null, 'drive_error' => null]);

        return back()->with('success', $n ? "{$n} file" . ($n === 1 ? '' : 's') . ' will be tried again.' : 'No failed files.');
    }

    public function activate(DriveConnection $connection): RedirectResponse
    {
        if ($connection->status !== 'connected') {
            return back()->with('error', 'Connect this account again first.');
        }
        DriveConnection::query()->update(['is_active' => false]);
        $connection->forceFill(['is_active' => true])->save();

        return back()->with('success', 'New files now go to ' . $connection->account_email . '.');
    }

    public function move(DriveConnection $connection): RedirectResponse
    {
        $active = DriveConnection::active();
        if (! $active || $active->id === $connection->id) {
            return back()->with('error', 'Choose a different account as the active one first.');
        }
        if ($connection->status !== 'connected') {
            return back()->with('error', 'Connect ' . $connection->account_email . ' again first, so its files can be read.');
        }
        $connection->forceFill(['moving_files' => true, 'last_error' => null])->save();

        return back()->with('success', 'Moving started. Files are copied to ' . $active->account_email . ' in the background; the old Drive is left untouched.');
    }

    public function remove(DriveConnection $connection): RedirectResponse
    {
        $left = $connection->files()->count();
        if ($left > 0) {
            return back()->with('error', "{$left} file" . ($left === 1 ? ' still lives' : 's still live') . ' in this Drive. Move them to the active account first.');
        }
        $this->drive->revoke((string) $connection->refresh_token);
        $connection->delete();

        return back()->with('success', 'The connection was removed.');
    }
}
