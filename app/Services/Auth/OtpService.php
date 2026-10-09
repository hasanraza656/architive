<?php

namespace App\Services\Auth;

use App\Mail\Portal\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Support\Facades\Hash;

/**
 * Passwordless sign-in for customers: a 6-digit code is e-mailed, valid for a few minutes, stored hashed,
 * and burned after too many wrong guesses. Unknown e-mails get the same answer as known ones (no account probing).
 */
class OtpService
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    public const SENT = 'sent';
    public const TOO_SOON = 'too_soon';

    /** Sends a code if this e-mail belongs to an active customer. Returns SENT / TOO_SOON (always SENT for unknown e-mails). */
    public function send(string $email, ?string $ip = null): string
    {
        $email = mb_strtolower(trim($email));
        $user = User::customers()->where('email', $email)->where('is_active', true)->first();
        if (! $user) {
            return self::SENT;
        }

        $recent = LoginCode::where('email', $email)->latest('id')->first();
        if ($recent && $recent->created_at->gt(now()->subSeconds(config('portal.otp.resend_after_seconds')))) {
            return self::TOO_SOON;
        }

        LoginCode::where('email', $email)->whereNull('consumed_at')->update(['consumed_at' => now()]);   // older codes stop working

        $len = (int) config('portal.otp.length');
        $code = str_pad((string) random_int(0, (10 ** $len) - 1), $len, '0', STR_PAD_LEFT);
        LoginCode::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('portal.otp.ttl_minutes')),
            'ip' => $ip,
        ]);

        $this->mailer->send($email, new LoginCodeMail($user, $code, (int) config('portal.otp.ttl_minutes')));

        return self::SENT;
    }

    /** Returns the customer when the code is right, otherwise null. */
    public function verify(string $email, string $code): ?User
    {
        $email = mb_strtolower(trim($email));
        $record = LoginCode::where('email', $email)->whereNull('consumed_at')->where('expires_at', '>', now())->latest('id')->first();
        if (! $record) {
            return null;
        }
        if ($record->attempts >= config('portal.otp.max_attempts')) {
            $record->update(['consumed_at' => now()]);

            return null;
        }

        if (! Hash::check(preg_replace('/\D/', '', $code), $record->code_hash)) {
            $record->increment('attempts');

            return null;
        }

        $record->update(['consumed_at' => now()]);

        return User::customers()->where('email', $email)->where('is_active', true)->first();
    }

    /** How many tries are left for the current code (for the UI hint). */
    public function attemptsLeft(string $email): int
    {
        $record = LoginCode::where('email', mb_strtolower(trim($email)))->whereNull('consumed_at')->where('expires_at', '>', now())->latest('id')->first();

        return $record ? max(0, config('portal.otp.max_attempts') - $record->attempts) : 0;
    }
}
