<?php

/*
|--------------------------------------------------------------------------
| Permit Revisions wizard (/permit-revisions/)
|--------------------------------------------------------------------------
| One place for every number and rule. The page reads this file (as JSON) and the server uses the same values to work out
| the estimate again, so the two can never disagree. Change numbers here only; do not change the formula (client instruction).
|
| 5 Nov 2026 direction from the client: the estimate is based on EXPECTED HOURS at the introductory rate of USD 16 per hour.
| Turnaround time and any rush surcharge are NOT shown: they are confirmed individually by the team.
|
| The hours below are the client's first placeholder PRICES divided by the $16 rate (so the dollar results are unchanged:
| $75 -> 4.6875 h, $60 -> 3.75 h, $22 -> 1.375 h, $9 -> 0.5625 h). Replace them with the real hour estimates when the client sends them.
*/

return [
    'rate' => (float) env('PERMIT_RATE', 16),         // USD per hour (introductory rate, same in every state)
    'currency' => '$',

    // hours of work
    'min_hours' => 4.6875,                             // never estimate below this
    'base_hours' => 3.75,                              // file review and setup
    'hours_per_comment' => 1.375,                      // per city comment
    'hours_per_affected_sheet' => 0.5625,              // per sheet that has to be revised

    // multipliers
    'format_mult' => ['native' => 1.0, 'pdf' => 1.35, 'scan' => 1.6],
    'area_add' => ['structural' => 0.15, 'mep' => 0.10, 'energy' => 0.08, 'zoning' => 0.10, 'fire' => 0.10, 'drafting' => 0],
    'area_cap' => 0.5,                                 // largest total uplift from the ticked areas

    // the range shown around the estimate
    'range_low' => 0.88,
    'range_high' => 1.22,

    // automatic estimates only for straightforward US revision projects; everything else is a manual quote
    'countries' => ['United States', 'Canada', 'United Kingdom', 'Australia', 'United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Germany', 'Italy', 'Other'],
    'auto_countries' => ['United States'],
    'manual_if_comments_over' => 60,
    'manual_if_sheets_over' => 40,

    // upload rules (the server enforces them; the page repeats them for quick feedback)
    'max_files' => 5,
    'max_file_mb' => 10,
    'allowed_extensions' => ['pdf', 'dwg', 'rvt', 'zip', 'png', 'jpg', 'jpeg', 'webp', 'heic'],
];
