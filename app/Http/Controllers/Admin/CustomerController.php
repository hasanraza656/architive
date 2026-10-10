<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customers. A customer account has no password: they sign in with an e-mailed code (OtpService).
 * store() answers JSON when called from the order form so a customer can be added without leaving the page.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $customers = User::customers()
            ->withCount('orders')
            ->withSum(['orders as paid_cents' => fn ($o) => $o->whereNotNull('paid_at')], 'total_cents')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('first_name', 'like', "%$q%")->orWhere('last_name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->latest('id')->paginate(15)->withQueryString();

        return view('portal.admin.customers.index', compact('customers', 'q'));
    }

    public function create()
    {
        return view('portal.admin.customers.form', ['customer' => new User(['phone_country' => 'US']), 'countries' => config('countries')]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse|RedirectResponse
    {
        $customer = User::create($request->customerData() + ['role' => User::ROLE_CUSTOMER, 'is_active' => true]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ], 201);
        }

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(User $customer)
    {
        $customer->load(['orders' => fn ($q) => $q->latest('id')]);

        return view('portal.admin.customers.show', compact('customer'));
    }

    /** Removes the customer together with all of their orders, requests, messages and files. */
    public function destroy(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);
        $name = $customer->name ?: $customer->email;

        DB::transaction(function () use ($customer) {
            $customer->orders()->get()->each->delete();   // one by one so uploaded files are removed from disk too
            \App\Models\LoginCode::where('email', $customer->email)->delete();
            $customer->delete();
        });

        return redirect()->route('admin.customers.index')->with('success', "Customer $name and all of their orders were deleted.");
    }

    public function edit(User $customer)
    {
        return view('portal.admin.customers.form', ['customer' => $customer, 'countries' => config('countries')]);
    }

    public function update(StoreCustomerRequest $request, User $customer): RedirectResponse
    {
        $data = $request->customerData();
        $data['is_active'] = $request->boolean('is_active');
        $customer->update($data);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated.');
    }
}
