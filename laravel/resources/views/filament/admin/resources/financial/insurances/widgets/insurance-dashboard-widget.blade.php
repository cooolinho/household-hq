<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex items-center gap-2">
                <x-heroicon-o-shield-check class="h-5 w-5 text-primary-500"/>
                Versicherungsuebersicht
            </span>
        </x-slot>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Gesamt</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalInsurances }}</p>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-600/40 dark:bg-amber-950/30">
                <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Laufen bald ab</p>
                <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $expiringSoonCount }}</p>
                <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">in den naechsten {{ $lookaheadDays }}
                    Tagen</p>
            </div>

            <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-600/40 dark:bg-red-950/30">
                <p class="text-xs uppercase tracking-wide text-red-700 dark:text-red-300">Bereits abgelaufen</p>
                <p class="mt-1 text-2xl font-semibold text-red-700 dark:text-red-300">{{ $expiredCount }}</p>
                <p class="mt-1 text-xs text-red-700/80 dark:text-red-300/80">separate Pruefung empfohlen</p>
            </div>
        </div>

        @if($expiredCount > 0)
            <div class="mt-4 rounded-xl border-2 border-red-500 bg-red-50 p-4 dark:border-red-400 dark:bg-red-950/40">
                <div class="mb-2 flex items-start gap-2">
                    <x-heroicon-s-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400"/>
                    <div>
                        <p class="text-sm font-semibold text-red-700 dark:text-red-300">
                            Achtung: {{ $expiredCount }} Versicherung(en) sind bereits abgelaufen.
                        </p>
                        <p class="text-xs text-red-700/80 dark:text-red-300/80">
                            Bitte zeitnah pruefen, ob eine automatische Verlaengerung aktiv ist.
                        </p>
                    </div>
                </div>

                <ul class="divide-y divide-red-200/80 dark:divide-red-900/80">
                    @foreach($expiredInsurances as $insurance)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-red-800 dark:text-red-200">{{ $insurance['name'] }}</p>
                                <p class="text-xs text-red-700/80 dark:text-red-300/80">
                                    {{ $insurance['company'] !== '' ? $insurance['company'] : '-' }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-xs font-semibold text-red-700 dark:text-red-300">
                                    {{ $insurance['end_date']?->format('d.m.Y') }}
                                </p>
                                @if($insurance['view_url'])
                                    <a href="{{ $insurance['view_url'] }}"
                                       class="text-xs text-red-700 underline hover:text-red-900 dark:text-red-300 dark:hover:text-red-100">
                                        Oeffnen
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Bald ablaufende Versicherungen</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    bis {{ $lookaheadEnd->format('d.m.Y') }}
                </span>
            </div>

            @if($expiringSoonCount === 0)
                <p class="text-sm italic text-gray-500 dark:text-gray-400">
                    Keine Versicherungen laufen in den naechsten {{ $lookaheadDays }} Tagen ab.
                </p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($expiringSoonInsurances as $insurance)
                        @php
                            $isToday = $insurance['days_until_end'] === 0;
                            $isSoon = is_int($insurance['days_until_end']) && $insurance['days_until_end'] <= 7;
                        @endphp
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ $insurance['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $insurance['company'] !== '' ? $insurance['company'] : '-' }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-xs font-semibold {{ $isToday ? 'text-red-600 dark:text-red-400' : ($isSoon ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-300') }}">
                                    {{ $insurance['end_date']?->format('d.m.Y') }}
                                </p>
                                <p class="text-xs {{ $isToday ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    @if($isToday)
                                        Heute
                                    @elseif(is_int($insurance['days_until_end']))
                                        in {{ $insurance['days_until_end'] }} Tagen
                                    @endif
                                </p>
                                @if($insurance['view_url'])
                                    <a href="{{ $insurance['view_url'] }}"
                                       class="text-xs text-primary-600 underline hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        Oeffnen
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if($withoutEndDateCount > 0)
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ $withoutEndDateCount }} Versicherung(en) ohne Enddatum sind in der Ablaufpruefung nicht enthalten.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
