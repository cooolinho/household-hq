<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\Pages;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMeasurementDeviceContract extends ViewRecord
{
    protected static string $resource = MeasurementDeviceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
