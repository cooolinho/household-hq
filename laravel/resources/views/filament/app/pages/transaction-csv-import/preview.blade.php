{{-- Schritt 2: Vorschau der analysierten CSV-Datei --}}
@php($analysis = $this->getAnalysis())

@if ($analysis)
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2">
            <x-filament::badge color="gray">{{ $analysis->columnCount }} Spalten</x-filament::badge>
            <x-filament::badge color="gray">{{ $analysis->recordCount }} Datensätze</x-filament::badge>
            @if ($analysis->emptyLineCount > 0)
                <x-filament::badge color="gray">{{ $analysis->emptyLineCount }} leere Zeilen (werden ignoriert)</x-filament::badge>
            @endif
            <x-filament::badge :color="$analysis->hasHeader ? 'success' : 'warning'">
                {{ $analysis->hasHeader ? 'mit Kopfzeile' : 'ohne Kopfzeile' }}
            </x-filament::badge>
        </div>

        @if ($analysis->irregularRecordCount > 0)
            <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
                {{ $analysis->irregularRecordCount }} Datensätze haben eine abweichende Spaltenanzahl
                (Zeilen: {{ implode(', ', $analysis->irregularLines) }}{{ $analysis->irregularRecordCount > count($analysis->irregularLines) ? ', …' : '' }}).
                Fehlende Spalten werden als leer behandelt.
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Zeile</th>
                        @foreach ($analysis->header as $index => $name)
                            <th class="whitespace-nowrap px-3 py-2 font-medium text-gray-700 dark:text-gray-200">
                                <span class="text-xs text-gray-400">{{ $index + 1 }}</span> {{ $name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($analysis->previewRecords as $record)
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $record->line }}</td>
                            @foreach ($analysis->header as $index => $name)
                                <td class="whitespace-nowrap px-3 py-2 text-gray-700 dark:text-gray-200">
                                    {{ \Illuminate\Support\Str::limit($record->value($index) ?? '', 60) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
