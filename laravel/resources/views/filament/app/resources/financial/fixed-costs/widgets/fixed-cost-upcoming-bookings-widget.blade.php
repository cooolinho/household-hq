<x-filament-widgets::widget>
    @php
        $fmt = fn(float $v): string => number_format($v, 2, ',', '.') . ' €';
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════
         BEVORSTEHENDE BUCHUNGEN (konfigurierbar, Standard 30 Tage)
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

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
