<?php

namespace App\Filament\App\Resources\Financial\Insurances\Pages;

use App\Filament\App\Resources\Financial\Insurances\InsuranceResource;
use App\Models\Financial\Insurance;
use Filament\Resources\Pages\CreateRecord;

class CreateInsurance extends CreateRecord
{
    protected static string $resource = InsuranceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[Insurance::user_id] = auth()->id();

        return $data;
    }
}
