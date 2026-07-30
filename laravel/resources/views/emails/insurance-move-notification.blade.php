<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Adressaenderung</title>
</head>
<body>
<p>Hallo {{ $recipientName }},</p>
<p>fuer die Versicherung "{{ $insuranceName }}" wurde folgende Mitteilung erstellt:</p>

@foreach(explode("\n", (string) $body) as $line)
    <p style="margin: 0 0 8px 0;">{{ $line !== '' ? $line : ' ' }}</p>
@endforeach

<p style="margin-top: 16px;">Viele Gruesse</p>
</body>
</html>

