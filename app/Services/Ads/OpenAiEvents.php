<?php

namespace App\Services\Ads;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server copy of the OpenAI (ChatGPT Ads) pixel events (Conversions API, https://bzr.openai.com/v1/events).
 *
 * Browser and server send the SAME event with the SAME id; OpenAI matches on pixel id + event name (custom_event_name for custom events) + id
 * and keeps the first one, so nothing is counted twice:
 *   lead_created     id "lead_<request number>"  (pixel: lead_created with event_id "lead_<request number>")
 *   custom whatsapp  id "wa_..."                 (pixel: custom_event_name whatsapp with the same event_id)
 * The API key lives in .env only (OPENAI_CAPI_KEY). Nothing is sent without the key, without the visitor's ad consent, or when the pixel is off.
 * Failures are logged and never reach the visitor. Retries reuse the same id and timestamp (as OpenAI requires).
 */
class OpenAiEvents
{
    public const ENDPOINT = 'https://bzr.openai.com/v1/events';

    public function enabled(): bool
    {
        $c = config('services.openai_pixel');

        return ! empty($c['enabled']) && ! empty($c['id']) && ! empty($c['api_key']) && ! empty($c['server_events']);
    }

    /** @param array{consent?:bool,oppref?:?string,obref?:?string,ip?:?string,user_agent?:?string,source_url?:?string,at_ms?:?int} $ctx */
    public function lead(Order $order, array $ctx): void
    {
        if (! $this->wants($ctx)) {
            return;
        }
        $user = array_filter([
            'emails_sha256' => $order->customer?->email ? [hash('sha256', mb_strtolower(trim($order->customer->email)))] : null,
            'ip_address' => $ctx['ip'] ?? null,
            'user_agent' => $ctx['user_agent'] ?? null,
            'obref' => $this->ref($ctx['obref'] ?? null),
        ]);

        $this->queue([
            'id' => 'lead_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order->number),
            'type' => 'lead_created',
            'timestamp_ms' => $this->ts($ctx),
            'oppref' => $this->ref($ctx['oppref'] ?? null),
            'source_url' => $this->url($ctx['source_url'] ?? null),
            'action_source' => 'web',
            'user' => $user ?: null,
            'data' => ['type' => 'customer_action'],
        ]);
    }

    /** @param array{consent?:bool,oppref?:?string,obref?:?string,ip?:?string,user_agent?:?string,source_url?:?string,at_ms?:?int} $ctx */
    public function whatsapp(string $eventId, array $ctx): void
    {
        if (! $this->wants($ctx)) {
            return;
        }
        $user = array_filter([
            'ip_address' => $ctx['ip'] ?? null,
            'user_agent' => $ctx['user_agent'] ?? null,
            'obref' => $this->ref($ctx['obref'] ?? null),
        ]);

        $this->queue([
            'id' => $eventId,
            'type' => 'custom',
            'custom_event_name' => 'whatsapp',
            'timestamp_ms' => $this->ts($ctx),
            'oppref' => $this->ref($ctx['oppref'] ?? null),
            'source_url' => $this->url($ctx['source_url'] ?? null),
            'action_source' => 'web',
            'user' => $user ?: null,
            'data' => ['type' => 'custom'],
        ]);
    }

    private function wants(array $ctx): bool
    {
        return $this->enabled() && ! empty($ctx['consent']);
    }

    /** Sent after the visitor already has the answer, so a slow OpenAI never slows a form or a WhatsApp tap. */
    private function queue(array $event): void
    {
        $event = array_filter($event, fn ($v) => $v !== null);
        $done = false;   // a terminating callback stays registered on long-lived apps (tests, Octane): it must run once
        app()->terminating(function () use ($event, &$done) {
            if (! $done) {
                $done = true;
                $this->send($event);
            }
        });
    }

    public function send(array $event): bool
    {
        $c = config('services.openai_pixel');
        try {
            $response = Http::withToken((string) $c['api_key'])->acceptJson()->asJson()->timeout(6)
                ->retry(3, 400, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->serverError()), throw: false)
                ->post(self::ENDPOINT . '?pid=' . urlencode((string) $c['id']), [
                    'validate_only' => false,
                    'integration_source' => 'architive-laravel',
                    'events' => [$event],
                ]);
            if ($response->successful()) {
                return true;
            }
            // Never log the key or personal data: only the event id, status and OpenAI's own error text / request id.
            Log::warning('OpenAI conversion event rejected', ['event' => $event['id'] ?? null, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300), 'cf_ray' => $response->header('CF-Ray')]);
        } catch (\Throwable $e) {
            Log::warning('OpenAI conversion event failed', ['event' => $event['id'] ?? null, 'error' => $e->getMessage()]);
        }

        return false;
    }

    private function ts(array $ctx): int
    {
        $now = (int) round(microtime(true) * 1000);
        $at = (int) ($ctx['at_ms'] ?? 0);

        // the real time of the action; ignore a browser clock that is more than a minute out
        return ($at > 0 && abs($at - $now) < 60000) ? $at : $now;
    }

    /** Opaque OpenAI references are passed through unchanged, only length / characters are checked. */
    private function ref(?string $v): ?string
    {
        $v = trim((string) $v);

        return ($v !== '' && strlen($v) <= 200 && preg_match('/^[\x21-\x7E]+$/', $v)) ? $v : null;
    }

    /** Page address without query string or fragment (no tokens, e-mail addresses or tags). */
    private function url(?string $u): string
    {
        $p = parse_url((string) $u);
        $base = rtrim((string) config('app.url'), '/');
        $path = (is_array($p) && ! empty($p['path'])) ? $p['path'] : '/';

        return $base . (str_starts_with($path, '/') ? $path : '/' . $path);
    }
}
