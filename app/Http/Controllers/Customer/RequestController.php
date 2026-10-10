<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\ContactEnquiry;
use App\Services\Orders\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "New request" inside the client area: same as the website form, but already signed in. */
class RequestController extends Controller
{
    public function __construct(private RequestService $requests)
    {
    }

    public function create(Request $request)
    {
        return view('portal.customer.request-new', [
            'services' => ContactEnquiry::SERVICES,
            'audiences' => ContactEnquiry::AUDIENCES,
            'selService' => $request->query('service'),
            'maxMb' => min(config('portal.uploads.max_kb') / 1024, (int) ini_get('upload_max_filesize')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $u = config('portal.uploads');
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(ContactEnquiry::SERVICES))],
            'audience' => ['nullable', Rule::in(array_keys(ContactEnquiry::AUDIENCES))],
            'company' => ['nullable', 'string', 'max:160'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'links' => ['nullable', 'string', 'max:1000'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'nda' => ['nullable'],
            'files' => ['nullable', 'array', 'max:' . $u['max_files']],
            'files.*' => ['file', 'max:' . $u['max_kb'], function ($attr, $file, $fail) use ($u) {
                if (in_array(strtolower($file->getClientOriginalExtension()), $u['blocked_extensions'], true)) {
                    $fail('This file type is not allowed. Put it in a .zip first.');
                }
            }],
        ], [
            'service.required' => 'Choose what you need help with.',
            'message.required' => 'Tell us a little about the project.',
            'message.min' => 'A little more detail helps: at least 10 characters.',
            'files.*.uploaded' => 'A file could not be uploaded. It may be bigger than the server allows (' . ini_get('upload_max_filesize') . ').',
        ]);

        $order = $this->requests->create($request->user(), $data, $request->file('files', []), 'portal');
        $this->requests->notifyAdmin($order, ['links' => $data['links'] ?? null, 'nda' => $data['nda'] ?? null], ['page' => url()->previous(), 'at' => now()->format('j M Y, H:i T')]);

        return redirect()->to($order->customerUrl())->with('success', 'Request sent! We will reply in the conversation below, usually within one business day.');
    }
}
