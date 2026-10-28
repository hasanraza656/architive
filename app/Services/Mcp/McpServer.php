<?php

namespace App\Services\Mcp;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * A small Model Context Protocol server (stateless "Streamable HTTP", JSON-RPC 2.0). It speaks just enough of the protocol for
 * Claude and other MCP clients: initialize, ping, tools/list, tools/call. What the tools do lives in Toolbox.
 */
class McpServer
{
    public const VERSION = '1.0.0';

    private const PROTOCOLS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public function __construct(private Toolbox $toolbox)
    {
    }

    /** @return array|null the JSON-RPC response, or null for notifications (nothing to send back) */
    public function handle(mixed $msg): ?array
    {
        if (! is_array($msg) || ($msg['jsonrpc'] ?? null) !== '2.0' || ! isset($msg['method']) || ! is_string($msg['method'])) {
            return $this->error($msg['id'] ?? null, -32600, 'Invalid request');
        }
        $id = $msg['id'] ?? null;
        $isNotification = ! array_key_exists('id', $msg);
        $params = is_array($msg['params'] ?? null) ? $msg['params'] : [];

        try {
            $result = match ($msg['method']) {
                'initialize' => $this->initialize($params),
                'ping' => (object) [],
                'tools/list' => $this->listTools(),
                'tools/call' => $this->callTool($params),
                'resources/list' => ['resources' => []],
                'resources/templates/list' => ['resourceTemplates' => []],
                'prompts/list' => ['prompts' => []],
                'logging/setLevel' => (object) [],
                default => str_starts_with($msg['method'], 'notifications/') ? null : throw new ToolError('__method__'),
            };
        } catch (ToolError $e) {
            return $isNotification ? null : $this->error($id, -32601, 'Method not found: ' . $msg['method']);
        }

        if ($isNotification) {
            return null;
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result ?? (object) []];
    }

    private function initialize(array $params): array
    {
        $asked = (string) ($params['protocolVersion'] ?? '');

        return [
            'protocolVersion' => in_array($asked, self::PROTOCOLS, true) ? $asked : self::PROTOCOLS[0],
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'Architive portal', 'version' => self::VERSION],
            'instructions' => "You are connected to the Architive admin portal, acting as the person who created this connection (their permissions apply).\n"
                . "- Call whoami first. Find ids with the list_* tools; never guess ids.\n"
                . "- Dates are YYYY-MM-DD. Task statuses: backlog, todo, in_progress, review, done.\n"
                . "- reply_to_customer sends a real message to a customer immediately: read get_conversation first and only send what the admin asked for.\n"
                . "- You cannot delete anything or touch invoices, offers, payments or deliveries; tell the admin to do that in the portal.\n"
                . "- After a change, tell the admin exactly what was changed (names, dates, statuses).",
        ];
    }

    private function listTools(): array
    {
        $tools = [];
        foreach ($this->toolbox->tools() as $name => $t) {
            $tools[] = [
                'name' => $name,
                'description' => $t['description'],
                'inputSchema' => $t['schema'],
                'annotations' => ['title' => Str::headline($name), 'readOnlyHint' => ! $t['write'], 'destructiveHint' => false, 'openWorldHint' => false],
            ];
        }

        return ['tools' => $tools];
    }

    private function callTool(array $params): array
    {
        $name = (string) ($params['name'] ?? '');
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        try {
            $data = $this->toolbox->call($name, $args);

            return $this->content($data, false);
        } catch (ToolError $e) {
            return $this->content($e->getMessage(), true);
        } catch (ValidationException $e) {
            $lines = [];
            foreach ($e->errors() as $field => $messages) {
                $lines[] = $field . ': ' . implode(' ', $messages);
            }

            return $this->content('Nothing was changed. The input was not valid. ' . implode(' | ', $lines), true);
        } catch (AuthorizationException) {
            return $this->content('Not allowed: your role does not permit this action. Nothing was changed.', true);
        } catch (ModelNotFoundException) {
            return $this->content('Not found. Check the id with the matching list_* tool. Nothing was changed.', true);
        } catch (HttpExceptionInterface $e) {
            return $this->content($e->getStatusCode() === 403 ? 'Not allowed: your role does not permit this action. Nothing was changed.' : 'The request could not be completed (' . $e->getStatusCode() . ').', true);
        } catch (Throwable $e) {
            $ref = Str::random(8);
            Log::error('MCP tool failed [' . $ref . '] ' . $name . ': ' . $e->getMessage(), ['exception' => $e]);

            return $this->content("Something went wrong on the server (ref {$ref}). Check with get_* / list_* whether the change happened before trying again.", true);
        }
    }

    private function content(mixed $data, bool $isError): array
    {
        $text = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return ['content' => [['type' => 'text', 'text' => $text]], 'isError' => $isError];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
