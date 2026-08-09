<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerung: {{ $fixedCostName }}</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#111827;">
<table style="width:100%; background:#f8fafc; padding:24px 0; border-collapse:collapse;">
    <tr>
        <td style="text-align:center;">
            <table style="max-width:640px; width:100%; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; margin:0 auto; border-collapse:collapse;">
                <tr>
                    <td style="padding:28px 32px 16px 32px; background:linear-gradient(135deg,#0f172a,#1e293b); color:#fff;">
                        <p style="margin:0 0 8px 0; font-size:12px; letter-spacing:.08em; text-transform:uppercase; opacity:.8;">
                            Fixkosten-Erinnerung</p>
                        <h1 style="margin:0; font-size:24px; line-height:1.3;">{{ $fixedCostName }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6;">Hallo {{ $recipientName }},</p>
                        <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#374151;">
                            für die Fixkosten <strong>{{ $fixedCostName }}</strong> steht am
                            <strong>{{ $dueDate }}</strong> eine Buchung an.
                            Diese Erinnerung wird <strong>{{ $leadTimeLabel }}</strong> vorher versendet.
                        </p>

                        <table style="width:100%; border-collapse:collapse; margin:0 0 24px 0;">
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280; width:40%;">
                                    Nächste Buchung
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold;">{{ $dueDate }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280;">
                                    Betrag
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold;">{{ number_format($fixedCostAmount, 2, ',', '.') . ' €' }}</td>
                            </tr>
                        </table>

                        @if(!empty($fixedCostUrl))
                            <p style="margin:0 0 28px 0;">
                                <a href="{{ $fixedCostUrl }}"
                                   style="display:inline-block; background:#2563eb; color:#fff; text-decoration:none; font-size:14px; font-weight:bold; padding:12px 18px; border-radius:10px;">Fixkosten
                                    öffnen</a>
                            </p>
                        @endif

                        <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.6;">
                            Du erhältst diese Nachricht, weil für diese Fixkosten eine aktive Erinnerung konfiguriert
                            wurde.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

