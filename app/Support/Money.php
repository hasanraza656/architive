<?php

namespace App\Support;

/** Money lives in cents everywhere; this turns it into text and back. */
class Money
{
    private const SYMBOLS = ['usd' => '$', 'eur' => '€', 'gbp' => '£', 'cad' => 'CA$', 'aud' => 'A$'];

    public static function format(int|float|null $cents, ?string $currency = null): string
    {
        $currency = strtolower($currency ?: config('portal.currency'));
        $amount = number_format(((int) $cents) / 100, 2, '.', ',');
        $symbol = self::SYMBOLS[$currency] ?? null;

        return $symbol ? $symbol . $amount : strtoupper($currency) . ' ' . $amount;
    }

    /** "12.5" / "1,200.00" / "$40" -> 1250 / 120000 / 4000 cents. Invalid input -> 0. */
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $clean === '' || ! is_numeric($clean) ? 0 : (int) round(((float) $clean) * 100);
    }
}
