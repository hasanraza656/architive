<?php

/*
|--------------------------------------------------------------------------
| Admin + customer portal settings
|--------------------------------------------------------------------------
*/
return [
    'currency' => 'usd',                       // single store currency (ISO code, lowercase)
    'order_prefix' => 'ARC-',
    'order_number_start' => 1000,              // first order becomes ARC-1001

    // One-time sign-in codes for customers
    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'resend_after_seconds' => 45,
    ],

    // Chat e-mail notifications (kept deliberately quiet)
    'chat' => [
        'online_window_seconds' => 120,        // a user counts as "on the page" if they polled within this time
        'email_cooldown_minutes' => 30,        // at most one "new message" email per person per order in this window
        'digest_after_minutes' => 5,           // the scheduled digest only mails messages that have stayed unread this long
        'poll_seconds' => 4,
    ],

    // File uploads (also limited by php.ini upload_max_filesize / post_max_size)
    'uploads' => [
        'disk' => 'uploads',   // = /public/uploads (see config/filesystems.php)
        'max_kb' => 51200,                     // 50 MB per file
        'max_files' => 8,
        'blocked_extensions' => ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'com', 'sh', 'js', 'vbs', 'msi', 'jar', 'dll', 'scr', 'html', 'htm'],
        'inline_image_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ],

    // Stripe's minimum charge is 0.50
    'min_charge_cents' => 50,
];
