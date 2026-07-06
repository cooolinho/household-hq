<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Pages;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurance extends ViewRecord
{
    protected static string $resource = InsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
