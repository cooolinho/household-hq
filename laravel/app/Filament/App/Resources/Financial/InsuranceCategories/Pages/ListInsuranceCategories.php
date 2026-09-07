<?php

namespace App\Filament\App\Resources\Financial\InsuranceCategories\Pages;

use App\Filament\App\Resources\Financial\InsuranceCategories\InsuranceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCategories extends ListRecords
{
    protected static string $resource = InsuranceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

