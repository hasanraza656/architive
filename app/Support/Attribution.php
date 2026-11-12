<?php

namespace App\Support;

/** Turns the attribution.js JSON (first and latest campaign touch) into readable lines for lead e-mails. */
final class Attribution
{
    public static function lines(?string $json): array
    {
        $raw = json_decode((string) $json, true);
        if (! is_array($raw)) {
            return [];
        }
        $lines = [];
        foreach (['last' => 'Latest', 'first' => 'First'] as $k => $label) {
            $t = is_array($raw[$k] ?? null) ? array_map(fn ($v) => is_scalar($v) ? \Illuminate\Support\Str::limit(trim((string) $v), 150, '') : '', $raw[$k]) : [];
            $bits = array_filter([
                trim(($t['utm_source'] ?? '') . (! empty($t['utm_medium']) ? ' / ' . $t['utm_medium'] : '')),
                ! empty($t['utm_campaign']) ? 'campaign ' . $t['utm_campaign'] : '',
                ! empty($t['utm_term']) ? 'keyword "' . $t['utm_term'] . '"' : '',
                ! empty($t['utm_content']) ? 'ad ' . $t['utm_content'] : '',
                ! empty($t['gclid']) || ! empty($t['gbraid']) || ! empty($t['wbraid']) ? 'Google Ads click' : '',
                empty($t['utm_source']) && ! empty($t['referrer']) ? 'from ' . (parse_url($t['referrer'], PHP_URL_HOST) ?: 'another site') : '',
                ! empty($t['landing']) ? 'landed on ' . $t['landing'] : '',
                ! empty($t['at']) ? $t['at'] : '',
            ]);
            if ($bits) {
                $lines[$label] = implode(' · ', $bits);
            }
        }
        // The raw Google click ID is what lets the agency report this lead back to Google Ads (offline conversion import: qualified, won).
        foreach (['last', 'first'] as $k) {
            $t = is_array($raw[$k] ?? null) ? $raw[$k] : [];
            foreach (['gclid' => 'gclid', 'gbraid' => 'gbraid', 'wbraid' => 'wbraid'] as $key => $name) {
                if (! empty($t[$key]) && is_scalar($t[$key]) && preg_match('/^[A-Za-z0-9_-]{10,200}$/', (string) $t[$key])) {
                    $lines['Google click ID'] = $name . ' ' . $t[$key];
                    break 2;
                }
            }
        }
        if (isset($lines['Latest'], $lines['First']) && $lines['Latest'] === $lines['First']) {
            unset($lines['First']);
        }

        return $lines;
    }
}
