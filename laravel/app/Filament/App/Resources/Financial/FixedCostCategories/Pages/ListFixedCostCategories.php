<?php

namespace App\Filament\App\Resources\Financial\FixedCostCategories\Pages;

use App\Filament\App\Resources\Financial\FixedCostCategories\FixedCostCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFixedCostCategories extends ListRecords
{
    protected static string $resource = FixedCostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

