<?php

namespace App\Filament\Admin\Resources\EnergyTracker\ReadingEntries\Schemas;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Services\EnergyTracker\ReadingEntryValueGuard;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ReadingEntryForm
{
    public static function configure(
        Schema             $schema,
        ?MeasurementDevice $measurementDevice = null
    ): Schema
    {
        $schemas = [];
        if ($measurementDevice !== null) {
            $schemas[] = Hidden::make(ReadingEntry::measurement_device_id)
                ->default($measurementDevice->id)
                ->required();
        } else {
            $schemas[] = Select::make(ReadingEntry::measurement_device_id)
                ->options(fn(): array => MeasurementDevice::query()
                    ->where(MeasurementDevice::user_id, auth()->id())
                    ->orderBy(MeasurementDevice::name)
                    ->pluck(MeasurementDevice::name, MeasurementDevice::id)
                    ->toArray())
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set): void {
                    $device = MeasurementDevice::query()->find($state);

                    $set(
                        ReadingEntry::reading_value,
                        $device ? ReadingEntryValueGuard::defaultValue($device) : null,
                    );
                })
                ->required();
        }

        $schemas[] = TextInput::make(ReadingEntry::reading_value)
            ->default($measurementDevice ? ReadingEntryValueGuard::defaultValue($measurementDevice) : null)
            ->numeric()
            ->rule(function (Get $get) use ($measurementDevice) {
                return function (string $attribute, mixed $value, Closure $fail) use ($get, $measurementDevice): void {
                    $device = $measurementDevice;

                    if (!$device && filled($deviceId = $get(ReadingEntry::measurement_device_id))) {
                        $device = MeasurementDevice::query()->find($deviceId);
                    }

                    if (!$device || $value === null || $value === '') {
                        return;
                    }

                    $validationError = ReadingEntryValueGuard::validateValue($device, (float)$value);

                    if ($validationError !== null) {
                        $fail($validationError);
                    }
                };
            })
            ->required();
        $schemas[] = DateTimePicker::make(ReadingEntry::reading_date)
            ->default(now())
            ->required();

        $schemas[] = TagResource::getMorphToManySelect($schema, ReadingEntry::morph_to_many_tags);

        return $schema
            ->components($schemas);
    }
}
