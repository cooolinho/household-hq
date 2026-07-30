<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Schemas;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use App\Models\Enums\EnergyTrackerCountingTypeEnum;
use App\Models\Enums\EnergyTrackerUnitEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MeasurementDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('base')
                    ->heading(false)
                    ->schema([
                        TextInput::make(MeasurementDevice::name)
                            ->required(),
                        TextInput::make(MeasurementDevice::group),
                    ]),
                Section::make('counting')
                    ->heading(false)
                    ->schema([
                        Select::make(MeasurementDevice::counting_type)
                            ->options(EnergyTrackerCountingTypeEnum::options())
                            ->default(EnergyTrackerCountingTypeEnum::default())
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                /** @var EnergyTrackerCountingTypeEnum $enum */
                                $enum = EnergyTrackerCountingTypeEnum::{$state};
                                $set(MeasurementDevice::counting_unit, $enum->defaultUnit()->name);
                            })
                            ->required(),
                        Select::make(MeasurementDevice::counting_method)
                            ->options(EnergyTrackerCountingMethodEnum::options())
                            ->default(EnergyTrackerCountingMethodEnum::default())
                            ->required(),
                        Select::make(MeasurementDevice::counting_unit)
                            ->options(EnergyTrackerUnitEnum::options())
                            ->default(EnergyTrackerUnitEnum::default())
                            ->required(),
                    ]),
                Section::make('meter')
                    ->heading(false)
                    ->schema([
                        TextInput::make(MeasurementDevice::meter_reading_value)
                            ->numeric()
                            ->default(0)
                            ->required(),
                        DateTimePicker::make(MeasurementDevice::meter_reading_date)
                            ->default(now())
                            ->required(),
                        TextInput::make(MeasurementDevice::meter_description),
                    ]),
                Section::make('additional')
                    ->heading(false)
                    ->schema([
                        TextInput::make(MeasurementDevice::decimal_places)
                            ->required()
                            ->numeric()
                            ->default(4),
                    ]),
            ]);
    }
}
