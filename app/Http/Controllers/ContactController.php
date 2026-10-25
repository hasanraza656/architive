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
            'attribution' => ['nullable', 'string', 'max:3000'],   // set by attribution.js: first and latest campaign touch
            'lp'       => ['nullable', 'string', 'max:60'],         // ad landing page the form was on
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

        $source = $this->source($data['attribution'] ?? null);
        $enquiry = collect($data)->except(['consent', 'g-recaptcha-response', 'files', 'attribution', 'lp'])->all();
        Log::info('Contact enquiry received', $enquiry + ['source' => $source]);

        $meta = ['ip' => $request->ip(), 'page' => url()->previous() ?: pu('contact', [], [], true), 'at' => now()->format('j M Y, H:i T'), 'source' => $source];

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

        if ($request->filled('lp')) {   // ad landing page without JavaScript: same thank-you page as with it
            return redirect()->to(pu('thanks', [], $order ? ['ref' => $order->number] : []));
        }

        return redirect()->to(pu('contact') . '#enquiry')->with('sent', $message)
            ->with('request', $order ? ['number' => $order->number, 'url' => route('customer.login', ['email' => $order->customer->email])] : null);
    }

    /**
     * Turn the attribution.js JSON into readable lines for the lead e-mail, e.g.
     * ['Latest' => 'google / cpc · campaign us-3d-rendering · keyword "3d floor plan" · Google Ads click · landed on /lp/3d-rendering/', 'First' => ...].
     * Anything unexpected is dropped, so a tampered value can only ever produce less text.
     */
    private function source(?string $json): array
    {
        $raw = json_decode((string) $json, true);
        if (! is_array($raw)) {
            return [];
        }
        $lines = [];
        foreach (['last' => 'Latest', 'first' => 'First'] as $k => $label) {
            $t = is_array($raw[$k] ?? null) ? array_map(fn ($v) => is_scalar($v) ? \Illuminate\Support\Str::limit(trim((string) $v), 150, '') : '', $raw[$k]) : [];
            $bits = array_filter([
                trim(($t['utm_source'] ?? '') . (! empty($t['utm_medium']) ? ' / ' . $t['utm_medium'] : '')),
                ! empty($t['utm_campaign']) ? 'campaign ' . $t['utm_campaign'] : '',
                ! empty($t['utm_term']) ? 'keyword "' . $t['utm_term'] . '"' : '',
                ! empty($t['utm_content']) ? 'ad ' . $t['utm_content'] : '',
                ! empty($t['gclid']) || ! empty($t['gbraid']) || ! empty($t['wbraid']) ? 'Google Ads click' : '',
                empty($t['utm_source']) && ! empty($t['referrer']) ? 'from ' . (parse_url($t['referrer'], PHP_URL_HOST) ?: 'another site') : '',
                ! empty($t['landing']) ? 'landed on ' . $t['landing'] : '',
                ! empty($t['at']) ? $t['at'] : '',
            ]);
            if ($bits) {
                $lines[$label] = implode(' · ', $bits);
            }
        }
        if (isset($lines['Latest'], $lines['First']) && $lines['Latest'] === $lines['First']) {
            unset($lines['First']);
        }

        return $lines;
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
