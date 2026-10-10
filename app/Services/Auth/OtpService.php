<?php

namespace App\Services\Auth;

use App\Mail\Portal\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Passwordless sign-in for customers: a 6-digit code is e-mailed, valid for a few minutes, stored hashed,
 * and burned after too many wrong guesses. Anyone can sign in with their e-mail: a customer account is created
 * the moment the code is confirmed (so new people never hit a dead end). Admin accounts can never use this flow.
 */
class OtpService
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    public const SENT = 'sent';
    public const TOO_SOON = 'too_soon';

    /** Sends a code to this e-mail (existing customer or newcomer). Returns SENT / TOO_SOON. Admin and disabled accounts silently get nothing. */
    public function send(string $email, ?string $ip = null): string
    {
        $email = mb_strtolower(trim($email));
        $user = User::where('email', $email)->first();
        if ($user && (! $user->isCustomer() || ! $user->is_active)) {
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

    /** Returns the customer when the code is right (creating the account for a newcomer), otherwise null. */
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

        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing->isCustomer() && $existing->is_active ? $existing : null;
        }

        // newcomer: instant account, no password. The welcome step asks for their name.
        return User::create([
            'role' => User::ROLE_CUSTOMER, 'email' => $email, 'is_active' => true, 'email_verified_at' => now(),
            'first_name' => Str::of(Str::before($email, '@'))->replaceMatches('/[^A-Za-z]+/', ' ')->trim()->title()->limit(40, '')->toString() ?: 'Customer',
        ]);
    }

    /** How many tries are left for the current code (for the UI hint). */
    public function attemptsLeft(string $email): int
    {
        $record = LoginCode::where('email', mb_strtolower(trim($email)))->whereNull('consumed_at')->where('expires_at', '>', now())->latest('id')->first();

        return $record ? max(0, config('portal.otp.max_attempts') - $record->attempts) : 0;
    }
}
