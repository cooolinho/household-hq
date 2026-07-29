<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upcoming fixed costs</title>
</head>
<body>
<p>Hello {{ $recipientName }},</p>
<p>Here are your upcoming outgoing fixed costs for the next day, week, and month.</p>

@php
    $labels = [
        'day' => 'Next day',
        'week' => 'Next week',
        'month' => 'Next month',
    ];
@endphp

@foreach (['day', 'week', 'month'] as $key)
    <h3>{{ $labels[$key] }} ({{ $ranges[$key][0]->format('Y-m-d') }} to {{ $ranges[$key][1]->format('Y-m-d') }})</h3>

    @if ($windows[$key]->isEmpty())
        <p>No upcoming outgoing fixed costs.</p>
    @else
        <table style="border-collapse: collapse;">
            <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: left;">Due date</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: left;">Name</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: right;">Amount (EUR)</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($windows[$key] as $fixedCost)
                <tr>
                    <td style="border: 1px solid #ccc; padding: 6px;">{{ $fixedCost->next_booking_date?->format('Y-m-d') }}</td>
                    <td style="border: 1px solid #ccc; padding: 6px;">{{ $fixedCost->name }}</td>
                    <td style="border: 1px solid #ccc; padding: 6px; text-align: right;">{{ number_format((float) $fixedCost->amount, 2, '.', ',') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <p>
            Total: {{ number_format((float) $windows[$key]->sum('amount'), 2, '.', ',') }} EUR
        </p>
    @endif
@endforeach

<p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

