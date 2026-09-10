<x-filament-widgets::widget>
    @php
        $fmt = fn(float $v): string => number_format($v, 2, ',', '.') . ' €';
        $isCovered = $requirement->isCovered();
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Liquiditätsbedarf</h2>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            <x-filament::input.checkbox wire:model.live="includeBudgets"/>
            Budgets einrechnen
        </label>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Kennzahl: benötigter Betrag vs. Kontostand --}}
        <x-filament::section
                :heading="'Bis ' . $requirement->periodEnd->format('d.m.Y')"
                :class="$isCovered
                    ? 'ring-2 ring-green-500 dark:ring-green-400'
                    : 'ring-2 ring-red-500 dark:ring-red-400'"
                class="lg:col-span-1"
        >
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-banknotes class="h-4 w-4 text-red-500"/>
                        Noch benötigt
                    </span>
                    <span class="font-semibold text-red-600 dark:text-red-400">
                        −{{ $fmt($requirement->totalRequired()) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-building-library class="h-4 w-4 text-gray-400"/>
                        Kontostand
                    </span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ $fmt($requirement->bankBalance) }}
                    </span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700"/>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ $isCovered ? 'Puffer' : 'Fehlbetrag' }}
                    </span>
                    <span @class(['text-lg font-bold',
                        'text-green-600 dark:text-green-400' => $isCovered,
                        'text-red-600 dark:text-red-400'     => !$isCovered])>
                        {{ $isCovered ? $fmt($requirement->bankBalance - $requirement->totalRequired()) : '−' . $fmt($requirement->shortfall()) }}
                    </span>
                </div>

                @if($requirement->bankBalanceAsOf !== null && $requirement->bankBalanceAsOf->lessThan($requirement->asOf->subDays(3)))
                    <p class="flex items-start gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-exclamation-triangle class="mt-0.5 h-3.5 w-3.5 shrink-0"/>
                        Kontostand vom {{ $requirement->bankBalanceAsOf->format('d.m.Y') }} – ggf. veraltet.
                    </p>
                @endif
            </div>
        </x-filament::section>

        {{-- Zusatzinfos: erwartete Einnahmen & Budgets --}}
        <x-filament::section heading="Übersicht" class="lg:col-span-2">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Erwartete Einnahmen</p>
                    <p class="text-base font-semibold text-green-600 dark:text-green-400">
                        +{{ $fmt($requirement->expectedIncome) }}
                    </p>
                </div>
                @if($requirement->budgetsIncluded && $requirement->budgetShare > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Budget-Anteil (anteilig)</p>
                        <p class="text-base font-semibold text-amber-600 dark:text-amber-400">
                            −{{ $fmt($requirement->budgetShare) }}
                        </p>
                    </div>
                @endif
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ausstehende Buchungen</p>
                    <p class="text-base font-semibold text-gray-800 dark:text-gray-200">
                        {{ count($requirement->bookings) }}
                    </p>
                </div>
            </div>

            @if(!empty($requirement->bookings))
                <ul class="mt-4 max-h-64 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
                    @foreach($requirement->bookings as $booking)
                        <li class="flex items-center justify-between py-2 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-heroicon-o-calendar-days class="h-4 w-4 shrink-0 text-gray-400"/>
                                <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $booking->fixedCost->name }}
                                </span>
                            </div>
                            <div class="flex shrink-0 flex-col items-end">
                                <span @class(['text-sm font-semibold',
                                    'text-green-600 dark:text-green-400' => $booking->isIncome(),
                                    'text-red-600 dark:text-red-400'     => !$booking->isIncome()])>
                                    {{ $booking->isIncome() ? '+' : '−' }}{{ $fmt(abs($booking->amount)) }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $booking->date->format('d.m.Y') }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-gray-400 dark:text-gray-500 italic">
                    Keine ausstehenden Fixkosten-Buchungen bis zum Periodenende.
                </p>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
