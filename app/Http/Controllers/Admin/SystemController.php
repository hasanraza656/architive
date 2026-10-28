<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McpToken;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Admin > System (super admin only): apply database updates that shipped with a new version of the site and clear caches,
 * so a developer does not need phpMyAdmin or SSH after every release. Only forward ("migrate") is offered, never a rollback.
 */
class SystemController extends Controller
{
    private Migrator $migrator;

    public function __construct()
    {
        $this->migrator = app('migrator');
    }

    public function show(Request $request)
    {
        return view('portal.admin.system', [
            'pending' => $this->pending(),
            'ran' => Schema::hasTable('migrations') ? \DB::table('migrations')->count() : 0,
            'info' => [
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Environment' => app()->environment(),
                'Debug mode' => config('app.debug') ? 'On (turn off on the live site)' : 'Off',
                'Database' => config('database.default'),
            ],
            'output' => session('system_output'),
            'mcpReady' => Schema::hasTable('mcp_tokens'),      // false until the database update has been applied
            'tokens' => Schema::hasTable('mcp_tokens') ? McpToken::where('user_id', $request->user()->id)->whereNull('revoked_at')->latest('id')->get() : collect(),
            'mcpKey' => session('mcp_key'),
        ]);
    }

    public function migrate(Request $request): RedirectResponse
    {
        $pending = $this->pending();
        if (! $pending) {
            return back()->with('info', 'The database is already up to date.');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            $this->clear();
        } catch (Throwable $e) {
            Log::error('Admin migrate failed: ' . $e->getMessage());

            return back()->with('error', 'The update stopped with an error: ' . str($e->getMessage())->limit(300) . ' Nothing else was changed. Please send this message to your developer.');
        }

        Log::info('Database updated from the admin portal by user ' . $request->user()->id . ': ' . implode(', ', $pending));

        return back()->with('success', count($pending) . ' database update' . (count($pending) === 1 ? '' : 's') . ' applied.')->with('system_output', $output);
    }

    public function clearCache(Request $request): RedirectResponse
    {
        try {
            $this->clear();
        } catch (Throwable $e) {
            return back()->with('error', 'Could not clear everything: ' . str($e->getMessage())->limit(300));
        }

        return back()->with('success', 'Caches cleared. The site now reads the newest settings, routes and pages.');
    }

    /** Create an access key for the AI assistant (MCP). The key is shown once; only its hash is stored. */
    public function createKey(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('mcp_tokens')) {
            return back()->with('error', 'Apply the database update first (the card above), then create a key.');
        }
        $data = $request->validate(['name' => ['required', 'string', 'max:80']], ['name.required' => 'Give the connection a name, for example “Claude on my laptop”.']);
        if (McpToken::where('user_id', $request->user()->id)->whereNull('revoked_at')->count() >= 10) {
            return back()->with('error', 'You already have 10 active connections. Remove one you no longer use first.');
        }
        [, $plain] = McpToken::issue($request->user(), trim($data['name']));

        return back()->with('success', 'Connection created. Copy the key now: it is shown only once.')->with('mcp_key', $plain);
    }

    public function revokeKey(Request $request, McpToken $token): RedirectResponse
    {
        abort_unless((int) $token->user_id === (int) $request->user()->id, 403);
        $token->forceFill(['revoked_at' => now()])->save();

        return back()->with('success', 'Connection removed. That key no longer works.');
    }

    /** Names of migration files that have not run yet. */
    private function pending(): array
    {
        $repo = $this->migrator->getRepository();
        if (! $repo->repositoryExists()) {
            return array_keys($this->migrator->getMigrationFiles($this->migrator->paths() + [database_path('migrations')]));
        }
        $files = $this->migrator->getMigrationFiles(array_merge($this->migrator->paths(), [database_path('migrations')]));

        return array_values(array_diff(array_keys($files), $repo->getRan()));
    }

    /** Config, route, view, event and application caches. Sessions and uploads are never touched. */
    private function clear(): void
    {
        foreach (['optimize:clear', 'view:clear'] as $cmd) {
            Artisan::call($cmd);
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}
