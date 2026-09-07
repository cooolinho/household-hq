<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMeasurementDevice extends EditRecord
{
    protected static string $resource = MeasurementDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
