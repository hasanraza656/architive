<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New project enquiry</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f0;font-family:Arial,Helvetica,sans-serif;color:#141414;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f0;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e6e6e0;">
                <tr>
                    <td style="background:#141414;padding:22px 28px;">
                        <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#FFD60A;font-weight:bold;">New website enquiry</div>
                        <div style="font-size:22px;line-height:1.3;color:#ffffff;margin-top:6px;">{{ $e['name'] }}@if (! empty($e['company'])) <span style="color:#bdbdb5;">· {{ $e['company'] }}</span>@endif</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.55;">
                            @php
                                $rows = [
                                    'Email'    => '<a href="mailto:' . e($e['email']) . '" style="color:#141414;">' . e($e['email']) . '</a>',
                                    'I am a'   => $audience ? e($audience) : null,
                                    'Needs help with' => $service ? e($service) : null,
                                    'Target deadline' => ! empty($e['deadline']) ? e(\Illuminate\Support\Carbon::parse($e['deadline'])->format('j F Y')) : null,
                                    'Mutual NDA requested' => ! empty($e['nda']) ? 'Yes, please send an NDA before sharing files' : null,
                                ];
                            @endphp
                            @foreach ($rows as $label => $value)
                                @if ($value)
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #eeeeea;width:150px;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#8a8a83;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #eeeeea;vertical-align:top;">{!! $value !!}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @if (! empty($e['links']))
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #eeeeea;width:150px;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#8a8a83;vertical-align:top;">Links / files</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #eeeeea;vertical-align:top;white-space:pre-line;word-break:break-word;">{{ $e['links'] }}</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 6px;">
                        <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#8a8a83;margin-bottom:8px;">Project details</div>
                        <div style="font-size:15px;line-height:1.65;background:#fafaf7;border:1px solid #eeeeea;border-radius:10px;padding:16px 18px;white-space:pre-line;word-break:break-word;">{{ $e['message'] }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px 26px;">
                        <a href="mailto:{{ $e['email'] }}?subject={{ rawurlencode('Re: your enquiry to ' . $siteName) }}" style="display:inline-block;background:#FFD60A;color:#141414;text-decoration:none;font-weight:bold;font-size:13px;letter-spacing:1px;text-transform:uppercase;padding:13px 22px;border-radius:999px;">Reply to {{ $e['name'] }}</a>
                        <div style="font-size:12px;color:#8a8a83;margin-top:14px;">You can also simply press Reply: this email's Reply-To is set to the sender.</div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#fafaf7;border-top:1px solid #eeeeea;padding:14px 28px;font-size:11px;line-height:1.6;color:#8a8a83;">
                        Submitted {{ $meta['at'] ?? now()->format('j M Y, H:i') }} @if (! empty($meta['page'])) from {{ $meta['page'] }}@endif @if (! empty($meta['ip'])) · IP {{ $meta['ip'] }}@endif<br>
                        Passed the reCAPTCHA security check. Sent automatically by {{ $siteName }} website.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
