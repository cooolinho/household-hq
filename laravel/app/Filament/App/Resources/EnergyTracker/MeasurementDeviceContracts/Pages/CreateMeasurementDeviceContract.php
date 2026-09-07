<?php

namespace App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\Pages;

use App\Filament\App\Resources\EnergyTracker\MeasurementDeviceContracts\MeasurementDeviceContractResource;
use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\MeasurementDeviceContract;
use Filament\Resources\Pages\CreateRecord;

class CreateMeasurementDeviceContract extends CreateRecord
{
    protected static string $resource = MeasurementDeviceContractResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $deviceId = $data[MeasurementDeviceContract::measurement_device_id] ?? null;

        abort_unless(
            MeasurementDevice::query()
                ->whereKey($deviceId)
                ->where(MeasurementDevice::user_id, auth()->id())
                ->exists(),
            403,
        );

        return $data;
    }
}
