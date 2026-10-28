<?php

namespace App\Http\Controllers\Admin\Team;

use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Controller;
use App\Mail\Portal\TeamInvite;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Team members (users.role = staff). People are switched off, never deleted: their messages, tasks and history must stay.
 */
class UserController extends Controller
{
    public function __construct(private PortalMailer $mailer)
    {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $roleId = (int) $request->query('role');

        $users = User::team()->with('teamRole')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when($roleId, fn ($b) => $b->where('role_id', $roleId))
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->orderBy('first_name')
            ->paginate(20)->withQueryString();

        return view('portal.admin.team.users', ['users' => $users, 'roles' => Role::orderBy('name')->get(), 'q' => $q, 'roleId' => $roleId]);
    }

    public function create()
    {
        return view('portal.admin.team.user-form', ['member' => new User(['is_active' => true, 'role' => User::ROLE_STAFF]), 'roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $invite = $request->boolean('send_invite', true);

        $user = new User([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null, 'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null, 'job_title' => $data['job_title'] ?? null, 'role_id' => $data['role_id'],
            'is_active' => true, 'invited_at' => $invite ? now() : null,
        ]);
        $user->role = User::ROLE_STAFF;
        $user->password = Hash::make($invite ? Str::random(40) : $data['password']);
        $user->email_verified_at = now();
        $user->profile_completed_at = now();
        $user->save();

        if ($invite) {
            $sent = $this->mailer->send($user->email, new TeamInvite($user, PasswordController::linkFor($user, true), $request->user()));

            return redirect()->route('admin.team.users.index')->with($sent ? 'success' : 'error', $sent
                ? "{$user->name} was added. An e-mail with a link to set their password is on its way."
                : "{$user->name} was added, but the e-mail could not be sent. Open their profile and use “Send invitation again”, or check the mail settings.");
        }

        return redirect()->route('admin.team.users.index')->with('success', "{$user->name} was added. They can sign in with the password you chose.");
    }

    public function edit(User $member)
    {
        abort_unless($member->isTeam(), 404);

        return view('portal.admin.team.user-form', ['member' => $member, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->isTeam(), 404);
        $data = $this->validated($request, $member);

        $member->fill([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null, 'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null, 'job_title' => $data['job_title'] ?? null,
        ]);

        if ($member->isStaff()) {
            $member->role_id = $data['role_id'];
            $active = $request->boolean('is_active');
            if (! $active && $member->id === $request->user()->id) {
                return back()->with('error', 'You cannot switch off your own account.')->withInput();
            }
            $member->is_active = $active;
        }
        if (! empty($data['password'])) {
            $member->password = Hash::make($data['password']);
        }
        $member->save();

        return redirect()->route('admin.team.users.index')->with('success', "{$member->name} was saved.");
    }

    public function invite(Request $request, User $member): RedirectResponse
    {
        abort_unless($member->isStaff(), 404);
        $sent = $this->mailer->send($member->email, new TeamInvite($member, PasswordController::linkFor($member, true), $request->user()));
        $member->forceFill(['invited_at' => now()])->save();

        return back()->with($sent ? 'success' : 'error', $sent ? 'Invitation sent again.' : 'The e-mail could not be sent. Check the mail settings.');
    }

    private function validated(Request $request, ?User $member): array
    {
        $isAdmin = $member?->isAdmin() ?? false;
        $sendInvite = ! $member && $request->boolean('send_invite', true);

        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($member?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role_id' => [$isAdmin ? 'nullable' : 'required', 'integer', 'exists:roles,id'],
            'password' => [$member || $sendInvite ? 'nullable' : 'required', Password::min(8)->letters()->numbers()],
        ], [
            'role_id.required' => 'Choose a role. It decides what this person can do.',
            'password.required' => 'Enter a password, or tick “E-mail an invitation” so they can choose their own.',
        ]);
    }
}
