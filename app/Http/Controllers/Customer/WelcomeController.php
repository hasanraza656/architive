<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** First sign-in of a new customer: just their name (and optionally a phone), then straight to "New request". */
class WelcomeController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->profile_completed_at) {
            return redirect()->route('customer.dashboard');
        }

        return view('portal.customer.welcome', ['user' => $request->user(), 'countries' => config('countries')]);
    }

    public function save(Request $request): RedirectResponse
    {
        $request->merge(['phone' => preg_replace('/\D+/', '', (string) $request->input('phone')) ?: null]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone_country' => ['nullable', 'required_with:phone', Rule::in(array_keys(config('countries')))],
            'phone' => ['nullable', 'digits_between:4,14'],
        ]);

        $phone = ! empty($data['phone']) ? '+' . config("countries.{$data['phone_country']}.1") . ltrim($data['phone'], '0') : null;
        $user = $request->user();
        $user->update([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
            'phone' => $phone, 'phone_country' => $phone ? $data['phone_country'] : null,
            'profile_completed_at' => now(),
        ]);

        $hasOrders = Order::where('customer_id', $user->id)->exists();

        return $hasOrders
            ? redirect()->intended(route('customer.dashboard'))
            : redirect()->route('customer.requests.create')->with('success', 'Welcome, ' . $user->first_name . '! Tell us about your project below.');
    }
}
