<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Tawk.to live chat widget (public embed IDs; set TAWK_ENABLED=false to switch it off)
    'tawk' => [
        'enabled'     => env('TAWK_ENABLED', true),
        'property_id' => env('TAWK_PROPERTY_ID', '686d1b63b0df99190ce93c1c'),
        'widget_id'   => env('TAWK_WIDGET_ID', '1ivl35vbb'),
    ],

    // Google reCAPTCHA v2 ("I'm not a robot" checkbox) for website forms
    'recaptcha' => [
        'site_key'   => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],

];
