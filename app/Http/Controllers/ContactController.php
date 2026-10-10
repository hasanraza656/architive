<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use App\Services\Notifications\PortalMailer;
use App\Services\Orders\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __construct(private RequestService $requests, private PortalMailer $mailer)
    {
    }

    /**
     * Project enquiry form (contact page + pop-up). Validates (incl. reCAPTCHA v2), then
     *   1. creates / reuses the visitor's customer account (instant, no password),
     *   2. opens an "order request" with their brief + attachments (a lead the team answers in the portal),
     *   3. e-mails the admin (ADMIN_EMAIL) and sends the visitor a receipt with a link to follow it.
     * A lead is never lost: once the request is stored, an e-mail problem is only logged.
     */
    public function store(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field. Pretend success, send nothing.
        if ($request->filled('website')) {
            return $this->respond($request);
        }

        $u = config('portal.uploads');
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email:rfc', 'max:160'],
            'company'  => ['nullable', 'string', 'max:160'],
            'audience' => ['nullable', 'in:firm,interior,developer,homeowner'],
            'service'  => ['nullable', 'in:visualization,bim,cad,outsourcing,unsure'],
            'deadline' => ['nullable', 'date'],
            'links'    => ['nullable', 'string', 'max:1000'],
            'topic'    => ['nullable', 'string', 'max:160'],
            'message'  => ['required', 'string', 'min:10', 'max:4000'],
            'nda'      => ['nullable'],
            'consent'  => ['accepted'],
            'files'    => ['nullable', 'array', 'max:5'],
            'files.*'  => ['file', 'max:10240', function ($attr, $file, $fail) use ($u) {
                if (in_array(strtolower($file->getClientOriginalExtension()), $u['blocked_extensions'], true)) {
                    $fail('This file type is not allowed. Please send it as a .zip.');
                }
            }],
            // without keys the check is skipped in local/testing only (see Recaptcha); elsewhere it is mandatory
            'g-recaptcha-response' => [
                (! config('services.recaptcha.secret_key') && app()->environment('local', 'testing')) ? 'nullable' : 'required',
                new Recaptcha,
            ],
        ], [
            'consent.accepted'              => 'Please confirm you agree to be contacted about this enquiry.',
            'g-recaptcha-response.required' => 'Please tick “I’m not a robot” to continue.',
            'files.max'                     => 'You can attach up to 5 files.',
            'files.*.max'                   => 'Each file can be up to 10 MB. For bigger files, share a link instead.',
            'files.*.uploaded'              => 'A file could not be uploaded. It may be too large (limit 10 MB each).',
        ]);

        $enquiry = collect($data)->except(['consent', 'g-recaptcha-response', 'files'])->all();
        Log::info('Contact enquiry received', $enquiry);

        $meta = ['ip' => $request->ip(), 'page' => url()->previous() ?: pu('contact', [], [], true), 'at' => now()->format('j M Y, H:i T')];

        $order = null;
        try {
            $customer = $this->requests->customerFor($data['name'], $data['email']);
            $order = $customer ? $this->requests->create($customer, $data, $request->file('files', []), 'website') : null;
        } catch (\Throwable $e) {
            report($e);   // never block the enquiry: fall back to the plain e-mail below
        }

        // admin notification (with the portal link when a request was created)
        if ($order) {
            $adminOk = $this->requests->notifyAdmin($order, $enquiry, $meta);
            $this->requests->notifyCustomer($order);
        } else {
            $adminOk = $this->mailer->toAdmin(new \App\Mail\ContactEnquiry($enquiry, $meta));
            if (! config('site.admin_email')) {
                Log::error('ADMIN_EMAIL is not set; enquiry was only logged.');
            }
        }

        if (! $order && ! $adminOk) {
            return $this->fail($request);
        }

        return $this->respond($request, $order);
    }

    private function respond(Request $request, $order = null)
    {
        $message = $order
            ? 'Thank you! Your request ' . $order->number . ' is in. We will review it and reply with a practical next step, usually within one business day.'
            : 'Thank you—your enquiry is in. We will review the information and reply with a practical next step.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message] + ($order ? [
                'request' => $order->number,
                'portal_url' => route('customer.login', ['email' => $order->customer->email]),
            ] : []));
        }

        return redirect()->to(pu('contact') . '#enquiry')->with('sent', $message)
            ->with('request', $order ? ['number' => $order->number, 'url' => route('customer.login', ['email' => $order->customer->email])] : null);
    }

    private function fail(Request $request)
    {
        $message = 'Sorry, we could not send your enquiry just now. Please try again, or email ' . config('site.email') . ' directly.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 500);
        }

        return back()->withInput()->withErrors(['mail' => $message]);
    }
}
