{{-- Schritt 4: Prüfung vor dem Import --}}
@php
    $report = $this->getReview();
    $mapped = $this->getMappedColumns();
    $ignored = $this->getIgnoredColumns();
    $skipInvalid = (bool) ($this->data['skip_invalid_rows'] ?? false);
@endphp

@if ($report)
    <div class="space-y-4 text-sm">
        @if ($report->mappingErrors !== [])
            <div class="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                <p class="font-medium">Import kann nicht gestartet werden.</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($report->mappingErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <div class="text-xs text-gray-500">Datensätze</div>
                    <div class="text-lg font-semibold">{{ $report->records }}</div>
                </div>
                <div class="rounded-lg border border-success-200 p-3 dark:border-success-500/30">
                    <div class="text-xs text-gray-500">Werden importiert</div>
                    <div class="text-lg font-semibold text-success-600">{{ $report->valid }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <div class="text-xs text-gray-500">Duplikate (werden übersprungen)</div>
                    <div class="text-lg font-semibold">{{ $report->duplicates() }}</div>
                </div>
                <div class="rounded-lg border p-3 {{ $report->invalid > 0 ? 'border-danger-200 dark:border-danger-500/30' : 'border-gray-200 dark:border-white/10' }}">
                    <div class="text-xs text-gray-500">Fehlerhaft</div>
                    <div class="text-lg font-semibold {{ $report->invalid > 0 ? 'text-danger-600' : '' }}">{{ $report->invalid }}</div>
                </div>
            </div>

            @if ($report->invalid > 0)
                <div class="space-y-2 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                    @unless ($skipInvalid)
                        <p class="font-medium">Import kann nicht gestartet werden.</p>
                    @endunless
                    @include('filament.app.pages.transaction-csv-import.problems', ['report' => $report])
                </div>
            @endif

            @if ($report->duplicates() > 0)
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $report->duplicatesInDatabase }} Datensätze sind bereits vorhanden,
                    {{ $report->duplicatesInFile }} kommen in der Datei mehrfach vor
                    (Zeilen: {{ implode(', ', $report->duplicateLines) }}{{ $report->duplicates() > count($report->duplicateLines) ? ', …' : '' }}).
                </p>
            @endif

            @if ($report->valid === 0)
                <p class="font-medium text-warning-600">Es gibt keine neuen Transaktionen zu importieren.</p>
            @endif
        @endif

        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <p class="font-medium text-gray-700 dark:text-gray-200">Zuordnung</p>
                <ul class="mt-1 text-gray-600 dark:text-gray-300">
                    @foreach ($mapped as $field => $column)
                        <li>{{ $column }} → <strong>{{ $field }}</strong></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="font-medium text-gray-700 dark:text-gray-200">Nicht importiert</p>
                <p class="mt-1 text-gray-500 dark:text-gray-400">{{ $ignored === [] ? '–' : implode(', ', $ignored) }}</p>
            </div>
        </div>
    </div>
@endif
