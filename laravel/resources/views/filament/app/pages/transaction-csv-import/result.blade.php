{{-- Ergebnis nach dem Import --}}
@php($report = $this->getResult())

<x-filament::section>
    <x-slot name="heading">Import abgeschlossen</x-slot>

    <div class="space-y-4 text-sm">
        <dl class="grid max-w-md grid-cols-2 gap-x-6 gap-y-1">
            <dt class="text-gray-500">Erfolgreich importiert</dt>
            <dd class="font-semibold text-success-600">{{ $report->imported }}</dd>
            <dt class="text-gray-500">Übersprungen</dt>
            <dd class="font-semibold">{{ $report->skipped() }}</dd>
            <dt class="text-gray-500">Fehler</dt>
            <dd class="font-semibold {{ $report->invalid > 0 ? 'text-danger-600' : '' }}">{{ $report->invalid }}</dd>
            <dt class="text-gray-500">Duplikate</dt>
            <dd class="font-semibold">{{ $report->duplicates() }}</dd>
            @if ($report->emptyLines > 0)
                <dt class="text-gray-500">Leere Zeilen</dt>
                <dd class="font-semibold">{{ $report->emptyLines }}</dd>
            @endif
        </dl>

        @if ($report->invalid > 0)
            <div class="space-y-2 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">
                <p class="font-medium">Diese Datensätze wurden wegen Fehlern nicht importiert:</p>
                @include('filament.app.pages.transaction-csv-import.problems', ['report' => $report])
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            @if ($url = $this->getBankAccountUrl())
                <x-filament::button tag="a" :href="$url" icon="heroicon-o-banknotes">
                    Zum Bankkonto
                </x-filament::button>
            @endif
            <x-filament::button color="gray" wire:click="startNewImport" icon="heroicon-o-arrow-path">
                Weitere Datei importieren
            </x-filament::button>
        </div>
    </div>
</x-filament::section>
