<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailTitle ?? 'OMG Computer' }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;color:#172033;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef2f7;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 36px rgba(15,23,42,.08);">
                    <tr>
                        <td style="padding:26px 36px;background:#12263f;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        <img src="{{ url('/assets/img/omg/logoex2w.png') }}" alt="OMG Computer" width="100" style="display:block;width:100px;height:auto;border:0;">
                                    </td>
                                    @if (! empty($emailKicker) || ! empty($emailReference))
                                        <td align="right" valign="middle" style="color:#ffffff;font-size:13px;line-height:20px;">
                                            @if (! empty($emailKicker))<strong>{{ $emailKicker }}</strong><br>@endif
                                            @if (! empty($emailReference))<span style="color:#b9c5d4;">{{ $emailReference }}</span>@endif
                                        </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @yield('email-content')
                    <tr>
                        <td style="padding:24px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            @yield('email-footer')
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:100%;">
                    <tr>
                        <td align="center" style="padding:20px 12px;color:#94a3b8;font-size:12px;line-height:18px;">
                            This email was sent by OMG Computer.<br>
                            Please do not share links or account-verification codes from this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
