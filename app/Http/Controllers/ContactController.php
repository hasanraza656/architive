<?php

namespace App\Http\Controllers;

use App\Mail\ContactEnquiry;
use App\Rules\Recaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Project enquiry form: validates (incl. reCAPTCHA v2), logs the enquiry and
     * emails the admin (ADMIN_EMAIL). The visitor's address is set as Reply-To.
     */
    public function store(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field. Pretend success, send nothing.
        if ($request->filled('website')) {
            return $this->respond($request);
        }

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
            'g-recaptcha-response' => ['required', new Recaptcha],
        ], [
            'consent.accepted'              => 'Please confirm you agree to be contacted about this enquiry.',
            'g-recaptcha-response.required' => 'Please tick “I’m not a robot” to continue.',
        ]);

        $enquiry = collect($data)->except(['consent', 'g-recaptcha-response'])->all();
        Log::info('Contact enquiry received', $enquiry);

        $admin = config('site.admin_email');
        if (! $admin) {
            Log::error('ADMIN_EMAIL is not set; enquiry was only logged.');

            return $this->fail($request);
        }

        try {
            Mail::to($admin)->send(new ContactEnquiry($enquiry, [
                'ip'   => $request->ip(),
                'page' => url()->previous() ?: pu('contact', [], [], true),
                'at'   => now()->format('j M Y, H:i T'),
            ]));
        } catch (\Throwable $e) {
            report($e);   // the enquiry is still in the log above

            return $this->fail($request);
        }

        return $this->respond($request);
    }

    private function respond(Request $request)
    {
        $message = 'Thank you—your enquiry is in. We will review the information and reply with a practical next step.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->to(pu('contact') . '#enquiry')->with('sent', $message);
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
