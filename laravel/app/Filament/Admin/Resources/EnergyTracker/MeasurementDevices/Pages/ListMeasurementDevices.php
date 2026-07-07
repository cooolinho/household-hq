<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeasurementDevices extends ListRecords
{
    protected static string $resource = MeasurementDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
