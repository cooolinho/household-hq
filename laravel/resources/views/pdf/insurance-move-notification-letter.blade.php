<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            line-height: 1.5;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .mb-24 {
            margin-bottom: 24px;
        }

        .muted {
            color: #555;
        }
    </style>
</head>
<body>
<div class="mb-24">
    <strong>Absender</strong><br>
    {{ $user->name }}<br>
    {{ $newAddress['line1'] ?: '-' }}<br>
    @if(!empty($newAddress['line2']))
        {{ $newAddress['line2'] }}<br>
    @endif
    {{ trim(($newAddress['zip'] ?? '') . ' ' . ($newAddress['city'] ?? '')) ?: '-' }}
</div>

<div class="mb-24">
    <strong>Empfaenger</strong><br>
    {{ $insurance->company ?: $insurance->name }}<br>
    {{ $insurance->address_line_1 ?: '-' }}<br>
    @if(!empty($insurance->address_line_2))
        {{ $insurance->address_line_2 }}<br>
    @endif
    {{ trim(($insurance->address_zip ?: '') . ' ' . ($insurance->address_city ?: '')) ?: '-' }}
</div>

<div class="mb-16 muted">{{ $createdAt->format('d.m.Y') }}</div>
<h2 class="mb-16">{{ $subject }}</h2>

@foreach(explode("\n", (string) $body) as $line)
    <div>{{ $line !== '' ? $line : ' ' }}</div>
@endforeach

<div class="mb-24"></div>
<div class="muted">Versicherung: {{ $insurance->name }}</div>
<div class="muted">Versicherungsnummer: {{ $insurance->number ?: '-' }}</div>
<div class="muted">Alte Adresse: {{ trim(($oldAddress['line1'] ?? '') . ' ' . ($oldAddress['line2'] ?? '')) }}
    , {{ trim(($oldAddress['zip'] ?? '') . ' ' . ($oldAddress['city'] ?? '')) }}</div>
<div class="muted">Neue Adresse: {{ trim(($newAddress['line1'] ?? '') . ' ' . ($newAddress['line2'] ?? '')) }}
    , {{ trim(($newAddress['zip'] ?? '') . ' ' . ($newAddress['city'] ?? '')) }}</div>
</body>
</html>

