<x-filament-widgets::widget>
    {{-- ═══════════════════════════════════════════════════════════════
         BILANZKARTE – negative Bilanz = auffällige Warnung
    ═══════════════════════════════════════════════════════════════ --}}
    @php
        $balanceNegativeMonthly = $monthlyBalance < 0;
        $balanceNegativeYearly  = $yearlyBalance  < 0;
        $weeklyBalanceNegative  = $weeklyBalance  < 0;
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
                    Überprüfe deine Fixkosten – die monatliche oder jährliche Bilanz ist negativ.
                </p>
            </div>
        </div>
    @endif

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
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $fmt($monthlyIncome) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-red-500"/>
                        Ausgaben
                    </span>
                    <span class="font-semibold text-red-600 dark:text-red-400">−{{ $fmt($monthlyExpenses) }}</span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700"/>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bilanz</span>
                    <span @class(['text-lg font-bold',
                        'text-green-600 dark:text-green-400' => !$balanceNegativeMonthly,
                        'text-red-600 dark:text-red-400'     => $balanceNegativeMonthly])>
                        {{ $fmt($monthlyBalance, true) }}
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
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $fmt($yearlyIncome) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-red-500"/>
                        Ausgaben
                    </span>
                    <span class="font-semibold text-red-600 dark:text-red-400">−{{ $fmt($yearlyExpenses) }}</span>
                </div>
                <hr class="border-gray-200 dark:border-gray-700"/>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Bilanz</span>
                    <span @class(['text-lg font-bold',
                        'text-green-600 dark:text-green-400' => !$balanceNegativeYearly,
                        'text-red-600 dark:text-red-400'     => $balanceNegativeYearly])>
                        {{ $fmt($yearlyBalance, true) }}
                    </span>
                </div>
            </div>
        </x-filament::section>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         WÖCHENTLICHE ÜBERSICHT (nächste 7 Tage)
    ═══════════════════════════════════════════════════════════════ --}}
    <x-filament::section class="mt-4">
        <x-slot name="heading">
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-calendar class="h-4 w-4 text-primary-500"/>
                Wöchentliche Übersicht
                <span class="ml-auto text-xs font-normal text-gray-400">(nächste 7 Tage)</span>
            </span>
        </x-slot>

        @if($weeklyAll->isEmpty() ?? ($weeklyIncome->isEmpty() && $weeklyExpenses->isEmpty()))
            <p class="text-sm text-gray-400 dark:text-gray-500 italic">Keine Buchungen in den nächsten 7 Tagen.</p>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Ausgaben diese Woche --}}
                <div>
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-red-500">
                        <x-heroicon-o-arrow-up-circle class="h-3.5 w-3.5"/>
                        Ausgaben
                    </p>
                    @if($weeklyExpenses->isEmpty())
                        <p class="text-sm text-gray-400 italic">Keine Ausgaben diese Woche.</p>
                    @else
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($weeklyExpenses as $fc)
                                @php
                                    $daysUntil = $today->diffInDays($fc->next_booking_date, false);
                                    $isToday   = $daysUntil === 0;
                                @endphp
                                <li class="flex items-center justify-between py-1.5 gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        @if($isToday)
                                            <x-heroicon-s-bell-alert
                                                    class="h-3.5 w-3.5 shrink-0 text-red-500 animate-pulse"/>
                                        @else
                                            <x-heroicon-o-calendar-days class="h-3.5 w-3.5 shrink-0 text-gray-400"/>
                                        @endif
                                        <span class="truncate text-sm text-gray-700 dark:text-gray-300">{{ $fc->name }}</span>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end">
                                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">−{{ $fmt(abs($fc->amount)) }}</span>
                                        <span class="text-xs {{ $isToday ? 'font-bold text-red-500' : 'text-gray-400' }}">
                                            {{ $isToday ? 'Heute' : $fc->next_booking_date->format('d.m.') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Gesamt</span>
                            <span class="font-bold text-red-600 dark:text-red-400">−{{ $fmt($weeklyExpensesTotal) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Einnahmen diese Woche --}}
                <div>
                    <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-green-500">
                        <x-heroicon-o-arrow-down-circle class="h-3.5 w-3.5"/>
                        Einnahmen
                    </p>
                    @if($weeklyIncome->isEmpty())
                        <p class="text-sm text-gray-400 italic">Keine Einnahmen diese Woche.</p>
                    @else
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($weeklyIncome as $fc)
                                @php
                                    $daysUntil = $today->diffInDays($fc->next_booking_date, false);
                                    $isToday   = $daysUntil === 0;
                                @endphp
                                <li class="flex items-center justify-between py-1.5 gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        @if($isToday)
                                            <x-heroicon-s-bell-alert
                                                    class="h-3.5 w-3.5 shrink-0 text-green-500 animate-pulse"/>
                                        @else
                                            <x-heroicon-o-calendar-days class="h-3.5 w-3.5 shrink-0 text-gray-400"/>
                                        @endif
                                        <span class="truncate text-sm text-gray-700 dark:text-gray-300">{{ $fc->name }}</span>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end">
                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">+{{ $fmt($fc->amount) }}</span>
                                        <span class="text-xs {{ $isToday ? 'font-bold text-green-500' : 'text-gray-400' }}">
                                            {{ $isToday ? 'Heute' : $fc->next_booking_date->format('d.m.') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Gesamt</span>
                            <span class="font-bold text-green-600 dark:text-green-400">+{{ $fmt($weeklyIncomeTotal) }}</span>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Wochenbilanz --}}
            <div class="mt-3 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Wochenbilanz</span>
                <span @class(['font-bold text-base',
                    'text-green-600 dark:text-green-400' => !$weeklyBalanceNegative,
                    'text-red-600 dark:text-red-400'     => $weeklyBalanceNegative])>
                    {{ $fmt($weeklyBalance, true) }}
                </span>
            </div>
        @endif
    </x-filament::section>

    {{-- ═══════════════════════════════════════════════════════════════
         BEVORSTEHENDE BUCHUNGEN (konfigurierbar, Standard 30 Tage)
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Bevorstehende AUSGABEN --}}
        <x-filament::section>
            <x-slot name="heading">
                <span class="flex items-center gap-1.5">
                    <x-heroicon-o-arrow-up-circle class="h-4 w-4 text-red-500"/>
                    Bevorstehende Ausgaben
                    <span class="ml-auto text-xs font-normal text-gray-400">(nächste {{ $lookaheadDays }} Tage)</span>
                </span>
            </x-slot>

            @if($upcomingExpenses->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Keine bevorstehenden Ausgaben.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($upcomingExpenses as $fc)
                        @php
                            $daysUntil = $today->diffInDays($fc->next_booking_date, false);
                            $isToday   = $daysUntil === 0;
                            $isSoon    = $daysUntil <= 3;
                        @endphp
                        <li class="flex items-center justify-between py-2 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($isToday)
                                    <x-heroicon-s-bell-alert class="h-4 w-4 shrink-0 text-red-500 animate-pulse"/>
                                @elseif($isSoon)
                                    <x-heroicon-s-bell class="h-4 w-4 shrink-0 text-orange-400"/>
                                @else
                                    <x-heroicon-o-calendar-days class="h-4 w-4 shrink-0 text-gray-400"/>
                                @endif
                                <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ $fc->name }}</span>
                            </div>
                            <div class="flex shrink-0 flex-col items-end">
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">−{{ $fmt(abs($fc->amount)) }}</span>
                                <span @class(['text-xs',
                                    'font-bold text-red-600 dark:text-red-400' => $isToday,
                                    'text-orange-500 dark:text-orange-400'     => !$isToday && $isSoon,
                                    'text-gray-400 dark:text-gray-500'         => !$isToday && !$isSoon])>
                                    {{ $isToday ? 'Heute' : $fc->next_booking_date->format('d.m.Y') }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-2 flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Gesamt</span>
                    <span class="text-base font-bold text-red-600 dark:text-red-400">−{{ $fmt($upcomingExpensesTotal) }}</span>
                </div>
            @endif
        </x-filament::section>

        {{-- Bevorstehende EINNAHMEN --}}
        <x-filament::section>
            <x-slot name="heading">
                <span class="flex items-center gap-1.5">
                    <x-heroicon-o-arrow-down-circle class="h-4 w-4 text-green-500"/>
                    Bevorstehende Einnahmen
                    <span class="ml-auto text-xs font-normal text-gray-400">(nächste {{ $lookaheadDays }} Tage)</span>
                </span>
            </x-slot>

            @if($upcomingIncome->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Keine bevorstehenden Einnahmen.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($upcomingIncome as $fc)
                        @php
                            $daysUntil = $today->diffInDays($fc->next_booking_date, false);
                            $isToday   = $daysUntil === 0;
                            $isSoon    = $daysUntil <= 3;
                        @endphp
                        <li class="flex items-center justify-between py-2 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($isToday)
                                    <x-heroicon-s-bell-alert class="h-4 w-4 shrink-0 text-green-500 animate-pulse"/>
                                @elseif($isSoon)
                                    <x-heroicon-s-bell class="h-4 w-4 shrink-0 text-green-400"/>
                                @else
                                    <x-heroicon-o-calendar-days class="h-4 w-4 shrink-0 text-gray-400"/>
                                @endif
                                <span class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ $fc->name }}</span>
                            </div>
                            <div class="flex shrink-0 flex-col items-end">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">+{{ $fmt($fc->amount) }}</span>
                                <span @class(['text-xs',
                                    'font-bold text-green-600 dark:text-green-400' => $isToday,
                                    'text-green-500 dark:text-green-500'            => !$isToday && $isSoon,
                                    'text-gray-400 dark:text-gray-500'              => !$isToday && !$isSoon])>
                                    {{ $isToday ? 'Heute' : $fc->next_booking_date->format('d.m.Y') }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-2 flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Gesamt</span>
                    <span class="text-base font-bold text-green-600 dark:text-green-400">+{{ $fmt($upcomingIncomeTotal) }}</span>
                </div>
            @endif
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
