<?php

namespace App\Filament\Admin\Resources\Insurances\Pages;

use App\Filament\Admin\Resources\Insurances\InsuranceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurances extends ListRecords
{
    protected static string $resource = InsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
