<?php

namespace Tests\Feature\Portal;

use App\Models\DriveConnection;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Services\Drive\DriveSync;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DriveStorageTest extends PortalTestCase
{
    /** Requests the fake Google saw, as "METHOD url". */
    private array $seen = [];

    private bool $driveHasFile = true;

    private bool $uploadFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'sec', 'services.google.redirect' => 'https://architive.test/admin/integrations/google/callback']);
        Storage::fake('uploads');
        Cache::flush();
    }

    /** A tiny pretend Google: tokens, user info, folders, resumable uploads, downloads, deletes, quota. */
    private function fakeGoogle(): void
    {
        Http::fake(function (Request $r) {
            $url = $r->url();
            $this->seen[] = $r->method() . ' ' . $url;

            return match (true) {
                str_contains($url, 'oauth2.googleapis.com/token') => Http::response(['access_token' => 'at-1', 'refresh_token' => 'rt-1', 'expires_in' => 3600, 'scope' => 'openid email https://www.googleapis.com/auth/drive.file']),
                str_contains($url, 'oauth2.googleapis.com/revoke') => Http::response('', 200),
                str_contains($url, 'oauth2/v3/userinfo') => Http::response(['email' => 'client@gmail.com', 'name' => 'Cleo Client']),
                str_contains($url, '/drive/v3/about') => Http::response(['storageQuota' => ['usage' => '1000', 'limit' => '16106127360']]),
                str_contains($url, '/upload/drive/v3/files') => $this->uploadFails ? Http::response(['error' => ['message' => 'boom']], 500) : Http::response('', 200, ['Location' => 'https://upload.googleapis.com/session/abc']),
                str_contains($url, 'upload.googleapis.com/session') => Http::response(['id' => 'drv-file-1'], 200),
                $r->method() === 'POST' && str_contains($url, '/drive/v3/files') => Http::response(['id' => 'folder-' . count($this->seen)]),
                $r->method() === 'DELETE' => Http::response('', 204),
                $r->method() === 'GET' && str_contains($url, 'alt=media') => Http::response('PDF-BYTES', 200),
                $r->method() === 'GET' && str_contains($url, '/drive/v3/files/') => $this->driveHasFile ? Http::response(['id' => 'x', 'trashed' => false]) : Http::response(['error' => ['message' => 'not found']], 404),
                default => Http::response('', 404),
            };
        });
    }

    private function connection(array $attrs = []): DriveConnection
    {
        return DriveConnection::create($attrs + ['account_email' => 'client@gmail.com', 'account_name' => 'Cleo', 'refresh_token' => 'rt-1', 'root_folder_id' => 'root-1', 'is_active' => true, 'status' => 'connected']);
    }

    private ?Order $order = null;

    private int $n = 0;

    private function adminUser(): User
    {
        return User::where('role', 'admin')->first() ?: $this->admin();
    }

    private function file(array $attrs = [], string $content = 'PDF-BYTES'): OrderFile
    {
        $admin = $this->adminUser();
        $this->order ??= $this->order($this->customer(), $admin);
        $path = 'orders/' . $this->order->id . '/f' . (++$this->n) . '.pdf';
        Storage::disk('uploads')->put($path, $content);

        return OrderFile::create($attrs + [
            'order_id' => $this->order->id, 'user_id' => $admin->id, 'original_name' => 'plan.pdf', 'path' => $path, 'mime' => 'application/pdf',
            'kind' => 'pdf', 'size' => strlen($content), 'created_at' => now()->subMinutes(5),
        ]);
    }

    public function test_connect_flow_stores_an_encrypted_connection(): void
    {
        $this->fakeGoogle();
        $this->actingAs($this->admin());

        $this->get('/admin/integrations')->assertOk()->assertSee('Connect Google Drive');
        $res = $this->get('/admin/integrations/google/connect');
        $res->assertRedirectContains('accounts.google.com');
        $this->assertStringContainsString('drive.file', $res->headers->get('Location'));
        $state = session('google_drive_state');

        $this->get('/admin/integrations/google/callback?code=abc&state=wrong')->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, DriveConnection::count());

        $this->withSession(['google_drive_state' => $state])->get('/admin/integrations/google/callback?code=abc&state=' . $state)->assertRedirect()->assertSessionHas('success');
        $c = DriveConnection::first();
        $this->assertSame('client@gmail.com', $c->account_email);
        $this->assertTrue($c->is_active);
        $this->assertNotEmpty($c->root_folder_id);
        $this->assertNotSame('rt-1', DB::table('drive_connections')->value('refresh_token'));      // stored encrypted
        $this->assertSame('rt-1', $c->refresh_token);

        $this->get('/admin/integrations')->assertOk()->assertSee('client@gmail.com')->assertSee('Receiving new files');
    }

    public function test_only_the_super_admin_sees_the_page_and_a_denied_login_is_explained(): void
    {
        $this->actingAs($this->customer())->get('/admin/integrations')->assertRedirect();
        $this->actingAs($this->admin())->withSession(['google_drive_state' => 's'])->get('/admin/integrations/google/callback?error=access_denied&state=s')
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_nothing_happens_without_a_connection(): void
    {
        $f = $this->file();
        $this->assertSame(0, app(DriveSync::class)->syncPending(5));
        $this->assertNull($f->fresh()->drive_file_id);
        $this->assertTrue(Storage::disk('uploads')->exists($f->path));
    }

    public function test_new_files_are_copied_to_drive_and_failures_back_off(): void
    {
        $this->fakeGoogle();
        $this->connection();
        $f = $this->file();
        $young = $this->file(['created_at' => now()]);                      // saved a moment ago: left alone for now

        $this->assertSame(1, app(DriveSync::class)->syncPending(5));
        $f->refresh();
        $this->assertSame('drv-file-1', $f->drive_file_id);
        $this->assertNotNull($f->drive_synced_at);
        $this->assertNull($young->fresh()->drive_file_id);
        $this->assertTrue(Storage::disk('uploads')->exists($f->path));      // the local copy stays

        $bad = $this->file();
        $this->uploadFails = true;
        app(DriveSync::class)->syncPending(5);
        $bad->refresh();
        $this->assertNull($bad->drive_file_id);
        $this->assertSame(1, $bad->drive_attempts);
        $this->assertNotEmpty($bad->drive_error);
        $this->assertTrue($bad->drive_next_attempt_at->isFuture());
        $this->assertSame(0, app(DriveSync::class)->syncPending(5));        // waits before trying again
    }

    public function test_old_local_copies_are_removed_only_when_drive_really_has_them(): void
    {
        $this->fakeGoogle();
        $c = $this->connection();
        $old = ['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-1', 'drive_synced_at' => now()->subDays(40)];
        $safe = $this->file($old);
        $lost = $this->file($old);
        $recent = $this->file(['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-2', 'drive_synced_at' => now()->subDays(2)]);

        $this->assertSame(2, app(DriveSync::class)->pruneLocal());
        $this->assertFalse(Storage::disk('uploads')->exists($safe->path));
        $this->assertNotNull($safe->fresh()->local_pruned_at);
        $this->assertTrue(Storage::disk('uploads')->exists($recent->path));   // too recent

        // Drive no longer has it: the local file must stay and the file is queued for a fresh upload
        $this->driveHasFile = false;
        $gone = $this->file($old);
        Storage::disk('uploads')->put($gone->path, 'keep me');
        app(DriveSync::class)->pruneLocal();
        $this->assertTrue(Storage::disk('uploads')->exists($gone->path));
        $this->assertNull($gone->fresh()->drive_file_id);
    }

    public function test_opening_a_removed_file_fetches_it_back_from_drive(): void
    {
        $this->fakeGoogle();
        $c = $this->connection();
        $f = $this->file(['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-1', 'drive_synced_at' => now()->subDays(40), 'local_pruned_at' => now()->subDay()]);
        Storage::disk('uploads')->delete($f->path);

        $admin = $this->adminUser();
        $res = $this->actingAs($admin)->get('/portal/files/' . $f->id);
        $res->assertOk();
        $this->assertTrue(Storage::disk('uploads')->exists($f->path));
        $this->assertNull($f->fresh()->local_pruned_at);
        $this->assertSame('PDF-BYTES', Storage::disk('uploads')->get($f->path));

        // a file that exists nowhere is a plain 404, not a crash
        $nowhere = $this->file();
        Storage::disk('uploads')->delete($nowhere->path);
        $this->actingAs($admin)->get('/portal/files/' . $nowhere->id)->assertNotFound();
    }

    public function test_deleting_an_order_removes_its_drive_copies_later(): void
    {
        $this->fakeGoogle();
        $c = $this->connection();
        $f = $this->file(['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-del', 'drive_synced_at' => now()]);
        Order::find($f->order_id)->delete();

        $this->assertSame(1, DB::table('drive_deletions')->count());
        app(DriveSync::class)->processDeletions(5);
        $this->assertSame(0, DB::table('drive_deletions')->count());
        $this->assertTrue(collect($this->seen)->contains(fn ($s) => str_starts_with($s, 'DELETE') && str_contains($s, 'drv-del')));
    }

    public function test_files_can_be_moved_to_another_account(): void
    {
        $this->fakeGoogle();
        $old = $this->connection(['account_email' => 'old@gmail.com', 'is_active' => false]);
        $new = $this->connection(['account_email' => 'new@gmail.com', 'is_active' => true]);
        $f = $this->file(['drive_connection_id' => $old->id, 'drive_file_id' => 'drv-old', 'drive_synced_at' => now()->subDays(40), 'local_pruned_at' => now()->subDay()]);
        Storage::disk('uploads')->delete($f->path);

        $this->actingAs($this->adminUser())->post('/admin/integrations/drive/' . $old->id . '/move')->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($old->fresh()->moving_files);

        app(DriveSync::class)->syncPending(10);                      // fetches it back and hands it to the copy job
        app(DriveSync::class)->syncPending(10);                      // which uploads it to the active account
        $f->refresh();
        $this->assertSame($new->id, $f->drive_connection_id);
        $this->assertSame('drv-file-1', $f->drive_file_id);
        $this->assertFalse($old->fresh()->moving_files);
        $this->assertTrue(Storage::disk('uploads')->exists($f->path));
    }

    public function test_a_drive_that_still_holds_files_cannot_be_removed(): void
    {
        $this->fakeGoogle();
        $c = $this->connection();
        $this->file(['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-1', 'drive_synced_at' => now()]);
        $this->actingAs($this->adminUser())->delete('/admin/integrations/drive/' . $c->id)->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($c->fresh());
    }

    public function test_opening_a_local_file_marks_it_as_recently_used(): void
    {
        $this->fakeGoogle();
        $c = $this->connection();
        $f = $this->file(['drive_connection_id' => $c->id, 'drive_file_id' => 'drv-1', 'drive_synced_at' => now()->subDays(40)]);

        $this->actingAs($this->adminUser())->get('/portal/files/' . $f->id)->assertOk();
        $this->assertNotNull($f->fresh()->last_opened_at);
        $this->assertSame(0, app(DriveSync::class)->pruneLocal());       // just opened: stays on the server
    }
}
