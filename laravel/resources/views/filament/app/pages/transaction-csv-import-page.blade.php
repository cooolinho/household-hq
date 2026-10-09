<x-filament-panels::page>
    @include('filament.app.pages.transaction-csv-import.context')

    @if ($this->getResult())
        @include('filament.app.pages.transaction-csv-import.result')
    @else
        {{ $this->form }}
    @endif
</x-filament-panels::page>
