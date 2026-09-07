<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMeasurementDeviceContract extends EditRecord
{
    protected static string $resource = MeasurementDeviceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
