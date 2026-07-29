@php
    use App\Models\Enums\EnergyTrackerCountingMethodEnum;
    use App\Models\Enums\EnergyTrackerCountingTypeEnum;
    use App\Services\EnergyTracker\ReadingEntryValueGuard;

    $device = $this->getCurrentDevice();
    $totalSteps = $this->getTotalSteps();
    $currentStep = $this->step + 1;
@endphp

<x-filament-panels::page>
    @if($device === null)
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-10 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-300">Es sind keine Messgeraete vorhanden.</p>
        </div>
    @else
        @php
            $typeEnum = EnergyTrackerCountingTypeEnum::tryFromName((string) $device->counting_type);
            $icon = $typeEnum?->icon() ?? 'heroicon-o-cpu-chip';
            $typeLabel = $typeEnum?->label() ?? (string) $device->counting_type;
            $methodLabel = EnergyTrackerCountingMethodEnum::tryFromName((string) $device->counting_method)?->label() ?? (string) $device->counting_method;
            $referenceValue = ReadingEntryValueGuard::referenceValue($device);
            $progress = $totalSteps > 0 ? ($currentStep / $totalSteps) * 100 : 0;
        @endphp

        <div class="mx-auto max-w-2xl space-y-6">
            <section
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Step {{ $currentStep }} von {{ $totalSteps }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $device->name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $device->group ?: 'Ohne Gruppe' }}</p>
                    </div>
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                        <x-filament::icon :icon="$icon" class="h-5 w-5"/>
                    </div>
                </div>

                <div class="mt-4 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-2 rounded-full bg-primary-600" style="width: {{ $progress }}%"></div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                        <p class="text-gray-500 dark:text-gray-400">Typ</p>
                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $typeLabel }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                        <p class="text-gray-500 dark:text-gray-400">Methode</p>
                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $methodLabel }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5 col-span-2">
                        <p class="text-gray-500 dark:text-gray-400">Referenzwert fuer Plausibilitaet</p>
                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $referenceValue !== null ? number_format($referenceValue, (int) $device->decimal_places, ',', '.') : '-' }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm space-y-4">
                <div>
                    <label for="readingValue" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Zaehlerstand</label>
                    <input
                            id="readingValue"
                            type="number"
                            step="any"
                            wire:model="readingValue"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    />
                    @error('readingValue')
                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="readingDate" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ablesedatum</label>
                    <input
                            id="readingDate"
                            type="datetime-local"
                            wire:model="readingDate"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                    />
                    @error('readingDate')
                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    <x-filament::button color="gray" size="sm" wire:click="previousStep" :disabled="$this->step === 0"
                                        icon="heroicon-o-arrow-left">
                        Zurueck
                    </x-filament::button>

                    <x-filament::button color="gray" size="sm" wire:click="skipStep"
                                        :disabled="$this->step >= ($this->getTotalSteps() - 1)"
                                        icon="heroicon-o-forward">
                        Ueberspringen
                    </x-filament::button>

                    <x-filament::button size="sm" wire:click="saveAndContinue" icon="heroicon-o-check">
                        {{ $this->step >= ($this->getTotalSteps() - 1) ? 'Speichern & Abschliessen' : 'Speichern & Weiter' }}
                    </x-filament::button>
                </div>
            </section>
        </div>
    @endif
</x-filament-panels::page>

