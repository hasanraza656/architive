<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Customer sign-in without a password:  e-mail  ->  6-digit code by e-mail  ->  signed in.
 * The same flow serves invoice links: opening an order while signed out lands here first, then returns to the order.
 */
class LoginController extends Controller
{
    private const SESSION_EMAIL = 'customer_login_email';

    public function __construct(private OtpService $otp)
    {
    }

    /** Step 1: ask for the e-mail address. */
    public function show(Request $request)
    {
        return view('portal.auth.customer-login', ['email' => old('email', $request->query('email'))]);
    }

    /** Step 1 submit: send the code. */
    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $email = mb_strtolower(trim($data['email']));

        $key = 'otp-send|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            throw ValidationException::withMessages(['email' => 'Too many requests. Please wait a minute and try again.']);
        }
        RateLimiter::hit($key, 60);

        $result = $this->otp->send($email, $request->ip());
        $request->session()->put(self::SESSION_EMAIL, $email);

        return redirect()->route('customer.login.verify')->with(
            $result === OtpService::TOO_SOON ? 'info' : 'success',
            $result === OtpService::TOO_SOON ? 'A code was just sent. Please check your inbox (and spam folder).' : 'If this address has an account, a 6-digit code is on its way.'
        );
    }

    /** Step 2: enter the code. */
    public function showVerify(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('customer.login');
        }

        return view('portal.auth.customer-verify', [
            'email' => $email,
            'resendAfter' => config('portal.otp.resend_after_seconds'),
            'ttl' => config('portal.otp.ttl_minutes'),
        ]);
    }

    /** Step 2 submit: check the code and sign in. */
    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('customer.login');
        }
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);

        $key = 'otp-verify|' . $email . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Please request a new code in a few minutes.']);
        }

        $user = $this->otp->verify($email, $data['code']);
        if (! $user) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['code' => 'That code is not valid or has expired. Please check it, or request a new one.']);
        }

        RateLimiter::clear($key);
        Auth::login($user, true);
        $request->session()->forget(self::SESSION_EMAIL);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        // brand-new account: ask for their name once, then straight to "New request"
        if (! $user->profile_completed_at) {
            return redirect()->route('customer.welcome');
        }

        return redirect()->intended(route('customer.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
