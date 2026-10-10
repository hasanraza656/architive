<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** The admin's own profile and password. */
class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('portal.admin.settings', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request, \App\Services\Blog\ImageStore $images): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:600'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:6144'],
        ], [
            'avatar.image' => 'Please choose a picture (JPG, PNG or WebP).',
            'avatar.max' => 'That picture is bigger than 6 MB. Please choose a smaller one.',
            'bio.max' => 'Keep the short bio under 600 characters.',
        ]);

        $update = ['email' => mb_strtolower($data['email']), 'first_name' => $data['first_name'], 'last_name' => $data['last_name'] ?? null,
            'job_title' => $data['job_title'] ?? null, 'bio' => $data['bio'] ?? null];

        try {
            if ($request->hasFile('avatar')) {
                $images->delete($user->avatar);
                $update['avatar'] = $images->store($request->file('avatar'), 'avatars', 600, 400);
            } elseif ($request->boolean('remove_avatar')) {
                $images->delete($user->avatar);
                $update['avatar'] = null;
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['avatar' => $e->getMessage()])->withInput();
        }
        $user->update($update);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password changed.');
    }
}
