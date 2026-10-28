<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Payments\StripeInvoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Admin > Stripe: the invoices that live in Stripe, with filters. Read-only. */
class StripeController extends Controller
{
    public function __construct(private StripeInvoices $stripe)
    {
    }

    public function index(Request $request)
    {
        $f = [
            'status' => in_array($request->query('status'), array_keys(StripeInvoices::STATUSES), true) ? $request->query('status') : null,
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
            'customer' => trim((string) $request->query('customer')),
            'number' => trim((string) $request->query('number')),
            'after' => trim((string) $request->query('after')),
        ];

        $data = ['rows' => [], 'has_more' => false, 'next' => null, 'mode' => 'all', 'note' => null];
        $error = null;
        if (! $this->stripe->configured()) {
            $error = 'Stripe is not connected yet. Add STRIPE_SECRET to the server settings (.env).';
        } else {
            try {
                $data = $this->stripe->search($f, $request->boolean('fresh'));
            } catch (Throwable $e) {
                Log::warning('Stripe invoice list failed: ' . $e->getMessage());
                $error = 'Could not load invoices from Stripe right now. ' . str($e->getMessage())->limit(160) . ' Please try again in a moment.';
            }
        }

        $rows = $data['rows'];
        $emails = collect($rows)->pluck('customer_email')->filter()->map(fn ($e) => mb_strtolower($e))->unique()->values()->all();
        $local = $emails ? User::customers()->whereIn(\DB::raw('LOWER(email)'), $emails)->get()->keyBy(fn ($u) => mb_strtolower($u->email)) : collect();

        $sums = [];
        foreach ($rows as $r) {
            $c = strtolower($r['currency']);
            $sums[$c]['paid'] = ($sums[$c]['paid'] ?? 0) + $r['amount_paid'];
            $sums[$c]['open'] = ($sums[$c]['open'] ?? 0) + ($r['status'] === 'open' ? $r['amount_due'] : 0);
        }

        return view('portal.admin.stripe.index', [
            'f' => $f, 'rows' => $rows, 'has_more' => $data['has_more'], 'next' => $data['next'], 'mode' => $data['mode'], 'note' => $data['note'], 'error' => $error,
            'local' => $local, 'sums' => $sums, 'filtered' => (bool) array_filter(array_diff_key($f, ['after' => 1])),
        ]);
    }

    public function show(Request $request, string $id)
    {
        try {
            $inv = $this->stripe->find($id);
        } catch (Throwable $e) {
            Log::warning('Stripe invoice failed: ' . $e->getMessage());

            return redirect()->route('admin.stripe.index')->with('error', 'Could not load that invoice from Stripe: ' . str($e->getMessage())->limit(160));
        }
        abort_unless($inv, 404);
        $local = $inv['customer_email'] ? User::customers()->whereRaw('LOWER(email) = ?', [mb_strtolower($inv['customer_email'])])->first() : null;

        return view('portal.admin.stripe.show', ['inv' => $inv, 'local' => $local]);
    }

    private function date(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
