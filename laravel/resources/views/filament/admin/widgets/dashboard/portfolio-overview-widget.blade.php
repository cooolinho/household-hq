<x-filament-widgets::widget>
    <x-filament::section heading="Portfolio Uebersicht">
        <div class="grid grid-cols-2 gap-3">
            @foreach($items as $item)
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 space-y-2 text-sm">
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                <span class="text-gray-600 dark:text-gray-300">Transaktionen im Monat ({{ $currency }})</span>
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $alerts['transactionsThisMonthCount'] }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                <span class="text-gray-600 dark:text-gray-300">Nicht zugeordnete Transaktionen</span>
                <span class="font-semibold {{ $alerts['unmatchedTransactionsThisMonthCount'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $alerts['unmatchedTransactionsThisMonthCount'] }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                <span class="text-gray-600 dark:text-gray-300">Bevorstehende Ausgaben (30 Tage)</span>
                <span class="font-semibold text-red-600 dark:text-red-400">
                    {{ $alerts['upcomingExpensesCount'] }} / {{ number_format($alerts['upcomingExpensesTotal'], 2, ',', '.') }} {{ $currency }}
                </span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                <span class="text-gray-600 dark:text-gray-300">Ablaufende Versicherungen (60 Tage)</span>
                <span class="font-semibold {{ $alerts['expiringInsurancesCount'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $alerts['expiringInsurancesCount'] }}</span>
            </div>
        </div>

        @if($alerts['nonPreferredCurrencyTransactionsCount'] > 0)
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ $alerts['nonPreferredCurrencyTransactionsCount'] }} Transaktion(en) in anderer Waehrung wurden im
                Monatsvergleich ignoriert.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

