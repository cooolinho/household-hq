<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Pages;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInsurance extends EditRecord
{
    protected static string $resource = InsuranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
