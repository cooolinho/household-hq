<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeasurementDeviceContracts extends ListRecords
{
    protected static string $resource = MeasurementDeviceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
