<?php

namespace App\Support;

/**
 * The Permit Revisions estimate (config/permit.php). The page runs the same steps in JavaScript so the visitor sees the number
 * straight away; the server runs them again from the answers (never trusting a number sent by the browser) and stores the result
 * with the request. Hours first, dollars = hours x the hourly rate. No turnaround or rush price: those are confirmed by the team.
 */
final class PermitEstimator
{
    /**
     * @param  array{need?:string,ptype?:string,scope?:string,country?:string,sheets?:int|string,comments?:int|string,areas?:array,format?:string}  $a
     * @return array{manual:bool,reasons?:array<int,string>,hours?:float,hours_low?:int,hours_high?:int,price_low?:int,price_high?:int,affected?:int,comments?:int,sheets?:int,areas?:array,format?:string,uplift?:float}
     */
    public static function estimate(array $a): array
    {
        $c = config('permit');
        $need = (string) ($a['need'] ?? '');
        $sheets = max(0, (int) ($a['sheets'] ?? 0));
        $comments = max(0, (int) ($a['comments'] ?? 0));
        $fmt = (string) ($a['format'] ?? '') ?: 'native';
        $areas = array_values(array_intersect(array_unique((array) ($a['areas'] ?? [])), array_keys($c['area_add'])));
        $country = (string) ($a['country'] ?? '');

        $reasons = [];
        if ($need === 'new_set') {
            $reasons[] = 'New permit sets are scoped individually.';
        }
        if ($need === 'unsure') {
            $reasons[] = 'We want to look at your set before suggesting a scope.';
        }
        if (! in_array($country, $c['auto_countries'], true)) {
            $reasons[] = 'We confirm local requirements outside the United States before pricing.';
        }
        if ($comments > $c['manual_if_comments_over']) {
            $reasons[] = 'A letter with more than ' . $c['manual_if_comments_over'] . ' comments needs a manual review.';
        }
        if ($sheets > $c['manual_if_sheets_over']) {
            $reasons[] = 'Sets of more than ' . $c['manual_if_sheets_over'] . ' sheets are quoted after review.';
        }
        if (($a['scope'] ?? '') === 'change_use' || ($a['ptype'] ?? '') === 'other') {
            $reasons[] = 'This project type needs a closer look.';
        }
        if ($reasons) {
            return ['manual' => true, 'reasons' => $reasons];
        }

        $affected = $need === 'design_change' ? max(1, (int) round($sheets * 0.5)) : min($sheets, max(1, (int) round($comments * 0.7)));
        $units = $need === 'design_change' ? 0 : $comments;
        $sub = $c['base_hours'] + $units * $c['hours_per_comment'] + $affected * $c['hours_per_affected_sheet'];
        $uplift = min($c['area_cap'], array_sum(array_map(fn ($k) => $c['area_add'][$k] ?? 0, $areas)));
        $mult = ($c['format_mult'][$fmt] ?? 1) * (1 + $uplift);
        $hours = max($c['min_hours'], $sub * $mult);
        $price = $hours * $c['rate'];
        $round5 = fn (float $n): int => (int) (round($n / 5) * 5);

        return [
            'manual' => false,
            'hours' => $hours,
            'hours_low' => (int) round($hours * $c['range_low']), 'hours_high' => (int) round($hours * $c['range_high']),
            'price_low' => $round5($price * $c['range_low']), 'price_high' => $round5($price * $c['range_high']),
            'affected' => $affected, 'comments' => $units, 'sheets' => $sheets, 'areas' => $areas, 'format' => $fmt, 'uplift' => $uplift,
        ];
    }
}
