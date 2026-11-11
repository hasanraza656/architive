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

    // Stripe Checkout for invoices (keys live in .env only)
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Google Drive as long-term file storage (keys live in .env only). The redirect must match the one registered in Google Cloud.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // Tawk.to live chat widget (public embed IDs; set TAWK_ENABLED=false to switch it off)
    'tawk' => [
        'enabled'     => env('TAWK_ENABLED', true),
        'property_id' => env('TAWK_PROPERTY_ID', '686d1b63b0df99190ce93c1c'),
        'widget_id'   => env('TAWK_WIDGET_ID', '1ivl35vbb'),
    ],

    // Google Analytics 4 (public Measurement ID, not a secret). Off outside production unless GA4_ENABLED=true.
    'analytics' => [
        'enabled' => env('GA4_ENABLED', env('APP_ENV', 'production') === 'production'),
        'ga4_id'  => env('GA4_MEASUREMENT_ID', 'G-36RQKB0CMB'),
        // Google Ads (public IDs, not secrets). Empty = no Ads tag. ads_id looks like AW-123456789; labels come from
        // Google Ads > Goals > Conversions > the conversion action > Tag setup ("send_to": "AW-123456789/AbC-dEfGh").
        'ads_id'            => env('GOOGLE_ADS_ID'),
        'ads_lead_label'    => env('GOOGLE_ADS_LEAD_LABEL'),     // form sent (primary conversion)
        'ads_contact_label' => env('GOOGLE_ADS_CONTACT_LABEL'),  // WhatsApp / phone / email tap (secondary)
    ],

    // Google reCAPTCHA v2 ("I'm not a robot" checkbox) for website forms
    'recaptcha' => [
        'site_key'   => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
    ],

];
