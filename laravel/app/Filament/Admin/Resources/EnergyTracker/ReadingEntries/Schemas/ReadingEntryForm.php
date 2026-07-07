<?php

namespace App\Filament\Admin\Resources\EnergyTracker\ReadingEntries\Schemas;

use App\Filament\Admin\Resources\Tags\TagResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                ->relationship(ReadingEntry::belongs_to_measurement_device, MeasurementDevice::name)
                ->required();
        }

        $schemas[] = TextInput::make(ReadingEntry::reading_value)
            ->default($measurementDevice->lastReadingEntry()?->reading_value ?? $measurementDevice->meter_reading_value)
            ->numeric()
            ->required();
        $schemas[] = DateTimePicker::make(ReadingEntry::reading_date)
            ->default(now())
            ->required();

        $schemas[] = TagResource::getMorphToManySelect($schema, ReadingEntry::morph_to_many_tags);

        return $schema
            ->components($schemas);
    }
}
