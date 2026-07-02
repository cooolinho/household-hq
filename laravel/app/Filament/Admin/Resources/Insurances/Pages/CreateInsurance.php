<?php

namespace App\Filament\Admin\Resources\Insurances\Pages;

use App\Filament\Admin\Resources\Insurances\InsuranceResource;
use App\Models\Insurance;
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
