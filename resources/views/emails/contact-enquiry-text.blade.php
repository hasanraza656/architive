NEW WEBSITE ENQUIRY
===================

Name:       {{ $e['name'] }}
@if (! empty($e['company']))
Company:    {{ $e['company'] }}
@endif
Email:      {{ $e['email'] }}
@if ($audience)
I am a:     {{ $audience }}
@endif
@if ($service)
Needs help: {{ $service }}
@endif
@if (! empty($e['deadline']))
Deadline:   {{ \Illuminate\Support\Carbon::parse($e['deadline'])->format('j F Y') }}
@endif
@if (! empty($e['nda']))
NDA:        Requested (send a mutual NDA before files are shared)
@endif
@if (! empty($e['links']))

Links / files:
{{ $e['links'] }}
@endif

Project details:
{{ $e['message'] }}

--
Reply to this email to answer {{ $e['name'] }} directly (Reply-To is set to the sender).
Submitted {{ $meta['at'] ?? now()->format('j M Y, H:i') }}@if (! empty($meta['page'])) from {{ $meta['page'] }}@endif @if (! empty($meta['ip'])) | IP {{ $meta['ip'] }}@endif

Sent automatically by the {{ $siteName }} website.
