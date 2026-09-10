<div class="space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Analysierter Zeitraum: {{ $record->analyzed_from->format('d.m.Y') }}
        &ndash; {{ $record->analyzed_to->format('d.m.Y') }}
        ({{ $record->sample_count }} Transaktionen)
    </p>

    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Datum</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tag im Monat</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Betrag</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($transactions as $transaction)
                <tr>
                    <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($transaction->date)->format('d.m.Y') }}</td>
                    <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($transaction->date)->day }}</td>
                    <td class="px-3 py-2 text-right {{ (float) $transaction->amount >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        {{ number_format((float) $transaction->amount, 2, ',', '.') }} €
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                        Keine Transaktionen im analysierten Zeitraum gefunden.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
