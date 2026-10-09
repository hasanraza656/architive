<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Architive')</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f0;font-family:Arial,Helvetica,sans-serif;color:#141414;">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f0;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e6e6e0;">
                <tr>
                    <td style="background:#141414;padding:22px 28px;">
                        <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#FFD60A;font-weight:bold;">@yield('kicker', 'Architive')</div>
                        <div style="font-size:22px;line-height:1.3;color:#ffffff;margin-top:6px;">@yield('heading')</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px 28px 8px;font-size:15px;line-height:1.6;color:#2b2b28;">
                        @yield('content')
                    </td>
                </tr>
                @hasSection('button')
                <tr>
                    <td style="padding:6px 28px 26px;">
                        <a href="@yield('button_url')" style="display:inline-block;background:#FFD60A;color:#141414;text-decoration:none;font-weight:bold;font-size:13px;letter-spacing:1px;text-transform:uppercase;padding:14px 26px;border-radius:999px;">@yield('button')</a>
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding:18px 28px;background:#fafaf7;border-top:1px solid #e6e6e0;font-size:12px;line-height:1.6;color:#8a8a83;">
                        @yield('footnote')
                        <div>{{ config('site.address.corporate.value') }}<br>
                        <a href="mailto:{{ config('site.email') }}" style="color:#8a8a83;">{{ config('site.email') }}</a></div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
