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

    // Message reactions people can pick from (the first 6 are the quick bar)
    'reactions' => ['👍', '❤️', '😂', '🎉', '👀', '🙏', '✅', '🔥', '😮', '😢', '👏', '💡', '🚀', '❓', '⚠️', '👌', '🙌', '💯', '😍', '🤔', '😅', '👎', '❌', '⭐'],

    // Voice messages and screen recordings (the browser records, the server just stores and streams them)
    'media' => [
        'voice_max_seconds' => 600,
        'screen_max_seconds' => 900,
    ],

    // File uploads (also limited by php.ini upload_max_filesize / post_max_size)
    'uploads' => [
        'disk' => 'uploads',   // = /public/uploads (see config/filesystems.php)
        'max_kb' => 51200,                     // 50 MB per file
        'max_files' => 8,
        'blocked_extensions' => ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'com', 'sh', 'js', 'vbs', 'msi', 'jar', 'dll', 'scr', 'html', 'htm'],
        'inline_image_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ],

    // Google Drive storage: files are saved on the server first, copied to Drive by a background job, and the local copy is
    // removed only after it has been safe in Drive for this many days without being opened.
    'drive' => [
        'keep_local_days' => (int) env('DRIVE_KEEP_LOCAL_DAYS', 30),
        'sync_seconds' => 40,         // how long one background run may work
        'max_attempts' => 8,          // then the file is left alone until an admin presses "Retry"
    ],

    // Stripe's minimum charge is 0.50
    'min_charge_cents' => 50,
];
