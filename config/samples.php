<?php

/*
|--------------------------------------------------------------------------
| "View complete sheet" links for the sample work (client feedback, 9 Nov 2026)
|--------------------------------------------------------------------------
| The site only shows a cover thumbnail of each sample. The full PDF stays with the client, and each card gets a
| "View complete sheet" button that opens the link below in a new tab. A card without a link simply shows no button.
| Put the links here, or in .env (SAMPLE_URL_ADR=https://... etc.) so no file upload is needed.
|
|   BIM page:  adr = Adaptive Reuse Permit Set, com = Commercial Existing Conditions, drv = Modular Driver Room Shop Drawings,
|              food = Food Service Permit Set, res = Residential Drawing Set
|   CAD page:  adu = ADU plans (2 cards), germany = Hand sketches to CAD plan, survey = UK survey plans (2 cards)
*/

return [
    'adr'     => env('SAMPLE_URL_ADR'),
    'com'     => env('SAMPLE_URL_COM'),
    'drv'     => env('SAMPLE_URL_DRV'),
    'food'    => env('SAMPLE_URL_FOOD'),
    'res'     => env('SAMPLE_URL_RES'),
    'adu'     => env('SAMPLE_URL_ADU'),
    'germany' => env('SAMPLE_URL_GERMANY'),
    'survey'  => env('SAMPLE_URL_SURVEY'),
];
