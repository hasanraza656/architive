<?php

namespace App\Http\Controllers;

use App\Models\McpToken;
use App\Services\Mcp\McpServer;
use App\Services\Mcp\Toolbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * POST /mcp (key in the Authorization header) or POST /mcp/{key} (key in the address, for connectors that cannot send headers).
 * Stateless: every call is checked on its own, nothing is stored in a session.
 */
class McpController extends Controller
{
    public function handle(Request $request, ?string $key = null)
    {
        $plain = $key ?: $this->bearer($request);
        $token = $plain ? McpToken::findByKey($plain) : null;
        $user = $token?->user;

        if (! $token || ! $user || ! $user->is_active || ! $user->isTeam()) {
            return response()->json(['error' => 'unauthorized', 'message' => 'Missing or invalid access key.'], 401, ['WWW-Authenticate' => 'Bearer realm="architive"']);
        }
        if (! RateLimiter::attempt('mcp:' . $token->id, 180, fn () => true, 60)) {
            return response()->json(['error' => 'rate_limited', 'message' => 'Too many requests. Wait a minute.'], 429, ['Retry-After' => 30]);
        }

        // Only touch the row once a minute so busy sessions do not write on every call.
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        $body = json_decode($request->getContent(), true);
        if (! is_array($body) || $body === []) {
            return response()->json(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']], 400);
        }

        Auth::guard('web')->setUser($user);
        Auth::shouldUse('web');
        $server = new McpServer(new Toolbox($user, $token->id));

        if (array_is_list($body)) {                          // a batch
            $out = array_values(array_filter(array_map(fn ($m) => $server->handle($m), $body)));

            return $out ? response()->json($out) : response('', 202);
        }
        $out = $server->handle($body);

        return $out ? response()->json($out, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : response('', 202);
    }

    /** Streaming and sessions are not used: clients that try GET / DELETE are told so politely. */
    public function notAllowed(): Response|JsonResponse
    {
        return response()->json(['error' => 'method_not_allowed', 'message' => 'Send MCP messages with POST.'], 405, ['Allow' => 'POST']);
    }

    private function bearer(Request $request): ?string
    {
        $h = (string) $request->header('Authorization');

        return preg_match('/^Bearer\s+(\S+)$/i', $h, $m) ? $m[1] : null;
    }
}
