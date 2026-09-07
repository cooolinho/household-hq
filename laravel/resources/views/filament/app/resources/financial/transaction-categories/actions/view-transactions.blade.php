@php
    use App\Filament\App\Resources\Financial\Transactions\TransactionResource;
@endphp

<div class="max-h-[60vh] overflow-auto">
    @if ($transactions->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Mit dieser Kategorie sind keine Transaktionen verknüpft.
        </p>
    @else
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Verknüpfte Transaktionen</caption>
            <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-3 py-2" scope="col">Buchung</th>
                <th class="px-3 py-2" scope="col">Auftraggeber</th>
                <th class="px-3 py-2" scope="col">Verwendungszweck</th>
                <th class="px-3 py-2 text-right" scope="col">Betrag</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($transactions as $transaction)
                @php
                    $transactionViewUrl = TransactionResource::getUrl('view', ['record' => $transaction]);
                @endphp
                <tr class="text-gray-700 dark:text-gray-200">
                    <td class="whitespace-nowrap px-3 py-3">
                        {{ $transaction->date?->format('d.m.Y') ?? '-' }}
                    </td>
                    <td class="px-3 py-3">
                        {{ $transaction->payer ?: '-' }}
                    </td>
                    <td class="max-w-md px-3 py-3">
                        <a
                                href="{{ $transactionViewUrl }}"
                                class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $transaction->purpose ?: 'Transaktion anzeigen' }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-right font-medium">
                        {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                        {{ $transaction->amount_currency }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
