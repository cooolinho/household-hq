<x-filament-panels::page>
    @if ($this->jobStatus === 'processing')
        @include('filament.app.pages.features.merge-images-to-pdf-wizard-page-processing')
    @else
        {{-- Wizard --}}
        {{ $this->content }}
    @endif
</x-filament-panels::page>
