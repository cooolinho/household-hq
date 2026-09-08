<x-filament-widgets::widget>
    {{-- ═══════════════════════════════════════════════════════════════
         BILANZKARTE – negative Bilanz = auffällige Warnung
    ═══════════════════════════════════════════════════════════════ --}}
    @php
        $balanceNegativeMonthly = $balance->monthlyBalance() < 0;
        $balanceNegativeYearly  = $balance->yearlyBalance()  < 0;
        $isNegative             = $balanceNegativeMonthly || $balanceNegativeYearly;

        $fmt = fn(float $v, bool $signed = false): string =>
            ($signed && $v > 0 ? '+' : '') .
            number_format($v, 2, ',', '.') . ' €';
    @endphp

    {{-- ─── WARNUNG wenn Bilanz negativ ─────────────────────────── --}}
    @if($isNegative)
        <div class="mb-4 flex items-start gap-3 rounded-xl border-2 border-red-500 bg-red-50 p-4 dark:bg-red-950/40 dark:border-red-400">
            <x-heroicon-s-exclamation-triangle class="mt-0.5 h-6 w-6 shrink-0 text-red-600 dark:text-red-400"/>
            <div>
                <p class="font-bold text-red-700 dark:text-red-300 text-sm">
                    ⚠️ Achtung: Deine Ausgaben übersteigen deine Einnahmen!
                </p>
                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">
                    Überprüfe deine Fixkosten{{ $balance->budgetsIncluded ? ' und Budgets' : '' }} – die monatliche oder jährliche Bilanz ist negativ.
                </p>
            </div>
        </div>
    @endif

    @if($balance->hasInvalidFixedCosts())
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-400 bg-amber-50 p-3 dark:bg-amber-950/30 dark:border-amber-500">
            <x-heroicon-s-exclamation-circle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"/>
            <p class="text-xs text-amber-700 dark:text-amber-400">
                {{ count($balance->invalidFixedCosts) }} Fixkosten haben ein unvollständig konfiguriertes, benutzerdefiniertes Intervall und wurden bei der Bilanz nicht berücksichtigt.
            </p>
        </div>
    @endif

    {{-- ─── KOPFZEILE: Titel + Schalter ─────────────────────────── --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</h2>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
            <x-filament::input.checkbox wire:model.live="includeBudgets"/>
            Budgets einrechnen
        </label>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MONATLICHE / JÄHRLICHE BILANZ
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Monatlich --}}
        <x-filament::section
                :heading="'Monatliche Bilanz'"
                :class="$balanceNegativeMonthly
                ? 'ring-2 ring-red-500 dark:ring-red-400'
                : 'ring-2 ring-green-500 dark:ring-green-400'"
        >
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-green-500"/>
                        Einnahmen
                    </span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $fmt($balance->monthlyIncome) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-red-500"/>
                        Fixkosten-Ausgaben
                    </span>
                    <span class="font-semibold text-red-600 dark:text-red-400">−{{ $fmt($balance->monthlyFixedCostExpenses) }}</span>
                </div>
                @if($balance->budgetsIncluded && $balance->budgetCount > 0)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-wallet class="h-4 w-4 text-amber-500"/>
                            Budgets ({{ $balance->budgetCount }})
                        </span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400">−{{ $fmt($balance->monthlyBudgetExpenses) }}</span>
                    </div>
                @endif
                <hr class="border-gray-200 dark:border-gray-700"/>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bilanz</span>
                    <span @class(['text-lg font-bold',
                        'text-green-600 dark:text-green-400' => !$balanceNegativeMonthly,
                        'text-red-600 dark:text-red-400'     => $balanceNegativeMonthly])>
                        {{ $fmt($balance->monthlyBalance(), true) }}
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- Jährlich --}}
        <x-filament::section
                :heading="'Jährliche Bilanz'"
                :class="$balanceNegativeYearly
                ? 'ring-2 ring-red-500 dark:ring-red-400'
                : 'ring-2 ring-green-500 dark:ring-green-400'"
        >
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-green-500"/>
                        Einnahmen
                    </span>
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $fmt($balance->yearlyIncome) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-red-500"/>
                        Fixkosten-Ausgaben
                    </span>
                    <span class="font-semibold text-red-600 dark:text-red-400">−{{ $fmt($balance->yearlyFixedCostExpenses) }}</span>
                </div>
                @if($balance->budgetsIncluded && $balance->budgetCount > 0)
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-wallet class="h-4 w-4 text-amber-500"/>
                            Budgets ({{ $balance->budgetCount }})
                        </span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400">−{{ $fmt($balance->yearlyBudgetExpenses) }}</span>
                    </div>
                @endif
                <hr class="border-gray-200 dark:border-gray-700"/>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bilanz</span>
                    <span @class(['text-lg font-bold',
                        'text-green-600 dark:text-green-400' => !$balanceNegativeYearly,
                        'text-red-600 dark:text-red-400'     => $balanceNegativeYearly])>
                        {{ $fmt($balance->yearlyBalance(), true) }}
                    </span>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
