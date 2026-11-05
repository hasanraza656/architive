<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use App\Support\Attribution;
use App\Support\PermitEstimator;
use App\Support\PermitFiles;
use App\Services\Orders\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Permit Revisions wizard (/permit-revisions/).
 *   show():  the five-step page. All numbers come from config/permit.php.
 *   store(): receives the enquiry and its files, checks everything again on the server, works the estimate out again, then (like the
 *            contact form) finds or creates the customer's portal account, opens a draft request with the answers, files and estimate,
 *            and e-mails both the team (ADMIN_EMAIL) and the customer. A lead is never lost: once stored, a mail problem is only logged.
 */
class PermitRevisionController extends Controller
{
    private const LABELS = [
        'fix_comments' => 'Fix city / plan-check comments', 'design_change' => 'Revise for a design change', 'new_set' => 'New permit drawing set', 'unsure' => 'Not sure, review my set',
        'residential' => 'Residential', 'adu' => 'ADU / guest house', 'commercial' => 'Commercial', 'ti' => 'Tenant improvement', 'other' => 'Other',
        'new' => 'New build', 'addition' => 'Addition', 'remodel' => 'Remodel / interior', 'change_use' => 'Change of use',
        'native' => 'DWG / Revit', 'pdf' => 'PDF only', 'scan' => 'Scans / photos',
        'no' => 'No', 'have' => 'Yes, has a licensed professional', 'need' => 'Yes, needs one',
        'structural' => 'Structural', 'mep' => 'Mechanical / electrical / plumbing', 'energy' => 'Energy compliance', 'zoning' => 'Zoning, setbacks, site plan',
        'fire' => 'Fire, egress or accessibility', 'drafting' => 'Mostly drafting',
    ];

    public function __construct(private RequestService $requests)
    {
    }

