<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Customers can update their name and phone. The e-mail is their sign-in, so it is changed by us on request. */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('portal.customer.profile', ['user' => $request->user(), 'countries' => config('countries')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['phone' => preg_replace('/\D+/', '', (string) $request->input('phone')) ?: null]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone_country' => ['nullable', 'required_with:phone', Rule::in(array_keys(config('countries')))],
            'phone' => ['nullable', 'digits_between:4,14'],
        ]);

        $phone = ! empty($data['phone']) ? '+' . config("countries.{$data['phone_country']}.1") . ltrim($data['phone'], '0') : null;
        $request->user()->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $phone,
            'phone_country' => $phone ? $data['phone_country'] : null,
        ]);

        return back()->with('success', 'Profile saved.');
    }
}
