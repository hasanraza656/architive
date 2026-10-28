<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Mail\Portal\TeamInvite;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/** Team members (super admin and staff) set or reset their password by e-mail link. Customers have no password. */
class PasswordController extends Controller
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    public function showForgot()
    {
        return view('portal.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $key = 'pwreset|' . Str::lower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['email' => 'Please wait a few minutes before asking for another link.']);
        }
        RateLimiter::hit($key, 600);

        $user = User::team()->where('email', Str::lower($data['email']))->where('is_active', true)->first();
        if ($user) {
            $this->mailer->send($user->email, new TeamInvite($user, $this->link($user), null, false));
        }

        // the same answer whether or not the address exists, so nobody can probe which e-mails have accounts
        return back()->with('success', 'If that e-mail belongs to a team account, a link to choose a new password is on its way.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('portal.auth.reset-password', ['token' => $token, 'email' => $request->query('email'), 'invite' => $request->boolean('invite')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::broker('team')->reset(
            ['email' => Str::lower($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (User $user, string $password) {
                abort_unless($user->isTeam() && $user->is_active, 403);
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This link has expired or was already used. Ask for a new one.']);
        }

        return redirect()->route('admin.login')->with('success', 'Your password is saved. You can sign in now.');
    }

    /** One-time link valid for 7 days (also used when a team member is invited). */
    public static function linkFor(User $user, bool $invite = false): string
    {
        $token = Password::broker('team')->createToken($user);

        return route('admin.password.reset', ['token' => $token, 'email' => $user->email] + ($invite ? ['invite' => 1] : []));
    }

    private function link(User $user): string
    {
        return self::linkFor($user);
    }
}