    public function show()
    {
        $cfg = config('permit');

        return view('pages.permit-revisions', [
            'cfg' => collect($cfg)->only(['rate', 'currency', 'min_hours', 'base_hours', 'hours_per_comment', 'hours_per_affected_sheet', 'format_mult', 'area_add', 'area_cap',
                'range_low', 'range_high', 'auto_countries', 'manual_if_comments_over', 'manual_if_sheets_over', 'max_files', 'max_file_mb', 'allowed_extensions'])->all()
                + ['max_post_mb' => PermitFiles::maxPostMb(), 'endpoint' => route('permit-revisions.send'), 'email' => config('site.email')],
            'countries' => $cfg['countries'],
            'captchaKey' => config('services.recaptcha.site_key'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Honeypot: real visitors never fill this hidden field. Pretend success, store nothing.
        if ($request->filled('website')) {
            return response()->json(['ok' => true, 'message' => 'Thank you. We have your enquiry.']);
        }

        // PHP throws the whole request away when it is bigger than post_max_size: say so plainly instead of "fields missing"
        if (empty($_POST) && empty($_FILES) && (int) $request->server('CONTENT_LENGTH') > 0) {
            return response()->json(['ok' => false, 'message' => 'Your files are bigger than this server accepts in one go (about ' . PermitFiles::maxPostMb() . ' MB in total). Please remove or compress some, or zip them.'], 413);
        }

        $cfg = config('permit');
        $noLetter = $request->boolean('no_letter');
        $data = $request->validate([
            'need' => ['required', Rule::in(['fix_comments', 'design_change', 'new_set', 'unsure'])],
            'ptype' => ['required', Rule::in(['residential', 'adu', 'commercial', 'ti', 'other'])],
            'scope' => ['required', Rule::in(['new', 'addition', 'remodel', 'change_use'])],
            'country' => ['required', Rule::in($cfg['countries'])],
            'state' => ['nullable', 'string', 'max:80'],
            'authority' => ['required', 'string', 'max:120'],
            'sheets' => ['required', 'integer', 'min:1', 'max:500'],
            'comments' => [Rule::requiredIf(fn () => $request->input('need') !== 'new_set'), 'nullable', 'integer', 'min:0', 'max:500'],
            'areas' => ['nullable', 'array', 'max:6'],
            'areas.*' => ['string', Rule::in(array_keys($cfg['area_add']))],
            'format' => ['required', Rule::in(['native', 'pdf', 'scan'])],
            'seal' => ['required', Rule::in(['no', 'have', 'need'])],
            'deadline' => ['nullable', 'date', 'after_or_equal:-2 days'],        // a little slack: visitors in other time zones can be a day behind
            'details' => ['nullable', 'string', 'max:2000'],
            'no_letter' => ['nullable', 'boolean'],
            'rush' => ['nullable', 'boolean'],
            'nda' => ['nullable', 'boolean'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'consent' => ['accepted'],
            'attribution' => ['nullable', 'string', 'max:3000'],
            'files' => [$noLetter ? 'nullable' : 'required', 'array', 'max:' . $cfg['max_files']],
            'files.*' => ['file', function ($attr, $file, $fail) {
                if ($problem = PermitFiles::problem($file)) {
                    $fail($problem);
                }
            }],
            // without keys the check is skipped in local/testing only (see Recaptcha); elsewhere it is mandatory
            'g-recaptcha-response' => [
                (! config('services.recaptcha.secret_key') && app()->environment('local', 'testing')) ? 'nullable' : 'required',
                new Recaptcha,
            ],
        ], [
            'need.required' => 'Please choose what you need.',
            'consent.accepted' => 'Please tick the consent box so we can contact you.',
            'files.required' => 'Add at least one file, or tick the box if you only have the drawings later.',
            'files.max' => 'You can attach up to ' . $cfg['max_files'] . ' files. Zip extra files together.',
            'files.*.uploaded' => 'A file could not be uploaded. It may be bigger than the server allows.',
            'g-recaptcha-response.required' => 'Please tick “I’m not a robot” to continue.',
        ]);

        $files = array_values($request->file('files', []));
        $answers = [
            'need' => $data['need'], 'ptype' => $data['ptype'], 'scope' => $data['scope'], 'country' => $data['country'],
            'sheets' => $data['sheets'], 'comments' => $data['need'] === 'new_set' ? 0 : (int) ($data['comments'] ?? 0),
            'areas' => $data['areas'] ?? [], 'format' => $data['format'],
        ];
        $estimate = PermitEstimator::estimate($answers);                  // always worked out here: a number sent by the browser is never trusted
        $source = Attribution::lines($data['attribution'] ?? null);

        $enquiry = [
            'name' => $data['name'], 'email' => $data['email'], 'company' => $data['company'] ?? null, 'service' => 'permit-revisions',
            'deadline' => $data['deadline'] ?? null, 'nda' => $request->boolean('nda'),
            'message' => $this->summary($data, $answers, $estimate, $files, $noLetter),
        ];
        Log::info('Permit revisions enquiry received', ['name' => $data['name'], 'country' => $data['country'], 'files' => count($files), 'manual' => $estimate['manual']]);   // never the files or the notes

        $meta = ['ip' => $request->ip(), 'page' => pu('permit-revisions', [], [], true), 'at' => now()->format('j M Y, H:i T'), 'source' => $source];

        try {
            $customer = $this->requests->customerFor($data['name'], $data['email']);     // existing customers keep their account (also Gmail dot / +tag spellings)
            if (! $customer) {
                return response()->json(['ok' => false, 'errors' => ['email' => ['Please use a different e-mail address for this enquiry.']], 'message' => 'Please use a different e-mail address for this enquiry.'], 422);
            }
            $order = $this->requests->create($customer, $enquiry, $files, 'website');
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Sorry, we could not send your enquiry just now. Please email ' . config('site.email') . ' with your files, or try again in a moment.'], 500);
        }

        $this->requests->notifyAdmin($order, ['nda' => $enquiry['nda']], $meta);
        $this->requests->notifyCustomer($order);

        return response()->json([
            'ok' => true,
            'message' => 'Thank you! Your request ' . $order->number . ' is in. We will review your files and reply with a written quotation, usually within one working day.',
            'request' => $order->number,
            'portal_url' => route('customer.login', ['email' => $order->customer->email]),
        ]);
    }

    /** The readable summary the team sees (e-mail, portal request, first chat message). Built here from the validated answers only. */
    private function summary(array $d, array $a, array $est, array $files, bool $noLetter): string
    {
        $L = fn (?string $k) => self::LABELS[$k] ?? (string) $k;
        $lines = ['PERMIT REVISION ENQUIRY'];
        $lines[] = 'Need: ' . $L($d['need']);
        $lines[] = 'Project: ' . $L($d['ptype']) . ', ' . $L($d['scope']);
        $lines[] = 'Location: ' . implode(', ', array_filter([$d['authority'], $d['state'] ?? null, $d['country']]));
        $lines[] = 'Sheets: ' . $a['sheets'] . ($d['need'] !== 'new_set' ? '  |  City comments: ' . $a['comments'] : '');
        $lines[] = 'Areas touched: ' . ($a['areas'] ? implode(', ', array_map($L, $a['areas'])) : 'not specified');
        $lines[] = 'Original format: ' . $L($d['format']) . '  |  Stamped or sealed drawings: ' . $L($d['seal']);
        if (! empty($d['deadline'])) {
            $lines[] = 'Resubmission deadline: ' . $d['deadline'];
        }
        if (! empty($d['rush'])) {
            $lines[] = 'URGENT: the customer asked for a fast turnaround (rush). Confirm what is possible.';
        }
        if ($noLetter) {
            $lines[] = 'City comment letter: not available yet';
        }
        $lines[] = 'Files: ' . ($files ? implode(', ', array_map(fn ($f) => $this->cleanName($f->getClientOriginalName()), $files)) : 'none attached yet');
        if (! empty($d['details'])) {
            $lines[] = 'Notes: ' . trim($d['details']);
        }

        $rate = config('permit.currency') . config('permit.rate') . '/hour';
        if ($est['manual']) {
            $lines[] = 'Estimate: MANUAL QUOTE (' . implode(' ', $est['reasons']) . ')';
        } else {
            $c = config('permit.currency');
            $lines[] = "Estimate shown to the customer: about {$est['hours_low']} to {$est['hours_high']} hours at the introductory rate of {$rate}, i.e. {$c}" . number_format($est['price_low']) . " to {$c}" . number_format($est['price_high']) . '. Final quote, turnaround and any rush charge to be confirmed after review.';
        }

        return implode("\n", $lines);
    }

    private function cleanName(string $name): string
    {
        return mb_substr(preg_replace('/[\r\n\t]+/', ' ', $name), 0, 120);
    }
}
