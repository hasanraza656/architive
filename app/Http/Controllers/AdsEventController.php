<?php

namespace App\Http\Controllers;

use App\Services\Ads\OpenAiEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Server copy of the browser's WhatsApp tap (OpenAI custom event "whatsapp"). The browser only calls this after it sent its own pixel event
 * with the same id, and only when the visitor's cookie choice allows ad measurement; OpenAI then keeps just one of the two.
 * Public endpoint, so: same-site only, strict id format, an id counts once, rate limited, and it always answers 204.
 */
class AdsEventController extends Controller
{
    public function whatsapp(Request $request, OpenAiEvents $events)
    {
        $origin = $request->headers->get('Origin');
        if ($origin && parse_url($origin, PHP_URL_HOST) !== $request->getHost()) {
            return response()->noContent();
        }

        $id = (string) $request->input('id');
        if (! preg_match('/^wa_[a-z0-9]{8,40}$/', $id) || ! $events->enabled() || ! config('services.openai_pixel.server_whatsapp')) {
            return response()->noContent();
        }
        if (! Cache::add('oai-wa:' . $id, 1, now()->addHour())) {   // the same tap is never sent twice (retries, double beacons)
            return response()->noContent();
        }

        $events->whatsapp($id, [
            'consent' => true,   // the browser only sends this after checking the visitor's choice
            'at_ms' => (int) $request->input('at'),
            'oppref' => (string) $request->input('oppref'),
            'obref' => (string) $request->input('obref'),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 300),
            'source_url' => (string) $request->input('path', '/'),
        ]);

        return response()->noContent();
    }
}
