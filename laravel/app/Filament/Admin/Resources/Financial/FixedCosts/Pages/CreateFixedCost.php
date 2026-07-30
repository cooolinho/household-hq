<?php

namespace App\Filament\Admin\Resources\Financial\FixedCosts\Pages;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Models\Financial\FixedCost;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedCost extends CreateRecord
{
    protected static string $resource = FixedCostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data[FixedCost::user_id] = auth()->id();

        return $data;
    }
}
