<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Google reCAPTCHA v2 ("I'm not a robot") response server-side.
 *
 * - Fails closed in production if the keys are missing.
 * - Skips verification (local/testing only) when no secret key is configured, so development works without keys.
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = (string) config('services.recaptcha.secret_key');

        if ($secret === '') {
            if (app()->environment('local', 'testing')) {
                return;   // not configured in development
            }
            Log::error('reCAPTCHA secret key is not configured; rejecting form submission.');
            $fail('The security check is temporarily unavailable. Please email us directly.');

            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please tick “I’m not a robot” to continue.');

            return;
        }

        try {
            $result = Http::asForm()->timeout(8)->post(config('services.recaptcha.verify_url'), [
                'secret'   => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed: ' . $e->getMessage());
            $fail('We could not verify the security check. Please try again.');

            return;
        }

        if (empty($result['success'])) {
            Log::info('reCAPTCHA rejected', ['codes' => $result['error-codes'] ?? []]);
            $fail('The security check failed or expired. Please tick “I’m not a robot” again.');
        }
    }
}
