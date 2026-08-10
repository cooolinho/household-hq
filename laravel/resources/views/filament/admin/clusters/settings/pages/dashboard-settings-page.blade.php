<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap gap-2">
        <x-filament::button type="button" color="success" wire:click="enableAllWidgets">
            Alle Widgets aktivieren
        </x-filament::button>
        <x-filament::button type="button" color="gray" wire:click="disableAllWidgets">
            Alle Widgets deaktivieren
        </x-filament::button>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Speichern
        </x-filament::button>
    </form>
</x-filament-panels::page>

