<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget: {{ $budgetName }}</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#111827;">
@php
    $accent = $percentage >= 100 ? '#dc2626' : '#d97706';
    $progress = min(100, max(0, (int) round($percentage)));
    $money = static fn (float $value): string => number_format($value, 2, ',', '.') . ' ' . $currency;
@endphp
<table style="width:100%; background:#f8fafc; padding:24px 0; border-collapse:collapse;">
    <tr>
        <td style="text-align:center;">
            <table style="max-width:640px; width:100%; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; margin:0 auto; border-collapse:collapse;">
                <tr>
                    <td style="padding:28px 32px 16px 32px; background:linear-gradient(135deg,#0f172a,#1e293b); color:#fff;">
                        <p style="margin:0 0 8px 0; font-size:12px; letter-spacing:.08em; text-transform:uppercase; opacity:.8;">
                            Budget-Benachrichtigung &middot; {{ $periodLabel }}</p>
                        <h1 style="margin:0; font-size:24px; line-height:1.3;">{{ $budgetName }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6;">Hallo {{ $recipientName }},</p>
                        <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#374151;">
                            das Budget <strong>{{ $budgetName }}</strong> hat im Zeitraum
                            <strong>{{ $periodLabel }}</strong> den Status
                            <strong style="color:{{ $accent }};">{{ $statusLabel }}</strong> erreicht &ndash;
                            <strong>{{ number_format($percentage, 1, ',', '.') }} %</strong> des Limits sind verbraucht.
                        </p>

                        <table style="width:100%; border-collapse:collapse; margin:0 0 8px 0;">
                            <tr>
                                <td style="background:#e5e7eb; border-radius:999px; height:10px; padding:0;">
                                    <table style="width:{{ $progress }}%; border-collapse:collapse;">
                                        <tr>
                                            <td style="background:{{ $accent }}; border-radius:999px; height:10px; font-size:0; line-height:0;">
                                                &nbsp;
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table style="width:100%; border-collapse:collapse; margin:0 0 24px 0;">
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280; width:50%;">
                                    Limit
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold;">{{ $money($limit) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280;">
                                    Verbraucht
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold;">{{ $money($spent) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280;">
                                    {{ $remaining < 0 ? 'Überschreitung' : 'Verbleibend' }}
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:{{ $remaining < 0 ? '#dc2626' : '#111827' }}; font-weight:bold;">{{ $money(abs($remaining)) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280;">
                                    Hochrechnung Periodenende
                                </td>
                                <td style="padding:12px 0; border-top:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold;">{{ $money($projected) }}</td>
                            </tr>
                        </table>

                        @if(!empty($budgetUrl))
                            <p style="margin:0 0 28px 0;">
                                <a href="{{ $budgetUrl }}"
                                   style="display:inline-block; background:#2563eb; color:#fff; text-decoration:none; font-size:14px; font-weight:bold; padding:12px 18px; border-radius:10px;">Budget
                                    öffnen</a>
                            </p>
                        @endif

                        <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.6;">
                            Du erhältst diese Nachricht, weil für dieses Budget der E-Mail-Versand aktiviert ist.
                            Pro Zeitraum wird je Schwellwert nur einmal benachrichtigt.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
