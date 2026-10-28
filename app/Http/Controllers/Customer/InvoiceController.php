<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Customer > Invoices: the Stripe invoices that were sent to the e-mail address of this account. */
class InvoiceController extends Controller
{
    public function __construct(private StripeInvoices $stripe)
    {
    }

    public function index(Request $request)
    {
        $rows = [];
        $error = null;
        if ($this->stripe->configured()) {
            try {
                $rows = $this->stripe->forEmail($request->user()->email, $request->boolean('fresh'));
            } catch (Throwable $e) {
                Log::warning('Customer Stripe invoices failed: ' . $e->getMessage());
                $error = 'We could not load your invoices right now. Please try again in a moment.';
            }
        }

        return view('portal.customer.invoices', ['rows' => $rows, 'error' => $error]);
    }
}
