<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Schemas;

use App\Models\EnergyTracker\MeasurementDeviceContractPrice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MeasurementDeviceContractPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make(MeasurementDeviceContractPrice::unit_price)
                    ->label('Preis pro Einheit')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.000001)
                    ->prefix('€')
                    ->required(),
                DatePicker::make(MeasurementDeviceContractPrice::valid_from)
                    ->label('Gültig ab')
                    ->required(),
                Textarea::make(MeasurementDeviceContractPrice::notes)
                    ->label('Notizen')
                    ->columnSpanFull(),
            ]);
    }
}
