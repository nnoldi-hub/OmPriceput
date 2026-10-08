<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmare cerere</title>
</head>
<body style="margin:0;background:#eef2f7;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f7;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#061426;padding:28px 24px;text-align:center;">
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" width="150" style="display:block;width:150px;max-width:100%;height:auto;margin:0 auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 32px 28px;">
                            <p style="margin:0 0 18px;font-size:20px;font-weight:700;color:#111827;">
                                Bună, {{ $recipientName }}!
                            </p>
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.6;color:#4b5563;">
                                Îți confirmăm că am primit mesajul tău. Un coleg din echipa noastră te va contacta în cel mai scurt timp pentru a stabili detaliile.
                            </p>

                            @if($visit?->scheduled_at)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;background:#f8fafc;border-left:4px solid #f59e0b;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">Programare solicitată</p>
                                        <p style="margin:0;font-size:16px;font-weight:600;color:#172033;">{{ $visit->scheduled_at->format('d.m.Y H:i') }}</p>
                                        @if($services !== [])
                                        <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#4b5563;">Servicii: {{ implode(', ', $services) }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            @elseif($services !== [])
                            <p style="margin:22px 0;font-size:14px;line-height:1.6;color:#4b5563;">
                                Servicii solicitate: <strong>{{ implode(', ', $services) }}</strong>
                            </p>
                            @endif

                            @if($phone)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;background:#f8fafc;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0;font-size:14px;line-height:1.6;color:#4b5563;">
                                            Ai întrebări? Sună-ne la <strong style="color:#172033;">{{ $phone }}</strong>@if($hours) &mdash; {{ $hours }}@endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin:28px 0 0;padding-top:22px;border-top:1px solid #e5e7eb;font-size:14px;line-height:1.6;color:#4b5563;">
                                Cu stima,<br>
                                <strong style="color:#172033;">{{ $companyName }}</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;background:#f8fafc;text-align:center;font-size:12px;line-height:1.5;color:#64748b;">
                            Acest mesaj a fost trimis automat. Te rugam sa nu raspunzi direct la acest email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
