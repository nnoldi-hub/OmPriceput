<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raspuns client la deviz</title>
</head>
<body style="margin:0;background:#eef2f7;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 12px;background:#eef2f7;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#061426;padding:28px 24px;text-align:center;">
                            <img src="{{ $logoUrl }}" alt="Omul Potrivit" width="250" style="display:block;width:250px;max-width:100%;height:auto;margin:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 32px;">
                            <p style="font-size:20px;font-weight:700;">Buna, {{ $recipientName }}!</p>
                            <p style="font-size:16px;line-height:1.6;color:#4b5563;">
                                Clientul a {{ $status === 'accepted' ? 'acceptat' : 'respins' }} devizul
                                „{{ $offer->title }}”.
                            </p>
                            @if ($clientMessage)
                                <div style="margin:24px 0;padding:18px;background:#f8fafc;border-left:4px solid #f59e0b;">
                                    <p style="margin:0 0 8px;font-weight:700;">Mesaj client</p>
                                    <p style="margin:0;color:#4b5563;white-space:pre-line;">{{ $clientMessage }}</p>
                                </div>
                            @endif
                            <p style="margin:28px 0;text-align:center;">
                                <a href="{{ $offerUrl }}" style="display:inline-block;padding:13px 22px;background:#f59e0b;color:#172033;text-decoration:none;border-radius:6px;font-weight:700;">
                                    Vezi devizul
                                </a>
                            </p>
                            <p style="margin-top:28px;border-top:1px solid #e5e7eb;padding-top:22px;color:#4b5563;">
                                Cu stima,<br><strong style="color:#172033;">Omul Potrivit</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
