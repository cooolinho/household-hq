<?php

namespace App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\Pages;

use App\Filament\Admin\Resources\EnergyTracker\MeasurementDevices\MeasurementDeviceResource;
use App\Models\EnergyTracker\MeasurementDevice;
use Filament\Resources\Pages\CreateRecord;

class CreateMeasurementDevice extends CreateRecord
{
    protected static string $resource = MeasurementDeviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[MeasurementDevice::user_id] = auth()->id();

        return $data;
    }
}
