<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation {{ $document->number }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;color:#172033;-webkit-text-size-adjust:100%;">
@php
    $acceptUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'accepted']);
    $rejectUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'rejected']);
    $issueDate = $document->issue_date ? \Illuminate\Support\Carbon::parse($document->issue_date)->format('d M Y') : now()->format('d M Y');
@endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#eef2f7;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 36px rgba(15,23,42,.08);">
                    <tr>
                        <td style="padding:28px 36px;background:#12263f;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        <img src="{{ url('/assets/img/omg/logoex2w.png') }}" alt="OMG Computer" width="100" style="display:block;width:100px;height:auto;border:0;">
                                    </td>
                                    <td align="right" valign="middle" style="color:#ffffff;font-size:13px;line-height:20px;">
                                        <strong style="font-size:16px;">QUOTATION</strong><br>
                                        <span style="color:#b9c5d4;">{{ $document->number }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 36px 28px;">
                            <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello,</p>
                            <h1 style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Your quotation is ready</h1>
                            <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">
                                Hi {{ $document->customer_name }}, we've prepared the quotation for your review. You can respond securely below or review the attached PDF.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 36px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                                <tr>
                                    <td width="50%" style="padding:18px;border-right:1px solid #e2e8f0;">
                                        <div style="font-size:11px;line-height:16px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;font-weight:bold;">Quotation number</div>
                                        <div style="margin-top:5px;font-size:15px;color:#12263f;font-weight:bold;">{{ $document->number }}</div>
                                    </td>
                                    <td width="50%" style="padding:18px;">
                                        <div style="font-size:11px;line-height:16px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;font-weight:bold;">Issued on</div>
                                        <div style="margin-top:5px;font-size:15px;color:#12263f;font-weight:bold;">{{ $issueDate }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#ffffff;border-radius:0 0 12px 12px;">
                                        <div style="font-size:11px;line-height:16px;color:#64748b;text-transform:uppercase;letter-spacing:.08em;font-weight:bold;">Quotation total</div>
                                        <div style="margin-top:3px;font-size:30px;line-height:38px;color:#12263f;font-weight:bold;">RM {{ number_format((float) $document->total, 2) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 36px 34px;">
                            <div style="margin-bottom:14px;font-size:16px;font-weight:bold;color:#12263f;">Would you like to proceed?</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="50%" style="padding-right:6px;">
                                        <a href="{{ $acceptUrl }}" style="display:block;padding:14px 10px;background:#16805b;color:#ffffff;text-align:center;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;">✓&nbsp; Accept quotation</a>
                                    </td>
                                    <td width="50%" style="padding-left:6px;">
                                        <a href="{{ $rejectUrl }}" style="display:block;padding:14px 10px;background:#ffffff;color:#b42318;text-align:center;text-decoration:none;border:1px solid #d92d20;border-radius:8px;font-size:15px;font-weight:bold;">×&nbsp; Reject quotation</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">The full details, terms, and item breakdown are included in the attached PDF.</p>
                            <p style="margin:0;font-size:14px;line-height:22px;color:#475569;">Questions? Simply reply to this email and our team will assist you.</p>
                            <p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">Sales &amp; Customer Support</span></p>
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:100%;">
                    <tr>
                        <td align="center" style="padding:20px 12px;color:#94a3b8;font-size:12px;line-height:18px;">
                            This email was sent by OMG Computer regarding quotation {{ $document->number }}.<br>
                            Please do not share the secure Accept or Reject links.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
