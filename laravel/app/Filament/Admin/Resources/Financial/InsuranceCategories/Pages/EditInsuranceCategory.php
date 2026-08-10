<?php

namespace App\Filament\Admin\Resources\Financial\InsuranceCategories\Pages;

use App\Filament\Admin\Resources\Financial\InsuranceCategories\InsuranceCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceCategory extends EditRecord
{
    protected static string $resource = InsuranceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

