@php
    use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
    use App\Models\Enums\EnergyTrackerCountingMethodEnum;
    use App\Models\Enums\EnergyTrackerCountingTypeEnum;
    use App\Services\EnergyTracker\ReadingEntryValueGuard;

    $devices = $this->measurementDevices;
@endphp

<div class="space-y-5">
    @if($devices->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-300">Es sind noch keine Messgeraete vorhanden.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($devices as $device)
                @php
                    $typeEnum = EnergyTrackerCountingTypeEnum::tryFromName((string) $device->counting_type);
                    $icon = $typeEnum?->icon() ?? 'heroicon-o-cpu-chip';
                    $typeLabel = $typeEnum?->label() ?? (string) $device->counting_type;
                    $methodLabel = EnergyTrackerCountingMethodEnum::tryFromName((string) $device->counting_method)?->label() ?? (string) $device->counting_method;
                    $referenceValue = ReadingEntryValueGuard::referenceValue($device);
                    $viewUrl = MeasurementDeviceResource::getUrl('view', ['record' => $device]);
                    $editUrl = MeasurementDeviceResource::getUrl('edit', ['record' => $device]);
                @endphp

                <article
                        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
                    <div class="p-5 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $device->name }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $device->group ?: 'Ohne Gruppe' }}</p>
                            </div>
                            <div class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                                <x-filament::icon :icon="$icon" class="h-5 w-5"/>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                                <dt class="text-gray-500 dark:text-gray-400">Typ</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $typeLabel }}</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                                <dt class="text-gray-500 dark:text-gray-400">Methode</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $methodLabel }}</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                                <dt class="text-gray-500 dark:text-gray-400">Einheit</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $device->counting_unit }}</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2.5">
                                <dt class="text-gray-500 dark:text-gray-400">Ablesungen</dt>
                                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $device->reading_entries_count }}</dd>
                            </div>
                        </dl>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Letzter Referenzwert</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $referenceValue !== null ? number_format($referenceValue, (int) $device->decimal_places, ',', '.') : '-' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <x-filament::button
                                    size="sm"
                                    wire:click="mountAction('addReadingEntry', { deviceId: {{ $device->id }} })"
                                    icon="heroicon-o-plus"
                            >
                                Ablesung
                            </x-filament::button>

                            <x-filament::button size="sm" color="gray" :href="$viewUrl" tag="a" icon="heroicon-o-eye">
                                Details
                            </x-filament::button>

                            <x-filament::button size="sm" color="gray" :href="$editUrl" tag="a"
                                                icon="heroicon-o-pencil-square">
                                Bearbeiten
                            </x-filament::button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals/>
</div>

