<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\Schemas\MeasurementDeviceContractDocumentForm;
use App\Models\EnergyTracker\MeasurementDeviceContract;

class CreateMeasurementDeviceContractDocument extends CreateRelatedDocument
{
    protected function getOwnerModelClass(): string
    {
        return MeasurementDeviceContract::class;
    }

    protected function getDocumentFormClass(): string
    {
        return MeasurementDeviceContractDocumentForm::class;
    }
}
