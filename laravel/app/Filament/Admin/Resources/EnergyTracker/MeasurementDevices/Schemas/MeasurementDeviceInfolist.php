<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Schemas;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MeasurementDeviceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make(MeasurementDevice::name),
                TextEntry::make(MeasurementDevice::group),
                TextEntry::make(MeasurementDevice::counting_type)
                    ->badge(),
                TextEntry::make(MeasurementDevice::counting_method)
                    ->badge(),
                TextEntry::make(MeasurementDevice::counting_unit)
                    ->badge(),
                TextEntry::make(MeasurementDevice::meter_reading_value),
                TextEntry::make(MeasurementDevice::meter_reading_date)
                    ->date(),
                TextEntry::make(MeasurementDevice::meter_description)
                    ->placeholder('-'),
                TextEntry::make('active_contract_name')
                    ->label('Aktiver Vertrag')
                    ->state(fn(MeasurementDevice $record): string => (string)($record->activeContract?->{MeasurementDeviceContract::name} ?? 'Kein aktiver Vertrag')),
                TextEntry::make(MeasurementDevice::decimal_places)
                    ->numeric(),
                TextEntry::make(MeasurementDevice::created_at)
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make(MeasurementDevice::updated_at)
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
