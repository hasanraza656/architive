<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Front-end phase: validates and acknowledges the enquiry.
     * TODO (backend phase): persist + send notification/auto-reply mail.
     */
    public function store(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field.
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
            'message'  => ['required', 'string', 'min:10', 'max:4000'],
            'nda'      => ['nullable'],
            'consent'  => ['accepted'],
        ], [
            'consent.accepted' => 'Please confirm you agree to be contacted about this enquiry.',
        ]);

        Log::info('Contact enquiry received', collect($data)->except('consent')->all());

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
}
