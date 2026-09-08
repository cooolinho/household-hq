<x-filament-widgets::widget>
    @php
        $weeklyBalanceNegative = $weeklyBalance < 0;
        $fmt = fn(float $v, bool $signed = false): string =>
            ($signed && $v > 0 ? '+' : '') .
            number_format($v, 2, ',', '.') . ' €';
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         WÖCHENTLICHE ÜBERSICHT (nächste 7 Tage)
    ═══════════════════════════════════════════════════════════════ --}}
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-calendar class="h-4 w-4 text-primary-500"/>
                Wöchentliche Übersicht
                <span class="ml-auto text-xs font-normal text-gray-400">(nächste 7 Tage)</span>
            </span>
        </x-slot>

        @if($weeklyAll->isEmpty())
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
</x-filament-widgets::widget>
