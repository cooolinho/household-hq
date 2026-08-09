<?php

namespace App\Filament\Admin\Resources\Financial\FixedCostCategories\Pages;

use App\Filament\Admin\Resources\Financial\FixedCostCategories\FixedCostCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFixedCostCategory extends EditRecord
{
    protected static string $resource = FixedCostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

